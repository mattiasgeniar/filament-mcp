<?php

namespace Mattiasgeniar\FilamentMcp\Server;

use Filament\Resources\Resource;
use InvalidArgumentException;
use Laravel\Mcp\Server\Tool;
use Mattiasgeniar\FilamentMcp\Actions\ResourceAction;
use Mattiasgeniar\FilamentMcp\Contracts\PreparesRecordData;
use Mattiasgeniar\FilamentMcp\Introspection\FieldDefinition;
use Mattiasgeniar\FilamentMcp\Introspection\ReadableField;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceIntrospector;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceSchema;
use Mattiasgeniar\FilamentMcp\Introspection\SchemaCompiler;
use Mattiasgeniar\FilamentMcp\Support\ResourceAuthorizer;
use Mattiasgeniar\FilamentMcp\Tools\ActionTool;
use Mattiasgeniar\FilamentMcp\Tools\CreateRecordTool;
use Mattiasgeniar\FilamentMcp\Tools\DeleteRecordTool;
use Mattiasgeniar\FilamentMcp\Tools\DescribeResourcesTool;
use Mattiasgeniar\FilamentMcp\Tools\GetRecordTool;
use Mattiasgeniar\FilamentMcp\Tools\ListRecordsTool;
use Mattiasgeniar\FilamentMcp\Tools\ResourceTool;
use Mattiasgeniar\FilamentMcp\Tools\UpdateRecordTool;

class ToolFactory
{
    public function __construct(
        private readonly ResourceIntrospector $introspector = new ResourceIntrospector,
        private readonly SchemaCompiler $compiler = new SchemaCompiler,
        private readonly ResourceAuthorizer $authorizer = new ResourceAuthorizer,
    ) {}

    /**
     * @return array<int, Tool>
     */
    public function make(): array
    {
        $tools = [];

        foreach ($this->resources() as $resourceClass => $abilities) {
            $schema = $this->introspector->for($resourceClass, $abilities['read_fields'] ?? []);
            $prepare = $this->resolvePrepare($abilities['prepare'] ?? null);
            $operations = $this->operations($resourceClass, $abilities);

            if ($operations['read']) {
                $tools[] = $this->tool(ListRecordsTool::class, $schema, $prepare);
                $tools[] = $this->tool(GetRecordTool::class, $schema, $prepare);
            }

            if ($operations['create']) {
                $tools[] = $this->tool(CreateRecordTool::class, $schema, $prepare);
            }

            if ($operations['update']) {
                $tools[] = $this->tool(UpdateRecordTool::class, $schema, $prepare);
            }

            if ($operations['delete']) {
                $tools[] = $this->tool(DeleteRecordTool::class, $schema, $prepare);
            }

            /** @var array<string, mixed> $actions */
            $actions = $abilities['actions'] ?? [];

            foreach ($actions as $actionKey => $actionClass) {
                $tools[] = new ActionTool($schema, $this->authorizer, $actionKey, $this->resolveAction($actionClass));
            }
        }

        $tools[] = new DescribeResourcesTool;

        return $tools;
    }

    /**
     * A compact map of the exposed resources, their operations, and their
     * readable/writable fields, for agent self-discovery.
     *
     * @return array<int, array<string, mixed>>
     */
    public function describe(): array
    {
        $described = [];

        foreach ($this->resources() as $resourceClass => $abilities) {
            $schema = $this->introspector->for($resourceClass, $abilities['read_fields'] ?? []);

            /** @var array<string, mixed> $actions */
            $actions = $abilities['actions'] ?? [];

            $described[] = [
                'resource' => $schema->singularName(),
                'operations' => $this->operations($resourceClass, $abilities),
                'actions' => array_keys($actions),
                'readable_fields' => $schema->readableFields->map(fn (ReadableField $field): string => $field->name)->values()->all(),
                'writable_fields' => $schema->fields->map(fn (FieldDefinition $field): string => $field->name)->values()->all(),
            ];
        }

        return $described;
    }

    /**
     * @return array<class-string<\Filament\Resources\Resource>, array<string, mixed>>
     */
    private function resources(): array
    {
        /** @var array<int|string, mixed> $resources */
        $resources = config('filament-mcp.resources', []);

        $normalized = [];

        foreach ($resources as $key => $value) {
            [$resourceClass, $abilities] = $this->normalize($key, $value);
            $normalized[$resourceClass] = $abilities;
        }

        return $normalized;
    }

    /**
     * @param  class-string<\Filament\Resources\Resource>  $resourceClass
     * @param  array<string, mixed>  $abilities
     * @return array{read: bool, create: bool, update: bool, delete: bool}
     */
    private function operations(string $resourceClass, array $abilities): array
    {
        $write = $abilities['write'] ?? null;
        $surface = $this->resourceSurface($resourceClass);

        return [
            'read' => (bool) ($abilities['read'] ?? true) && $surface['read'],
            'create' => (bool) ($abilities['create'] ?? ($write ?? true)) && $surface['create'],
            'update' => (bool) ($abilities['update'] ?? ($write ?? true)) && $surface['update'],
            'delete' => (bool) ($abilities['delete'] ?? false) && $surface['delete'],
        ];
    }

    /**
     * Infer the dashboard surface exposed by Filament's resource pages. Destructive
     * deletes still require an explicit filament-mcp opt-in in operations().
     *
     * @param  class-string<\Filament\Resources\Resource>  $resourceClass
     * @return array{read: bool, create: bool, update: bool, delete: bool}
     */
    private function resourceSurface(string $resourceClass): array
    {
        $pages = array_keys($resourceClass::getPages());

        return [
            'read' => $this->hasAnyPage($pages, ['index', 'manage']),
            'create' => $this->hasAnyPage($pages, ['create', 'manage']),
            'update' => $this->hasAnyPage($pages, ['edit', 'manage']),
            'delete' => $this->hasAnyPage($pages, ['index', 'edit', 'manage']),
        ];
    }

    /**
     * @param  array<int, string>  $pages
     * @param  array<int, string>  $needles
     */
    private function hasAnyPage(array $pages, array $needles): bool
    {
        return array_intersect($needles, $pages) !== [];
    }

    private function resolveAction(mixed $action): ResourceAction
    {
        if ($action instanceof ResourceAction) {
            return $action;
        }

        $resolved = app($action);

        if (! $resolved instanceof ResourceAction) {
            throw new InvalidArgumentException(
                'A filament-mcp [actions] entry must extend ' . ResourceAction::class . '.'
            );
        }

        return $resolved;
    }

    /**
     * @return array{0: class-string<\Filament\Resources\Resource>, 1: array<string, mixed>}
     */
    private function normalize(int | string $key, mixed $value): array
    {
        if (is_string($key)) {
            /** @var class-string<\Filament\Resources\Resource> $key */
            return [$key, is_array($value) ? $value : []];
        }

        /** @var class-string<\Filament\Resources\Resource> $value */
        return [$value, []];
    }

    private function resolvePrepare(mixed $prepare): ?PreparesRecordData
    {
        if ($prepare === null) {
            return null;
        }

        if ($prepare instanceof PreparesRecordData) {
            return $prepare;
        }

        $resolved = app($prepare);

        if (! $resolved instanceof PreparesRecordData) {
            throw new InvalidArgumentException(
                'A filament-mcp [prepare] class must implement ' . PreparesRecordData::class . '.'
            );
        }

        return $resolved;
    }

    /**
     * @param  class-string<ResourceTool>  $toolClass
     */
    private function tool(string $toolClass, ResourceSchema $schema, ?PreparesRecordData $prepare): ResourceTool
    {
        return new $toolClass($schema, $this->compiler, $this->authorizer, $prepare);
    }
}

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

            if ($operations['list']) {
                $tools[] = $this->tool(ListRecordsTool::class, $schema, $prepare);
            }

            if ($operations['read']) {
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
     * @return array{read: bool, list: bool, create: bool, update: bool, delete: bool}
     */
    private function operations(string $resourceClass, array $abilities): array
    {
        $write = $abilities['write'] ?? null;
        $surface = $this->resourceSurface($resourceClass);

        return [
            'read' => $this->resolveOperation($abilities, 'read', true, $surface['read']),
            'list' => $this->resolveOperation($abilities, 'list', (bool) ($abilities['read'] ?? true), $surface['list']),
            'create' => $this->resolveOperation($abilities, 'create', $write ?? true, $surface['create']),
            'update' => $this->resolveOperation($abilities, 'update', $write ?? true, $surface['update']),
            'delete' => $this->resolveOperation($abilities, 'delete', false, $surface['delete']),
        ];
    }

    /**
     * Resolve a single operation. An explicit boolean in the resource config is
     * an override: it force-exposes (or force-hides) the operation regardless of
     * the inferred dashboard surface, so a view-only resource can still opt in to
     * `list` without registering an index page. When the key is absent the
     * default is gated by the surface, preserving the page-driven inference.
     *
     * @param  array<string, mixed>  $abilities
     */
    private function resolveOperation(array $abilities, string $operation, bool $default, bool $surface): bool
    {
        if (array_key_exists($operation, $abilities)) {
            return (bool) $abilities[$operation];
        }

        return $default && $surface;
    }

    /**
     * Infer the dashboard surface exposed by Filament's resource pages. This is the
     * default gate for each operation; an explicit boolean in the resource config
     * overrides it (see resolveOperation()). Delete additionally defaults to off.
     *
     * @param  class-string<\Filament\Resources\Resource>  $resourceClass
     * @return array{read: bool, list: bool, create: bool, update: bool, delete: bool}
     */
    private function resourceSurface(string $resourceClass): array
    {
        $pages = array_keys($resourceClass::getPages());

        return [
            'read' => $this->hasAnyPage($pages, ['view', 'index', 'manage']),
            'list' => $this->hasAnyPage($pages, ['index', 'manage']),
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

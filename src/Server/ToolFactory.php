<?php

namespace Mattiasgeniar\FilamentMcp\Server;

use Mattiasgeniar\FilamentMcp\Contracts\PreparesRecordData;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceIntrospector;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceSchema;
use Mattiasgeniar\FilamentMcp\Introspection\SchemaCompiler;
use Mattiasgeniar\FilamentMcp\Support\ResourceAuthorizer;
use Mattiasgeniar\FilamentMcp\Tools\CreateRecordTool;
use Mattiasgeniar\FilamentMcp\Tools\DeleteRecordTool;
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
     * @return array<int, ResourceTool>
     */
    public function make(): array
    {
        $tools = [];

        /** @var array<int|string, mixed> $resources */
        $resources = config('filament-mcp.resources', []);

        foreach ($resources as $key => $value) {
            [$resourceClass, $abilities] = $this->normalize($key, $value);

            $schema = $this->introspector->for($resourceClass);
            $prepare = $this->resolvePrepare($abilities['prepare'] ?? null);

            $write = $abilities['write'] ?? null;

            if ($abilities['read'] ?? true) {
                $tools[] = $this->tool(ListRecordsTool::class, $schema, $prepare);
                $tools[] = $this->tool(GetRecordTool::class, $schema, $prepare);
            }

            if ($abilities['create'] ?? ($write ?? true)) {
                $tools[] = $this->tool(CreateRecordTool::class, $schema, $prepare);
            }

            if ($abilities['update'] ?? ($write ?? true)) {
                $tools[] = $this->tool(UpdateRecordTool::class, $schema, $prepare);
            }

            if ($abilities['delete'] ?? true) {
                $tools[] = $this->tool(DeleteRecordTool::class, $schema, $prepare);
            }
        }

        return $tools;
    }

    /**
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    private function normalize(int | string $key, mixed $value): array
    {
        if (is_string($key)) {
            /** @var class-string $key */
            return [$key, is_array($value) ? $value : []];
        }

        /** @var class-string $value */
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

        return app($prepare);
    }

    /**
     * @param  class-string<ResourceTool>  $toolClass
     */
    private function tool(string $toolClass, ResourceSchema $schema, ?PreparesRecordData $prepare): ResourceTool
    {
        return new $toolClass($schema, $this->compiler, $this->authorizer, $prepare);
    }
}

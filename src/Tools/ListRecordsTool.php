<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListRecordsTool extends ResourceTool
{
    public function name(): string
    {
        return 'list_' . $this->resource->pluralName();
    }

    public function description(): string
    {
        return "List {$this->resource->pluralName()}, most recent first.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Maximum records to return (default 25, max 100).'),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'viewAny')) {
            return Response::error("Not authorized to view {$this->resource->pluralName()}.");
        }

        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $modelClass = $this->modelClass();
        $keyName = (new $modelClass)->getKeyName();

        $records = $modelClass::query()
            ->latest($keyName)
            ->limit($validated['limit'] ?? 25)
            ->get();

        return Response::text((string) json_encode([
            'count' => $records->count(),
            'records' => $records->map(fn (Model $model): array => $this->present($model))->all(),
        ], JSON_PRETTY_PRINT));
    }
}

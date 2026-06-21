<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetRecordTool extends ResourceTool
{
    public function name(): string
    {
        return 'get_' . $this->resource->singularName();
    }

    public function description(): string
    {
        return "Get a single {$this->resource->singularName()} by id.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description("The id of the {$this->resource->singularName()}.")->required(),
        ];
    }

    protected function run(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required'],
        ]);

        $record = $this->findRecord($validated['id']);

        if ($record === null) {
            return Response::error("No {$this->resource->singularName()} found with id {$validated['id']}.");
        }

        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'view', $record)) {
            return Response::error("Not authorized to view this {$this->resource->singularName()}.");
        }

        return $this->json($this->present($record));
    }
}

<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteRecordTool extends ResourceTool
{
    public function name(): string
    {
        return 'delete_' . $this->resource->singularName();
    }

    public function description(): string
    {
        return "Delete a {$this->resource->singularName()} by id. This cannot be undone.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description("The id of the {$this->resource->singularName()} to delete.")->required(),
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

        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'delete', $record)) {
            return Response::error("Not authorized to delete this {$this->resource->singularName()}.");
        }

        $record->delete();

        return $this->json([
            'success' => true,
            'deleted_id' => $validated['id'],
        ]);
    }
}

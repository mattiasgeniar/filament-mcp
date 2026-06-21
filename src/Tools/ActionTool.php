<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Mattiasgeniar\FilamentMcp\Actions\ResourceAction;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceSchema;
use Mattiasgeniar\FilamentMcp\Introspection\SchemaCompiler;
use Mattiasgeniar\FilamentMcp\Support\ResourceAuthorizer;

class ActionTool extends ResourceTool
{
    public function __construct(
        ResourceSchema $resource,
        ResourceAuthorizer $authorizer,
        private readonly string $key,
        private readonly ResourceAction $action,
    ) {
        parent::__construct($resource, new SchemaCompiler, $authorizer);
    }

    public function name(): string
    {
        return $this->key . '_' . $this->resource->singularName();
    }

    public function description(): string
    {
        return $this->action->description();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description("The id of the {$this->resource->singularName()} to act on.")->required(),
            ...$this->action->schema($schema),
        ];
    }

    protected function run(Request $request): Response
    {
        $validated = $request->validate(['id' => ['required']] + $this->action->rules());

        $record = $this->findRecord($validated['id']);

        if ($record === null) {
            return Response::error("No {$this->resource->singularName()} found with id {$validated['id']}.");
        }

        if (! $this->authorizer->allows($this->user(), $this->modelClass(), $this->action->ability(), $record)) {
            return Response::error("Not authorized to {$this->key} this {$this->resource->singularName()}.");
        }

        unset($validated['id']);

        $result = $this->action->handle($record, $validated);

        return $this->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}

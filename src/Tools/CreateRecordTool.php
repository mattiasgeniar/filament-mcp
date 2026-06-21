<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class CreateRecordTool extends ResourceTool
{
    public function name(): string
    {
        return 'create_' . $this->resource->singularName();
    }

    public function description(): string
    {
        return "Create a new {$this->resource->singularName()}.";
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->compiler->inputProperties($schema, $this->resource->fields, forUpdate: false);
    }

    protected function run(Request $request): Response
    {
        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'create')) {
            return Response::error("Not authorized to create {$this->resource->pluralName()}.");
        }

        $validated = $request->validate($this->compiler->validationRules($this->resource->fields, forUpdate: false));

        $modelClass = $this->modelClass();
        $record = new $modelClass;

        // forceFill is safe: the writable keys are the form-introspected,
        // validated fields, which act as the allowlist regardless of $fillable.
        $record->forceFill($this->prepare($validated, null));
        $record->save();
        $record->refresh();

        return $this->json([
            'success' => true,
            'record' => $this->present($record),
        ]);
    }
}

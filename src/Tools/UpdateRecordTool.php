<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class UpdateRecordTool extends ResourceTool
{
    public function name(): string
    {
        return 'update_' . $this->resource->singularName();
    }

    public function description(): string
    {
        return "Update an existing {$this->resource->singularName()} by id. Only the fields you pass are changed.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description("The id of the {$this->resource->singularName()} to update.")->required(),
            ...$this->compiler->inputProperties($schema, $this->resource->fields, forUpdate: true),
        ];
    }

    protected function run(Request $request): Response
    {
        $rules = ['id' => ['required']] + $this->compiler->validationRules($this->resource->fields, forUpdate: true);

        $validated = $request->validate($rules);

        $record = $this->findRecord($validated['id']);

        if ($record === null) {
            return Response::error("No {$this->resource->singularName()} found with id {$validated['id']}.");
        }

        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'update', $record)) {
            return Response::error("Not authorized to update this {$this->resource->singularName()}.");
        }

        unset($validated['id']);

        // forceFill is safe: the writable keys are the form-introspected,
        // validated fields, which act as the allowlist regardless of $fillable.
        $record->forceFill($this->prepare($validated, $record));
        $record->save();

        return Response::text((string) json_encode([
            'success' => true,
            'record' => $this->present($record),
        ], JSON_PRETTY_PRINT));
    }
}

<?php

namespace Mattiasgeniar\FilamentMcp\Actions;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * A custom action exposed to the MCP for a single record. Register one per
 * resource via the `actions` config key. This is the guardrailed way to mirror
 * a Filament action or bulk action: the developer decides exactly what an agent
 * can trigger and what it does.
 */
abstract class ResourceAction
{
    abstract public function description(): string;

    /**
     * Extra arguments the action accepts, beyond the record `id`.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * Run the action against the resolved record. The return value is sent back
     * to the agent as the tool result.
     *
     * @param  array<string, mixed>  $arguments
     */
    abstract public function handle(Model $record, array $arguments): mixed;
}

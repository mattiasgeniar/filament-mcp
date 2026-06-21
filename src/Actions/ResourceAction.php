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
     * The policy ability the acting user must satisfy on the record before the
     * action runs. Filament treats custom record actions as mutations, so this
     * defaults to `update`; override it to map onto a dedicated policy method.
     */
    public function ability(): string
    {
        return 'update';
    }

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
     * Validation rules for the action's arguments. Only keys with a rule here
     * reach handle(), so the agent cannot smuggle undeclared attributes into the
     * action. Mirror whatever schema() advertises.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
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

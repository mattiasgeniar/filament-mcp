<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool as BaseTool;
use Mattiasgeniar\FilamentMcp\Introspection\ReadableField;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceSchema;
use Mattiasgeniar\FilamentMcp\Introspection\SchemaCompiler;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;
use Mattiasgeniar\FilamentMcp\Support\ResourceAuthorizer;
use Throwable;

abstract class ResourceTool extends BaseTool
{
    /**
     * @param  (callable(array<string, mixed>, ?Model): array<string, mixed>)|null  $prepareData
     */
    public function __construct(
        protected ResourceSchema $resource,
        protected SchemaCompiler $compiler,
        protected ResourceAuthorizer $authorizer,
        protected mixed $prepareData = null,
    ) {}

    abstract protected function run(Request $request): Response;

    final public function handle(Request $request): Response
    {
        $start = hrtime(true);
        $success = false;

        try {
            $response = $this->run($request);
            $success = ! $response->isError();

            return $response;
        } finally {
            $this->recordToolCall($request, $success, $start);
        }
    }

    private function recordToolCall(Request $request, bool $success, float $start): void
    {
        try {
            FilamentMcpToolCall::query()->create([
                'user_id' => request()->user()?->getAuthIdentifier(),
                'tool_name' => $this->name(),
                'arguments' => $request->all(),
                'success' => $success,
                'duration_ms' => (int) ((hrtime(true) - $start) / 1_000_000),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function user(): Authenticatable
    {
        $user = request()->user();

        if (! $user instanceof Authenticatable) {
            abort(500, 'Filament MCP context is missing a user.');
        }

        return $user;
    }

    /**
     * @return class-string<Model>
     */
    protected function modelClass(): string
    {
        return $this->resource->modelClass;
    }

    /**
     * The resource's base query, so the host app's tenant scopes, soft-delete
     * filters, and any getEloquentQuery() modifications apply here too.
     *
     * @return Builder<Model>
     */
    protected function query(): Builder
    {
        return ($this->resource->resourceClass)::getEloquentQuery();
    }

    protected function findRecord(int | string $id): ?Model
    {
        return $this->query()->find($id);
    }

    protected function keyName(): string
    {
        return (new ($this->modelClass()))->getKeyName();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function json(array $payload): Response
    {
        return Response::text((string) json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, ?Model $record): array
    {
        if ($this->prepareData === null) {
            return $data;
        }

        return ($this->prepareData)($data, $record);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Model $model): array
    {
        $hidden = $model->getHidden();
        $data = ['id' => $model->getKey()];

        $this->resource->readableFields
            ->reject(fn (ReadableField $field): bool => in_array($field->name, $hidden, true))
            ->each(function (ReadableField $field) use ($model, &$data): void {
                $data[$field->name] = $this->normalize($model->getAttribute($field->name));
            });

        collect(['created_at', 'updated_at'])
            ->reject(fn (string $column): bool => in_array($column, $hidden, true))
            ->each(function (string $column) use ($model, &$data): void {
                $value = $model->getAttribute($column);

                if ($value === null) {
                    return;
                }

                $data[$column] = $this->normalize($value);
            });

        return $data;
    }

    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        return $value;
    }
}

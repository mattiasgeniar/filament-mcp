<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool as BaseTool;
use Mattiasgeniar\FilamentMcp\Introspection\FieldDefinition;
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
        $success = true;

        try {
            return $this->run($request);
        } catch (Throwable $exception) {
            $success = false;

            throw $exception;
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

    protected function findRecord(int $id): ?Model
    {
        return $this->modelClass()::query()->find($id);
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
        $data = ['id' => $model->getKey()];

        $this->resource->fields->each(function (FieldDefinition $field) use ($model, &$data): void {
            $data[$field->name] = $this->normalize($model->getAttribute($field->name));
        });

        collect(['created_at', 'updated_at'])->each(function (string $column) use ($model, &$data): void {
            $value = $model->getAttribute($column);

            if ($value === null) {
                return;
            }

            $data[$column] = $this->normalize($value);
        });

        return $data;
    }

    private function normalize(mixed $value): mixed
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

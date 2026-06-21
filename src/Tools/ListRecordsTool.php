<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Mattiasgeniar\FilamentMcp\Introspection\ReadableField;

#[IsReadOnly]
class ListRecordsTool extends ResourceTool
{
    protected int $defaultPerPage = 25;

    protected int $maxPerPage = 100;

    public function name(): string
    {
        return 'list_' . $this->resource->pluralName();
    }

    public function description(): string
    {
        return "List {$this->resource->pluralName()} with optional search, field filters, sorting, and pagination.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Case-insensitive substring match across the readable fields.'),
            'filters' => $schema->object()->description('Exact-match filters keyed by field name. Array values match any of the listed values.'),
            'sort' => $schema->string()->enum($this->sortableColumns())->description('Field to sort by.'),
            'direction' => $schema->string()->enum(['asc', 'desc'])->description('Sort direction (default desc).'),
            'page' => $schema->integer()->description('Page number (default 1).'),
            'per_page' => $schema->integer()->description("Records per page (default {$this->defaultPerPage}, max {$this->maxPerPage})."),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->authorizer->allows($this->user(), $this->modelClass(), 'viewAny')) {
            return Response::error("Not authorized to view {$this->resource->pluralName()}.");
        }

        $validated = $request->validate([
            'search' => ['sometimes', 'string'],
            'filters' => ['sometimes', 'array'],
            'sort' => ['sometimes', 'string', Rule::in($this->sortableColumns())],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', "max:{$this->maxPerPage}"],
        ]);

        $query = $this->query();

        $this->applySearch($query, $validated['search'] ?? null);
        $this->applyFilters($query, $validated['filters'] ?? []);

        $query->orderBy(
            $validated['sort'] ?? $this->keyName(),
            $validated['direction'] ?? 'desc',
        );

        $perPage = $validated['per_page'] ?? $this->defaultPerPage;
        $page = $validated['page'] ?? 1;
        $total = (clone $query)->count();

        $records = $query->forPage($page, $perPage)->get();

        return $this->json([
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'records' => $records->map(fn (Model $model): array => $this->present($model))->all(),
        ]);
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $fields = $this->fieldNames();

        $query->where(function (Builder $query) use ($fields, $search): void {
            foreach ($fields as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $allowed = $this->fieldNames();

        foreach ($filters as $field => $value) {
            if (! in_array($field, $allowed, true)) {
                continue;
            }

            is_array($value)
                ? $query->whereIn($field, $value)
                : $query->where($field, $value);
        }
    }

    /**
     * @return array<int, string>
     */
    private function fieldNames(): array
    {
        return $this->resource->readableFields->map(fn (ReadableField $field): string => $field->name)->all();
    }

    /**
     * @return array<int, string>
     */
    private function sortableColumns(): array
    {
        return array_values(array_unique([
            ...$this->fieldNames(),
            $this->keyName(),
        ]));
    }
}

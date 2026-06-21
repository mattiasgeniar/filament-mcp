<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResourceSchema
{
    /**
     * @param  class-string  $resourceClass
     * @param  class-string<Model>  $modelClass
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<int, string>  $skippedFields
     */
    public function __construct(
        public readonly string $resourceClass,
        public readonly string $modelClass,
        public readonly Collection $fields,
        public readonly array $skippedFields = [],
    ) {}

    public function singularName(): string
    {
        return Str::snake(class_basename($this->modelClass));
    }

    public function pluralName(): string
    {
        return Str::plural($this->singularName());
    }

    public function field(string $name): ?FieldDefinition
    {
        return $this->fields->first(fn (FieldDefinition $field): bool => $field->name === $name);
    }
}

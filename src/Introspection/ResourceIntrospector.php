<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Mattiasgeniar\FilamentMcp\Support\SchemaContainer;

class ResourceIntrospector
{
    public function __construct(
        private readonly FieldMapper $mapper = new FieldMapper,
        private readonly InfolistIntrospector $infolist = new InfolistIntrospector,
    ) {}

    /**
     * @param  class-string  $resourceClass
     * @param  array<int, string>  $readFields  Explicit readable field override (e.g. for view-only resources whose schema lives on a page).
     */
    public function for(string $resourceClass, array $readFields = []): ResourceSchema
    {
        $schema = $resourceClass::form(Schema::make(SchemaContainer::make()));

        $fields = collect();
        $skipped = [];

        $this->walk($schema->getComponents(), $fields, $skipped);

        return new ResourceSchema(
            resourceClass: $resourceClass,
            modelClass: $resourceClass::getModel(),
            fields: $fields,
            readableFields: $this->readableFields($resourceClass, $fields, $readFields),
            skippedFields: $skipped,
        );
    }

    /**
     * Readable fields come from, in order: an explicit config override, the
     * resource's infolist (what Filament shows on the view page), then the form
     * fields. So editable, view-only, and page-driven resources all expose
     * something useful.
     *
     * @param  class-string  $resourceClass
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<int, string>  $readFields
     * @return Collection<int, ReadableField>
     */
    private function readableFields(string $resourceClass, Collection $fields, array $readFields): Collection
    {
        if ($readFields !== []) {
            return collect($readFields)->map(fn (string $name): ReadableField => new ReadableField($name));
        }

        $fromInfolist = $this->infolist->for($resourceClass);

        if ($fromInfolist->isNotEmpty()) {
            return $fromInfolist;
        }

        return $fields->map(fn (FieldDefinition $field): ReadableField => new ReadableField($field->name, $field->description));
    }

    /**
     * @param  array<int, Component>  $components
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<int, string>  $skipped
     */
    private function walk(array $components, Collection $fields, array &$skipped): void
    {
        foreach ($components as $component) {
            if ($component instanceof Field) {
                $definition = $this->mapper->map($component);

                if ($definition === null) {
                    $skipped[] = $component->getName();

                    continue;
                }

                $fields->push($definition);

                continue;
            }

            $this->walk($component->getChildComponents(), $fields, $skipped);
        }
    }
}

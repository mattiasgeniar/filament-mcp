<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
     * Readable fields are the union of the resource's infolist (what Filament
     * shows on the view page) and its writable form fields, so an agent can
     * always read back what it can write and still see view-only entries. The
     * infolist wins on name clashes, keeping its label. An explicit `read_fields`
     * config override replaces both. Fields the model marks `$hidden` are dropped
     * later, at read time.
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

        $fromForm = $fields->map(fn (FieldDefinition $field): ReadableField => new ReadableField($field->name, $field->description));

        return $this->infolist->for($resourceClass)
            ->concat($fromForm)
            ->unique(fn (ReadableField $field): string => $field->name)
            ->values();
    }

    /**
     * @param  array<int, Component|Action|ActionGroup>  $components
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

            if (! $component instanceof Component) {
                continue;
            }

            $this->walk($component->getChildComponents(), $fields, $skipped);
        }
    }
}

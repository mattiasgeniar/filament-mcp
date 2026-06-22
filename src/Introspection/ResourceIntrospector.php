<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mattiasgeniar\FilamentMcp\Support\ModelAttributeVisibility;
use Mattiasgeniar\FilamentMcp\Support\SchemaContainer;

class ResourceIntrospector
{
    public function __construct(
        private readonly FieldMapper $mapper = new FieldMapper,
        private readonly InfolistIntrospector $infolist = new InfolistIntrospector,
    ) {}

    /**
     * @param  class-string<FilamentResource>  $resourceClass
     * @param  array<int, string>  $readFields  Explicit readable field override (e.g. for view-only resources whose schema lives on a page).
     */
    public function for(string $resourceClass, array $readFields = []): ResourceSchema
    {
        $schema = $resourceClass::form(Schema::make(SchemaContainer::make()));

        $fields = collect();
        $skipped = [];

        $this->walk($schema->getComponents(), $fields, $skipped);

        $modelClass = $resourceClass::getModel();

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Filament MCP resources must point to an Eloquent model.');
        }

        $model = new $modelClass;
        $fields = $this->fieldDefinitionsAllowedByModelVisibility($fields, $model);
        $readableFields = $this->readableFieldsAllowedByModelVisibility(
            $this->readableFields($resourceClass, $fields, $readFields),
            $model,
        );

        return new ResourceSchema(
            resourceClass: $resourceClass,
            modelClass: $modelClass,
            fields: $fields,
            readableFields: $readableFields,
            skippedFields: $skipped,
        );
    }

    /**
     * Readable fields are the union of the resource's infolist (what Filament
     * shows on the view page) and its writable form fields, so an agent can
     * always read back what it can write and still see view-only entries. The
     * infolist wins on name clashes, keeping its label. An explicit `read_fields`
     * config override replaces both. Model `$hidden` / `$visible` settings are
     * applied before the schema is returned, so hidden attributes are never
     * exposed to discovery or list query controls.
     *
     * @param  class-string<FilamentResource>  $resourceClass
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
     * @param  Collection<int, FieldDefinition>  $fields
     * @return Collection<int, FieldDefinition>
     */
    private function fieldDefinitionsAllowedByModelVisibility(Collection $fields, Model $model): Collection
    {
        return $fields
            ->filter(fn (FieldDefinition $field): bool => ModelAttributeVisibility::allows($model, $field->name))
            ->values();
    }

    /**
     * @param  Collection<int, ReadableField>  $fields
     * @return Collection<int, ReadableField>
     */
    private function readableFieldsAllowedByModelVisibility(Collection $fields, Model $model): Collection
    {
        return $fields
            ->filter(fn (ReadableField $field): bool => ModelAttributeVisibility::allows($model, $field->name))
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

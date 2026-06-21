<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Component as LivewireComponent;

class ResourceIntrospector
{
    public function __construct(
        private readonly FieldMapper $mapper = new FieldMapper,
    ) {}

    /**
     * @param  class-string  $resourceClass
     */
    public function for(string $resourceClass): ResourceSchema
    {
        $schema = $resourceClass::form(Schema::make($this->container()));

        $fields = collect();
        $skipped = [];

        $this->walk($schema->getComponents(), $fields, $skipped);

        return new ResourceSchema(
            resourceClass: $resourceClass,
            modelClass: $resourceClass::getModel(),
            fields: $fields,
            skippedFields: $skipped,
        );
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

    private function container(): HasSchemas & LivewireComponent
    {
        return new class extends LivewireComponent implements HasSchemas
        {
            use InteractsWithSchemas;

            public function render(): string
            {
                return '';
            }
        };
    }
}

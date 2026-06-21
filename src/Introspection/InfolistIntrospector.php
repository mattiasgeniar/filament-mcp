<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Mattiasgeniar\FilamentMcp\Support\SchemaContainer;
use Throwable;

class InfolistIntrospector
{
    /**
     * The infolist entry types that do not map to a plain readable attribute.
     *
     * @var array<int, class-string>
     */
    protected array $skippedEntries = [
        ImageEntry::class,
        RepeatableEntry::class,
        ViewEntry::class,
    ];

    /**
     * Build the list of readable fields from a resource's infolist. Returns an
     * empty collection when the resource defines no infolist entries.
     *
     * @param  class-string  $resourceClass
     * @return Collection<int, ReadableField>
     */
    public function for(string $resourceClass): Collection
    {
        $schema = $resourceClass::infolist(Schema::make(SchemaContainer::make()));

        $fields = collect();

        $this->walk($schema->getComponents(), $fields);

        return $fields;
    }

    /**
     * @param  array<int, Component|Action|ActionGroup>  $components
     * @param  Collection<int, ReadableField>  $fields
     */
    private function walk(array $components, Collection $fields): void
    {
        foreach ($components as $component) {
            if ($component instanceof Entry) {
                $field = $this->map($component);

                if ($field !== null) {
                    $fields->push($field);
                }

                continue;
            }

            if (! $component instanceof Component) {
                continue;
            }

            $this->walk($component->getChildComponents(), $fields);
        }
    }

    private function map(Entry $entry): ?ReadableField
    {
        foreach ($this->skippedEntries as $skipped) {
            if ($entry instanceof $skipped) {
                return null;
            }
        }

        try {
            $label = $entry->getLabel();

            return new ReadableField(
                name: $entry->getName(),
                description: is_string($label) && $label !== '' ? $label : null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}

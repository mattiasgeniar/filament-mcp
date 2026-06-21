<?php

namespace Mattiasgeniar\FilamentMcp\Support;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component as LivewireComponent;

class SchemaContainer
{
    /**
     * A throwaway Livewire component that can host a Filament schema, so a
     * resource's form() or infolist() can be built headlessly for introspection.
     */
    public static function make(): HasSchemas & LivewireComponent
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

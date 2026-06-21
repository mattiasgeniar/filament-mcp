<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Report;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('summary'),
            ImageEntry::make('cover'),
        ]);
    }

    public static function getPages(): array
    {
        return [];
    }
}

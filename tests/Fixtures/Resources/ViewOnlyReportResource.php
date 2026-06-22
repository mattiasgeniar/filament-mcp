<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Report;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ViewOnlyReportResource\Pages;

class ViewOnlyReportResource extends Resource
{
    protected static ?string $model = Report::class;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('summary'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }
}

<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Project;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\TeamProjectResource\Pages;

class TeamProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('team_id')->relationship('team', 'name'),
            TextInput::make('name')->visible(fn (Get $get): bool => $get('team_id') === null),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamProjects::route('/'),
        ];
    }
}

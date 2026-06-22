<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Project;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProjectResource\Pages;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
        ];
    }
}

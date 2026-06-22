<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Profile;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProfileResource\Pages;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('bio'),
            TextInput::make('secret_token'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
        ];
    }
}

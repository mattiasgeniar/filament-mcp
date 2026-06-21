<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(160),
            TextInput::make('slug'),
            MarkdownEditor::make('body')->required(),
            Select::make('status')->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ]),
            Toggle::make('published'),
            DateTimePicker::make('published_at'),
            TextInput::make('views')->integer(),
            TextInput::make('rating')->numeric(),
            FileUpload::make('cover'),
            TextInput::make('internal_ref')->disabled(),
            TextInput::make('computed_value')->dehydrated(false),
        ]);
    }

    public static function getPages(): array
    {
        return [];
    }
}

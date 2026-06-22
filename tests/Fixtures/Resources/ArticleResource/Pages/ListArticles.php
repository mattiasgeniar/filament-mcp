<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;
}

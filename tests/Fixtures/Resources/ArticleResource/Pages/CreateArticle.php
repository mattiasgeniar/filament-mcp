<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
}

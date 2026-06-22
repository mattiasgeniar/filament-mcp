<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;
}

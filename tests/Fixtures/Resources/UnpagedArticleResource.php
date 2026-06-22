<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources;

class UnpagedArticleResource extends ArticleResource
{
    public static function getPages(): array
    {
        return [];
    }
}

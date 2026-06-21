<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mattiasgeniar\FilamentMcp\Actions\ResourceAction;

class PublishArticle extends ResourceAction
{
    public function description(): string
    {
        return 'Publish the article.';
    }

    public function handle(Model $record, array $arguments): mixed
    {
        $record->update(['published' => true]);

        return ['id' => $record->getKey(), 'published' => true];
    }
}

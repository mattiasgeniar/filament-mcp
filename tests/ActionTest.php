<?php

use Mattiasgeniar\FilamentMcp\Server\ToolFactory;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\PublishArticle;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

beforeEach(function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['actions' => ['publish' => PublishArticle::class]],
    ]]);

    actingAsMcpUser();
});

it('generates a tool for a configured action', function () {
    $names = collect(app(ToolFactory::class)->make())->map(fn ($tool) => $tool->name())->all();

    expect($names)->toContain('publish_article');
});

it('runs the action against the resolved record', function () {
    $article = Article::query()->create(['title' => 'Draft', 'body' => 'b', 'published' => false]);

    $result = callMcpTool('publish_article', ['id' => $article->id]);

    expect($result['success'])->toBeTrue();
    expect($result['result']['published'])->toBeTrue();
    expect(Article::find($article->id)->published)->toBeTrue();
});

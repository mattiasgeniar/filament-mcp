<?php

use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Mattiasgeniar\FilamentMcp\Server\ToolFactory;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\EchoArguments;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Policies\DenyUpdateArticlePolicy;
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

it('refuses the action when the user fails the resource policy', function () {
    Gate::policy(Article::class, DenyUpdateArticlePolicy::class);

    $article = Article::query()->create(['title' => 'Draft', 'body' => 'b', 'published' => false]);

    $response = mcpTool('publish_article')->handle(new Request(['id' => $article->id]));

    expect($response->isError())->toBeTrue();
    expect(Article::find($article->id)->published)->toBeFalse();
});

it('drops arguments the action did not declare so they cannot reach the handler', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['actions' => ['echo' => EchoArguments::class]],
    ]]);

    $article = Article::query()->create(['title' => 'Draft', 'body' => 'b', 'published' => false]);

    $result = callMcpTool('echo_article', ['id' => $article->id, 'note' => 'keep', 'is_admin' => true]);

    expect($result['result']['arguments'])->toBe(['note' => 'keep']);
});

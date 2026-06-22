<?php

use Mattiasgeniar\FilamentMcp\Tests\Fixtures\PublishArticle;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

it('describes the exposed resources, operations, and fields', function () {
    $result = callMcpTool('describe_resources', []);

    $article = collect($result['resources'])->firstWhere('resource', 'article');

    expect($article)->not->toBeNull();
    expect($article['operations'])->toBe([
        'read' => true,
        'create' => true,
        'update' => true,
        'delete' => false,
    ]);
    expect($article['writable_fields'])->toContain('title', 'body', 'status');
    expect($article['readable_fields'])->toContain('title');
});

it('reflects disabled operations and custom actions', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => [
            'write' => false,
            'actions' => ['publish' => PublishArticle::class],
        ],
    ]]);

    $result = callMcpTool('describe_resources', []);
    $article = collect($result['resources'])->firstWhere('resource', 'article');

    expect($article['operations']['create'])->toBeFalse();
    expect($article['operations']['delete'])->toBeFalse();
    expect($article['actions'])->toBe(['publish']);
});

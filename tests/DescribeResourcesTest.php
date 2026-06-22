<?php

use Mattiasgeniar\FilamentMcp\Tests\Fixtures\PublishArticle;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ViewOnlyReportResource;

it('describes the exposed resources, operations, and fields', function () {
    $result = callMcpTool('describe_resources', []);

    $article = collect($result['resources'])->firstWhere('resource', 'article');

    expect($article)->not->toBeNull();
    expect($article['operations'])->toBe([
        'read' => true,
        'list' => true,
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

it('reports a view-only resource as readable but not listable', function () {
    config(['filament-mcp.resources' => [ViewOnlyReportResource::class]]);

    $result = callMcpTool('describe_resources', []);
    $report = collect($result['resources'])->firstWhere('resource', 'report');

    expect($report['operations']['read'])->toBeTrue();
    expect($report['operations']['list'])->toBeFalse();
    expect($report['operations']['create'])->toBeFalse();
    expect($report['operations']['update'])->toBeFalse();
});

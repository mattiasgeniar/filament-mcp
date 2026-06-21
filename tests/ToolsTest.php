<?php

use Illuminate\Validation\ValidationException;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;
use Mattiasgeniar\FilamentMcp\Server\ToolFactory;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\UppercasesTitle;

beforeEach(function () {
    actingAsMcpUser();
});

it('generates the expected tool set for a resource', function () {
    $names = collect(app(ToolFactory::class)->make())
        ->map(fn ($tool) => $tool->name())
        ->all();

    expect($names)->toContain(
        'list_articles',
        'get_article',
        'create_article',
        'update_article',
        'delete_article',
    );
});

it('runs a full create, read, update, delete round trip', function () {
    $created = callMcpTool('create_article', [
        'title' => 'Hello World',
        'body' => 'Some body.',
        'status' => 'draft',
    ]);

    expect($created['success'])->toBeTrue();
    $id = $created['record']['id'];

    $fetched = callMcpTool('get_article', ['id' => $id]);
    expect($fetched['title'])->toBe('Hello World');

    $updated = callMcpTool('update_article', ['id' => $id, 'title' => 'Renamed']);
    expect($updated['record']['title'])->toBe('Renamed');
    expect(Article::find($id)->title)->toBe('Renamed');

    $deleted = callMcpTool('delete_article', ['id' => $id]);
    expect($deleted['success'])->toBeTrue();
    expect(Article::find($id))->toBeNull();
});

it('fires model events on create so the slug is generated', function () {
    $created = callMcpTool('create_article', [
        'title' => 'My First Article',
        'body' => 'Body text.',
    ]);

    expect(Article::find($created['record']['id'])->slug)->toBe('my-first-article');
});

it('rejects a create that is missing a required field', function () {
    expect(fn () => callMcpTool('create_article', ['body' => 'No title']))
        ->toThrow(ValidationException::class);

    expect(Article::query()->count())->toBe(0);
});

it('rejects an enum value outside the allowed options', function () {
    expect(fn () => callMcpTool('create_article', [
        'title' => 'Bad status',
        'body' => 'Body.',
        'status' => 'archived',
    ]))->toThrow(ValidationException::class);
});

it('applies a configured data preparer before saving', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['prepare' => UppercasesTitle::class],
    ]]);

    $created = callMcpTool('create_article', [
        'title' => 'lowercase',
        'body' => 'Body.',
    ]);

    expect($created['record']['title'])->toBe('LOWERCASE');
});

it('records every tool call in the audit log', function () {
    callMcpTool('create_article', ['title' => 'Audited', 'body' => 'Body.']);

    $call = FilamentMcpToolCall::query()->latest('id')->first();

    expect($call->tool_name)->toBe('create_article');
    expect($call->success)->toBeTrue();
    expect($call->user_id)->not->toBeNull();
});

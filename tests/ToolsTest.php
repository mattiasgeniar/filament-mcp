<?php

use Illuminate\Validation\ValidationException;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;
use Mattiasgeniar\FilamentMcp\Server\ToolFactory;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\UnpagedArticleResource;
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
    );
    expect($names)->not->toContain('delete_article');
});

it('runs a full create, read, update, delete round trip', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['delete' => true],
    ]]);

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

it('requires an explicit opt-in for delete tools', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['delete' => true],
    ]]);

    $names = collect(app(ToolFactory::class)->make())
        ->map(fn ($tool) => $tool->name())
        ->all();

    expect($names)->toContain('delete_article');
});

it('does not generate tools for resources without matching Filament pages', function () {
    config(['filament-mcp.resources' => [
        UnpagedArticleResource::class => ['delete' => true],
    ]]);

    $names = collect(app(ToolFactory::class)->make())
        ->map(fn ($tool) => $tool->name())
        ->all();

    expect($names)->not->toContain(
        'list_articles',
        'get_article',
        'create_article',
        'update_article',
        'delete_article',
    );
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

it('does not generate a delete tool when write is disabled', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['write' => false],
    ]]);

    $names = collect(app(ToolFactory::class)->make())->map(fn ($tool) => $tool->name())->all();

    expect($names)->toContain('list_articles', 'get_article');
    expect($names)->not->toContain('create_article', 'update_article', 'delete_article');
});

it('reflects database defaults in the created record by refreshing it', function () {
    $result = callMcpTool('create_article', ['title' => 'Defaults', 'body' => 'b', 'status' => 'draft']);

    expect($result['record']['views'])->toBe(0);
    expect($result['record']['published'])->toBeFalse();
});

it('accepts a string id so UUID-keyed models work', function () {
    $created = callMcpTool('create_article', ['title' => 'String id', 'body' => 'Body.']);

    $fetched = callMcpTool('get_article', ['id' => (string) $created['record']['id']]);

    expect($fetched['title'])->toBe('String id');
});

it('records a failed tool call as unsuccessful in the audit log', function () {
    try {
        callMcpTool('create_article', ['body' => 'No title']);
    } catch (ValidationException) {
        // expected
    }

    expect(FilamentMcpToolCall::query()->latest('id')->first()->success)->toBeFalse();
});

it('records every tool call in the audit log', function () {
    callMcpTool('create_article', ['title' => 'Audited', 'body' => 'Body.']);

    $call = FilamentMcpToolCall::query()->latest('id')->first();

    expect($call->tool_name)->toBe('create_article');
    expect($call->success)->toBeTrue();
    expect($call->user_id)->not->toBeNull();
});

it('records the token that authenticated each tool call', function () {
    ['token' => $token] = actingAsMcpToken();

    callMcpTool('create_article', ['title' => 'Audited', 'body' => 'Body.']);

    expect(FilamentMcpToolCall::query()->latest('id')->first()->filament_mcp_token_id)
        ->toBe($token->getKey());
});

it('leaves the token null when no token authenticated the call', function () {
    callMcpTool('create_article', ['title' => 'Audited', 'body' => 'Body.']);

    expect(FilamentMcpToolCall::query()->latest('id')->first()->filament_mcp_token_id)
        ->toBeNull();
});

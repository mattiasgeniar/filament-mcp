<?php

use Illuminate\Validation\ValidationException;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Profile;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProfileResource;

beforeEach(function () {
    actingAsMcpUser();

    Article::query()->create(['title' => 'Alpha', 'body' => 'a', 'status' => 'draft']);
    Article::query()->create(['title' => 'Beta', 'body' => 'b', 'status' => 'published']);
    Article::query()->create(['title' => 'Gamma', 'body' => 'c', 'status' => 'published']);
});

it('lists all records with pagination metadata', function () {
    $result = callMcpTool('list_articles', []);

    expect($result['total'])->toBe(3);
    expect($result['page'])->toBe(1);
    expect($result['records'])->toHaveCount(3);
});

it('searches across readable fields', function () {
    $result = callMcpTool('list_articles', ['search' => 'lph']);

    expect($result['total'])->toBe(1);
    expect($result['records'][0]['title'])->toBe('Alpha');
});

it('filters by an exact field value', function () {
    $result = callMcpTool('list_articles', ['filters' => ['status' => 'published']]);

    expect($result['total'])->toBe(2);
});

it('sorts by a field in the requested direction', function () {
    $result = callMcpTool('list_articles', ['sort' => 'title', 'direction' => 'asc']);

    expect($result['records'][0]['title'])->toBe('Alpha');
    expect($result['records'][2]['title'])->toBe('Gamma');
});

it('paginates the result set', function () {
    $result = callMcpTool('list_articles', ['per_page' => 2, 'page' => 2]);

    expect($result['total'])->toBe(3);
    expect($result['per_page'])->toBe(2);
    expect($result['records'])->toHaveCount(1);
});

it('rejects sorting by a column that is not a readable field', function () {
    expect(fn () => callMcpTool('list_articles', ['sort' => 'password']))
        ->toThrow(ValidationException::class);
});

it('does not search, filter, or sort by model hidden attributes', function () {
    config(['filament-mcp.resources' => [ProfileResource::class]]);

    Profile::query()->create(['name' => 'Ada', 'bio' => 'Engineer', 'secret_token' => 'sk_hidden_ada']);
    Profile::query()->create(['name' => 'Grace', 'bio' => 'Scientist', 'secret_token' => 'sk_hidden_grace']);

    $searched = callMcpTool('list_profiles', ['search' => 'sk_hidden_ada']);
    $filtered = callMcpTool('list_profiles', ['filters' => ['secret_token' => 'sk_hidden_ada']]);

    expect($searched['total'])->toBe(0);
    expect($filtered['total'])->toBe(2);
    expect(fn () => callMcpTool('list_profiles', ['sort' => 'secret_token']))
        ->toThrow(ValidationException::class);
});

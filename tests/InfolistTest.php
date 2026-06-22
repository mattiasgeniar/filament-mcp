<?php

use Mattiasgeniar\FilamentMcp\Introspection\InfolistIntrospector;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceIntrospector;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Profile;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Report;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProfileResource;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ReportResource;

it('reads field names from a resource infolist, skipping non-scalar entries', function () {
    $fields = (new InfolistIntrospector)->for(ReportResource::class);

    expect($fields->map(fn ($field) => $field->name)->all())->toBe(['title', 'summary']);
});

it('uses the infolist for readable fields on a view-only resource', function () {
    $schema = (new ResourceIntrospector)->for(ReportResource::class);

    expect($schema->fields)->toBeEmpty();
    expect($schema->readableFields->map(fn ($field) => $field->name)->all())->toBe(['title', 'summary']);
});

it('uses the writable form fields for readable fields when there is no infolist', function () {
    $schema = (new ResourceIntrospector)->for(ArticleResource::class);

    expect($schema->readableFields->map(fn ($field) => $field->name)->all())
        ->toBe($schema->fields->map(fn ($field) => $field->name)->all());
});

it('unions the infolist and writable form fields for readable fields', function () {
    $schema = (new ResourceIntrospector)->for(ProfileResource::class);

    expect($schema->readableFields->map(fn ($field) => $field->name)->all())
        ->toBe(['name', 'bio']);

    expect($schema->fields->map(fn ($field) => $field->name)->all())
        ->toBe(['name', 'bio']);
});

it('drops $hidden attributes from read output and discovery', function () {
    config(['filament-mcp.resources' => [ProfileResource::class]]);
    actingAsMcpUser();

    $profile = Profile::query()->create(['name' => 'Ada', 'bio' => 'Engineer', 'secret_token' => 'sk_live_123']);

    $fetched = callMcpTool('get_profile', ['id' => $profile->id]);

    expect($fetched['name'])->toBe('Ada');
    expect($fetched['bio'])->toBe('Engineer');
    expect($fetched)->not->toHaveKey('secret_token');

    $resources = callMcpTool('describe_resources', [])['resources'];
    $profileResource = collect($resources)->firstWhere('resource', 'profile');

    expect($profileResource['readable_fields'])->not->toContain('secret_token');
    expect($profileResource['writable_fields'])->not->toContain('secret_token');
});

it('does not let read_fields override model hidden attributes', function () {
    $schema = (new ResourceIntrospector)->for(ProfileResource::class, ['name', 'secret_token']);

    expect($schema->readableFields->map(fn ($field) => $field->name)->all())->toBe(['name']);
});

it('lets config override the readable fields explicitly', function () {
    $schema = (new ResourceIntrospector)->for(ArticleResource::class, ['title', 'status']);

    expect($schema->readableFields->map(fn ($field) => $field->name)->all())->toBe(['title', 'status']);
});

it('exposes only the configured read_fields through the get tool', function () {
    config(['filament-mcp.resources' => [
        ArticleResource::class => ['read_fields' => ['title']],
    ]]);
    actingAsMcpUser();

    $article = Article::query()->create(['title' => 'Visible', 'body' => 'hidden', 'status' => 'draft']);

    $fetched = callMcpTool('get_article', ['id' => $article->id]);

    expect($fetched)->toHaveKey('title');
    expect($fetched)->not->toHaveKey('body');
    expect($fetched)->not->toHaveKey('status');
});

it('exposes infolist fields through the get tool on a view-only resource', function () {
    config(['filament-mcp.resources' => [ReportResource::class]]);
    actingAsMcpUser();

    $report = Report::query()->create(['title' => 'Quarterly', 'summary' => 'All good.']);

    $fetched = callMcpTool('get_report', ['id' => $report->id]);

    expect($fetched['title'])->toBe('Quarterly');
    expect($fetched['summary'])->toBe('All good.');
    expect($fetched)->not->toHaveKey('cover');
});

<?php

use Mattiasgeniar\FilamentMcp\Introspection\InfolistIntrospector;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceIntrospector;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Report;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;
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

it('falls back to form fields for readable fields when there is no infolist', function () {
    $schema = (new ResourceIntrospector)->for(ArticleResource::class);

    expect($schema->readableFields->map(fn ($field) => $field->name)->all())
        ->toBe($schema->fields->map(fn ($field) => $field->name)->all());
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

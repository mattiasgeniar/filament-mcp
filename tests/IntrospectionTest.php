<?php

use Mattiasgeniar\FilamentMcp\Introspection\FieldType;
use Mattiasgeniar\FilamentMcp\Introspection\ResourceIntrospector;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ArticleResource;

it('maps a resource form to typed scalar fields', function () {
    $schema = (new ResourceIntrospector)->for(ArticleResource::class);

    expect($schema->singularName())->toBe('article');
    expect($schema->pluralName())->toBe('articles');
    expect($schema->fields->map(fn ($field) => $field->name)->all())
        ->toBe(['title', 'slug', 'body', 'status', 'published', 'published_at']);

    expect($schema->field('title')->type)->toBe(FieldType::String);
    expect($schema->field('title')->required)->toBeTrue();
    expect($schema->field('published')->type)->toBe(FieldType::Boolean);
    expect($schema->field('published_at')->type)->toBe(FieldType::Date);
    expect($schema->field('status')->type)->toBe(FieldType::Enum);
    expect($schema->field('status')->enumOptions)->toBe(['draft', 'published']);
});

it('skips file upload and unsupported components', function () {
    $schema = (new ResourceIntrospector)->for(ArticleResource::class);

    expect($schema->fields->map(fn ($field) => $field->name)->all())->not->toContain('cover');
    expect($schema->skippedFields)->toContain('cover');
});

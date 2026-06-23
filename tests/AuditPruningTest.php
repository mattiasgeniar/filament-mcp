<?php

use Illuminate\Support\Carbon;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;

function recordCallAt(Carbon $createdAt): FilamentMcpToolCall
{
    return FilamentMcpToolCall::query()->create([
        'tool_name' => 'list_articles',
        'created_at' => $createdAt,
    ]);
}

it('prunes tool calls older than the retention window', function () {
    config()->set('filament-mcp.audit.retention_days', 365);

    $stale = recordCallAt(now()->subDays(366));
    $fresh = recordCallAt(now()->subDays(364));

    $this->artisan('model:prune', ['--model' => [FilamentMcpToolCall::class]]);

    expect(FilamentMcpToolCall::query()->pluck('id')->all())
        ->toEqual([$fresh->id])
        ->not->toContain($stale->id);
});

it('honours a custom retention window', function () {
    config()->set('filament-mcp.audit.retention_days', 30);

    recordCallAt(now()->subDays(31));
    $fresh = recordCallAt(now()->subDays(29));

    $this->artisan('model:prune', ['--model' => [FilamentMcpToolCall::class]]);

    expect(FilamentMcpToolCall::query()->pluck('id')->all())->toEqual([$fresh->id]);
});

it('keeps every record when retention is disabled', function () {
    config()->set('filament-mcp.audit.retention_days', 0);

    recordCallAt(now()->subYears(5));

    $this->artisan('model:prune', ['--model' => [FilamentMcpToolCall::class]]);

    expect(FilamentMcpToolCall::query()->count())->toBe(1);
});

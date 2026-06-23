<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the audit prune command to run daily', function () {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command ?? '', 'model:prune')
            && str_contains($event->command ?? '', 'FilamentMcpToolCall'));

    expect($prune)->not->toBeNull();
    expect($prune->expression)->toBe('0 0 * * *');
});

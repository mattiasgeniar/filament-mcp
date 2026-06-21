<?php

namespace Mattiasgeniar\FilamentMcp\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Mattiasgeniar\FilamentMcp\Filament\Pages\ManageMcpTokens;

class FilamentMcpPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-mcp';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            ManageMcpTokens::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}

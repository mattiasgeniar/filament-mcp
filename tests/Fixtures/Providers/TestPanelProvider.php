<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Mattiasgeniar\FilamentMcp\Filament\FilamentMcpPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentMcpPlugin::make());
    }
}

<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Team;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProjectResource;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            ->tenant(Team::class)
            ->resources([
                ProjectResource::class,
            ]);
    }
}

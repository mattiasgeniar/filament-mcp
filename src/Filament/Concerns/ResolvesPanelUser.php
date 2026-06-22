<?php

namespace Mattiasgeniar\FilamentMcp\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

trait ResolvesPanelUser
{
    protected function currentUser(): Authenticatable
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof Authenticatable, 403);

        return $user;
    }
}

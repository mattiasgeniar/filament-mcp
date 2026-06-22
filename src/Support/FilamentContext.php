<?php

namespace Mattiasgeniar\FilamentMcp\Support;

use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;

class FilamentContext
{
    public function initialize(Request $request, Authenticatable $user): ?string
    {
        $panel = $this->panel();

        if (is_string($panel)) {
            return $panel;
        }

        if ($panel === null) {
            return null;
        }

        Filament::setCurrentPanel($panel);
        Filament::bootCurrentPanel();

        $panel->auth()->setUser($user);

        if (! $panel->hasTenancy()) {
            Filament::setTenant(null, isQuiet: true);

            return null;
        }

        $tenantKey = $request->header($this->tenantHeader());

        if (! is_string($tenantKey) || $tenantKey === '') {
            return "Tenant required. Send the [{$this->tenantHeader()}] header with the Filament tenant route key.";
        }

        if (! $user instanceof HasTenants) {
            return 'The token user cannot access Filament tenants.';
        }

        try {
            $tenant = $panel->getTenant($tenantKey);
        } catch (ModelNotFoundException) {
            return 'The requested Filament tenant was not found.';
        }

        if (! $user->canAccessTenant($tenant)) {
            return 'You are not allowed to access the requested Filament tenant.';
        }

        Filament::setTenant($tenant);

        return null;
    }

    private function panel(): Panel | string | null
    {
        $panelId = config('filament-mcp.panel');

        if (is_string($panelId) && $panelId !== '') {
            $panels = Filament::getPanels();

            if (! array_key_exists($panelId, $panels)) {
                return "Configured Filament MCP panel [{$panelId}] was not found.";
            }

            return $panels[$panelId];
        }

        try {
            return Filament::getCurrentOrDefaultPanel();
        } catch (Throwable) {
            return null;
        }
    }

    private function tenantHeader(): string
    {
        return (string) config('filament-mcp.tenant_header', 'X-Filament-Mcp-Tenant');
    }
}

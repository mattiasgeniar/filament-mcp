<?php

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Mattiasgeniar\FilamentMcp\Filament\Pages\ManageMcpTokens;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;

/*
 * The page's Livewire rendering is exercised end-to-end against a real Filament
 * panel; Livewire 4 + Orchestra Testbench cannot render any validation component
 * here (it fails for a bare Livewire component too), so these cover the page's
 * security-critical logic render-free: who may reach it and what it queries.
 */

function tokenPage(): ManageMcpTokens
{
    return new class extends ManageMcpTokens
    {
        /** @return Builder<FilamentMcpToken> */
        public function exposeQuery(): Builder
        {
            return $this->ownTokensQuery();
        }
    };
}

beforeEach(function () {
    FilamentMcp::authorizeUsing(fn ($user) => (bool) $user->is_admin);
    Filament::setCurrentPanel('admin');
});

it('grants access to an authorized user', function () {
    $this->actingAs(makeUser(isAdmin: true));

    expect(ManageMcpTokens::canAccess())->toBeTrue();
});

it('hides the page from a user the authorization gate rejects', function () {
    $this->actingAs(makeUser(isAdmin: false));

    expect(ManageMcpTokens::canAccess())->toBeFalse();
});

it('hides the page when the ui is disabled', function () {
    config(['filament-mcp.ui.enabled' => false]);
    $this->actingAs(makeUser(isAdmin: true));

    expect(ManageMcpTokens::canAccess())->toBeFalse();
});

it('scopes the token table to the current user', function () {
    $user = makeUser();
    $other = makeUser();

    ['token' => $mine] = FilamentMcpToken::issue($user, 'Mine');
    FilamentMcpToken::issue($other, 'Theirs');

    $this->actingAs($user);

    expect(tokenPage()->exposeQuery()->pluck('id')->all())->toBe([$mine->id]);
});

it('revokes a token by stamping revoked_at', function () {
    ['token' => $token] = FilamentMcpToken::issue(makeUser(), 'Laptop');

    $token->revoke();

    expect($token->fresh()->revoked_at)->not->toBeNull();
});

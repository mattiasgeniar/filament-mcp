<?php

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mattiasgeniar\FilamentMcp\Filament\Pages\TokenActivity;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;

/*
 * The page's Livewire rendering cannot run under Testbench (see ManageMcpTokensTest),
 * so these cover the security-critical logic render-free: which token is mounted,
 * who may mount it, and what the table queries.
 */

function activityPage(): TokenActivity
{
    return new class extends TokenActivity
    {
        /** @return Builder<FilamentMcpToolCall> */
        public function exposeQuery(): Builder
        {
            return $this->toolCallsQuery();
        }
    };
}

function recordCall(FilamentMcpToken $token, string $name, $createdAt): FilamentMcpToolCall
{
    return FilamentMcpToolCall::query()->create([
        'user_id' => $token->tokenable_id,
        'filament_mcp_token_id' => $token->id,
        'tool_name' => $name,
        'created_at' => $createdAt,
    ]);
}

beforeEach(function () {
    FilamentMcp::authorizeUsing(fn ($user) => (bool) $user->is_admin);
    Filament::setCurrentPanel('admin');
});

it('grants access to an authorized user', function () {
    $this->actingAs(makeUser(isAdmin: true));

    expect(TokenActivity::canAccess())->toBeTrue();
});

it('hides the page from a user the authorization gate rejects', function () {
    $this->actingAs(makeUser(isAdmin: false));

    expect(TokenActivity::canAccess())->toBeFalse();
});

it('lists the token\'s tool calls newest first', function () {
    $user = makeUser();
    ['token' => $token] = FilamentMcpToken::issue($user, 'Mine');

    recordCall($token, 'older_call', now()->subMinute());
    recordCall($token, 'newer_call', now());

    $this->actingAs($user);

    $page = activityPage();
    $page->mount($token->id);

    expect($page->exposeQuery()->pluck('tool_name')->all())
        ->toBe(['newer_call', 'older_call']);
});

it('excludes tool calls made with a different token', function () {
    $user = makeUser();
    ['token' => $token] = FilamentMcpToken::issue($user, 'Mine');
    ['token' => $otherToken] = FilamentMcpToken::issue($user, 'Other');

    recordCall($otherToken, 'other_token_call', now());

    $this->actingAs($user);

    $page = activityPage();
    $page->mount($token->id);

    expect($page->exposeQuery()->get())->toBeEmpty();
});

it('refuses to mount another user\'s token', function () {
    $other = makeUser();
    ['token' => $theirs] = FilamentMcpToken::issue($other, 'Theirs');

    $this->actingAs(makeUser());

    activityPage()->mount($theirs->id);
})->throws(ModelNotFoundException::class);

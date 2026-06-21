<?php

use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;

it('issues a hashed, prefixed token and resolves it from the plaintext', function () {
    $user = makeUser();

    ['token' => $token, 'plainText' => $plainText] = FilamentMcpToken::issue($user, 'Laptop');

    expect($plainText)->toStartWith('fmcp_');
    expect($token->token)->toBe(hash('sha256', $plainText));
    expect(FilamentMcpToken::findByPlainText($plainText)?->is($token))->toBeTrue();
});

it('does not resolve a revoked token', function () {
    $user = makeUser();

    ['token' => $token, 'plainText' => $plainText] = FilamentMcpToken::issue($user, 'Laptop');
    $token->update(['revoked_at' => now()]);

    expect(FilamentMcpToken::findByPlainText($plainText))->toBeNull();
});

it('does not resolve a token without the configured prefix', function () {
    expect(FilamentMcpToken::findByPlainText('nope_123'))->toBeNull();
});

it('issues a token via the command for an authorized user', function () {
    FilamentMcp::authorizeUsing(fn ($user) => (bool) $user->is_admin);
    $user = makeUser(isAdmin: true);

    $this->artisan('filament-mcp:token', ['user' => (string) $user->getKey()])
        ->assertSuccessful();

    expect(FilamentMcpToken::query()->count())->toBe(1);
});

it('refuses to issue a token for an unauthorized user', function () {
    FilamentMcp::authorizeUsing(fn ($user) => (bool) $user->is_admin);
    $user = makeUser(isAdmin: false);

    $this->artisan('filament-mcp:token', ['user' => (string) $user->getKey()])
        ->assertFailed();

    expect(FilamentMcpToken::query()->count())->toBe(0);
});

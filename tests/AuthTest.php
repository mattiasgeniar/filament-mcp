<?php

use Illuminate\Testing\TestResponse;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;

function postMcp(?string $token): TestResponse
{
    $headers = ['Accept' => 'application/json, text/event-stream'];

    if ($token !== null) {
        $headers['Authorization'] = "Bearer {$token}";
    }

    return test()->postJson('/filament-mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], $headers);
}

beforeEach(function () {
    FilamentMcp::authorizeUsing(fn ($user) => (bool) $user->is_admin);
});

it('rejects a request without a token', function () {
    postMcp(null)->assertStatus(401);
});

it('rejects an unknown token', function () {
    postMcp('fmcp_not_a_real_token')->assertStatus(401);
});

it('rejects a token without the configured prefix', function () {
    postMcp('wrong_prefix_token')->assertStatus(401);
});

it('rejects a revoked token', function () {
    $user = makeUser(isAdmin: true);
    ['plainText' => $plainText, 'token' => $token] = FilamentMcpToken::issue($user, 'Test');
    $token->update(['revoked_at' => now()]);

    postMcp($plainText)->assertStatus(401);
});

it('forbids a user who fails the authorization callback', function () {
    $user = makeUser(isAdmin: false);
    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Test');

    postMcp($plainText)->assertStatus(403);
});

it('fails closed when no authorization is configured', function () {
    FilamentMcp::flushAuthorization();

    $user = makeUser(isAdmin: true);
    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Test');

    postMcp($plainText)->assertStatus(403);
});

it('allows an authorized user and lists the generated tools', function () {
    $user = makeUser(isAdmin: true);
    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Test');

    $response = postMcp($plainText);

    $response->assertStatus(200);
    $response->assertSee('create_article', escape: false);
    $response->assertSee('list_articles', escape: false);
});

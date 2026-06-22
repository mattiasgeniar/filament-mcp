<?php

use Filament\Facades\Filament;
use Illuminate\Testing\TestResponse;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Project;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Team;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Resources\ProjectResource;

function postTenantMcp(string $token, array $payload, array $headers = []): TestResponse
{
    return test()->postJson('/filament-mcp', $payload, [
        'Accept' => 'application/json, text/event-stream',
        'Authorization' => "Bearer {$token}",
        ...$headers,
    ]);
}

beforeEach(function () {
    FilamentMcp::authorizeUsing(fn () => true);
    config([
        'filament-mcp.panel' => 'tenant',
        'filament-mcp.resources' => [ProjectResource::class],
    ]);
});

it('requires a tenant header for tenant-enabled panels', function () {
    $user = makeUser();
    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Tenant test');

    postTenantMcp($plainText, [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ])->assertStatus(403)
        ->assertJsonPath('error_description', 'Tenant required. Send the [X-Filament-Mcp-Tenant] header with the Filament tenant route key.');
});

it('sets the requested tenant before resource queries run', function () {
    $user = makeUser();
    $allowed = Team::query()->create(['name' => 'Allowed']);
    $blocked = Team::query()->create(['name' => 'Blocked']);
    $user->teams()->attach($allowed);

    Project::query()->create(['team_id' => $allowed->id, 'name' => 'Visible']);
    Project::query()->create(['team_id' => $blocked->id, 'name' => 'Hidden']);

    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Tenant test');

    $response = postTenantMcp($plainText, [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'list_projects',
            'arguments' => [],
        ],
    ], [
        'X-Filament-Mcp-Tenant' => (string) $allowed->id,
    ]);

    $response->assertStatus(200);

    $result = json_decode($response->json('result.content.0.text'), true);

    expect(Filament::getTenant()?->is($allowed))->toBeTrue();
    expect($result['records'])->toHaveCount(1);
    expect($result['records'][0]['name'])->toBe('Visible');
});

it('rejects a tenant the token user cannot access', function () {
    $user = makeUser();
    $blocked = Team::query()->create(['name' => 'Blocked']);
    ['plainText' => $plainText] = FilamentMcpToken::issue($user, 'Tenant test');

    postTenantMcp($plainText, [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], [
        'X-Filament-Mcp-Tenant' => (string) $blocked->id,
    ])->assertStatus(403)
        ->assertJsonPath('error_description', 'You are not allowed to access the requested Filament tenant.');
});

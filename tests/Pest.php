<?php

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Mattiasgeniar\FilamentMcp\Server\ToolFactory;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\User;
use Mattiasgeniar\FilamentMcp\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function makeUser(bool $isAdmin = true): User
{
    return User::query()->create([
        'name' => 'Test',
        'email' => 'user' . uniqid() . '@example.com',
        'is_admin' => $isAdmin,
    ]);
}

function actingAsMcpUser(?User $user = null): User
{
    $user ??= makeUser();

    request()->setUserResolver(fn () => $user);

    return $user;
}

/**
 * @return array{user: User, token: FilamentMcpToken}
 */
function actingAsMcpToken(?User $user = null, string $name = 'Test token'): array
{
    $user ??= makeUser();

    ['token' => $token] = FilamentMcpToken::issue($user, $name);

    request()->setUserResolver(fn () => $user);
    request()->attributes->set(FilamentMcpToken::REQUEST_ATTRIBUTE, $token);

    return ['user' => $user, 'token' => $token];
}

function mcpTool(string $name): Tool
{
    foreach (app(ToolFactory::class)->make() as $tool) {
        if ($tool->name() === $name) {
            return $tool;
        }
    }

    throw new RuntimeException("Tool [{$name}] not found.");
}

/**
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>
 */
function callMcpTool(string $name, array $arguments): array
{
    $response = mcpTool($name)->handle(new Request($arguments));

    return json_decode((string) $response->content(), true);
}

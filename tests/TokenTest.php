<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\AdminUser;

it('issues a hashed, prefixed token and resolves it from the plaintext', function () {
    $user = makeUser();

    ['token' => $token, 'plainText' => $plainText] = FilamentMcpToken::issue($user, 'Laptop');

    expect($plainText)->toStartWith('fmcp_');
    expect($token->token)->toBe(hash('sha256', $plainText));
    expect(FilamentMcpToken::findByPlainText($plainText)?->is($token))->toBeTrue();
    expect($token->user?->is($user))->toBeTrue();
    expect($token->tokenable_type)->toBe($user->getMorphClass());
});

it('binds tokens to the issuing authenticatable model, not just a numeric id', function () {
    $user = makeUser();
    $admin = AdminUser::query()->create([
        'id' => $user->getKey(),
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]);

    ['plainText' => $userPlainText] = FilamentMcpToken::issue($user, 'User token');
    ['plainText' => $adminPlainText] = FilamentMcpToken::issue($admin, 'Admin token');

    expect(FilamentMcpToken::findByPlainText($userPlainText)?->user?->is($user))->toBeTrue();
    expect(FilamentMcpToken::findByPlainText($adminPlainText)?->user?->is($admin))->toBeTrue();
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

it('upgrades legacy token tables with a non-null user_id column', function () {
    Schema::dropIfExists('filament_mcp_tokens');

    try {
        Schema::create('filament_mcp_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        $user = makeUser();

        DB::table('filament_mcp_tokens')->insert([
            'user_id' => $user->getKey(),
            'name' => 'Legacy',
            'token' => str_repeat('a', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (require __DIR__ . '/../database/migrations/add_tokenable_to_filament_mcp_tokens_table.php')->up();
        (require __DIR__ . '/../database/migrations/make_filament_mcp_tokens_user_id_nullable.php')->up();

        $userId = collect(Schema::getColumns('filament_mcp_tokens'))
            ->firstWhere('name', 'user_id');

        expect($userId['nullable'])->toBeTrue()
            ->and(DB::table('filament_mcp_tokens')->where('name', 'Legacy')->value('tokenable_type'))->toBe($user->getMorphClass())
            ->and(DB::table('filament_mcp_tokens')->where('name', 'Legacy')->value('tokenable_id'))->toBe($user->getKey());

        ['token' => $token] = FilamentMcpToken::issue($user, 'New token');

        expect($token->user?->is($user))->toBeTrue();
    } finally {
        Schema::dropIfExists('filament_mcp_tokens');
        (require __DIR__ . '/../database/migrations/create_filament_mcp_tokens_table.php')->up();
    }
});

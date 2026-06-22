<?php

namespace Mattiasgeniar\FilamentMcp\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as DefaultUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mattiasgeniar\FilamentMcp\FilamentMcp;

/**
 * @property int $id
 * @property int|string $tokenable_id
 * @property class-string<Model>|string $tokenable_type
 * @property string $name
 * @property string $token
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property-read (Model&Authenticatable)|null $user
 */
class FilamentMcpToken extends Model
{
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Create a token for the given user and return both the model and the
     * one-time plaintext value (which is never stored).
     *
     * @return array{token: self, plainText: string}
     */
    public static function issue(Authenticatable $user, string $name): array
    {
        $plainText = FilamentMcp::tokenPrefix() . Str::random(48);

        $token = static::query()->create([
            ...self::tokenableAttributes($user),
            'name' => $name,
            'token' => static::hash($plainText),
        ]);

        return ['token' => $token, 'plainText' => $plainText];
    }

    public static function findByPlainText(string $plainText): ?self
    {
        if (! str_starts_with($plainText, FilamentMcp::tokenPrefix())) {
            return null;
        }

        return static::query()
            ->where('token', static::hash($plainText))
            ->whereNull('revoked_at')
            ->first();
    }

    public static function hash(string $plainText): string
    {
        return hash('sha256', $plainText);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, Authenticatable $user): Builder
    {
        return $query->where(self::tokenableAttributes($user));
    }

    public function revoke(): void
    {
        if ($this->revoked_at !== null) {
            return;
        }

        $this->update(['revoked_at' => now()]);
    }

    public function markAsUsed(): void
    {
        if ($this->last_used_at?->gt(now()->subMinute())) {
            return;
        }

        static::query()->whereKey($this->getKey())->update(['last_used_at' => now()]);
    }

    /** @return MorphTo<Model, $this> */
    public function user(): MorphTo
    {
        return $this->morphTo('tokenable', 'tokenable_type', 'tokenable_id');
    }

    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model', DefaultUser::class);

        return $model;
    }

    /**
     * @return array{tokenable_type: string, tokenable_id: int|string|null}
     */
    private static function tokenableAttributes(Authenticatable $user): array
    {
        if (! $user instanceof Model) {
            throw new InvalidArgumentException('Filament MCP tokens can only be issued for Eloquent authenticatable models.');
        }

        return [
            'tokenable_type' => $user->getMorphClass(),
            'tokenable_id' => $user->getAuthIdentifier(),
        ];
    }
}

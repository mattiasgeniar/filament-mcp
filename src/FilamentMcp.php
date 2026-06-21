<?php

namespace Mattiasgeniar\FilamentMcp;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

class FilamentMcp
{
    private static ?Closure $authUsing = null;

    /**
     * Register the callback that decides who may use the MCP server. Call this
     * from a service provider's boot method. This is the cache-safe alternative
     * to a closure in the config file.
     *
     * @param  Closure(Authenticatable): bool  $callback
     */
    public static function authorizeUsing(Closure $callback): void
    {
        static::$authUsing = $callback;
    }

    /**
     * Decide whether the given user may use the MCP server. Resolution order:
     * the registered closure, then a `viewFilamentMcp` gate, then deny.
     * The default is fail-closed: with neither configured, nobody gets in.
     */
    public static function authorize(Authenticatable $user): bool
    {
        if (static::$authUsing !== null) {
            return (bool) (static::$authUsing)($user);
        }

        if (Gate::has('viewFilamentMcp')) {
            return Gate::forUser($user)->allows('viewFilamentMcp');
        }

        return false;
    }

    public static function tokenPrefix(): string
    {
        return (string) config('filament-mcp.token_prefix', 'fmcp_');
    }

    /**
     * Reset the registered authorization callback. Intended for tests.
     */
    public static function flushAuthorization(): void
    {
        static::$authUsing = null;
    }
}

<?php

namespace Mattiasgeniar\FilamentMcp\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ResourceAuthorizer
{
    /**
     * Mirrors Filament's non-strict default: when a model has no policy method
     * for the action, the action is allowed; when a policy method exists, it is
     * enforced for the acting user. Access to the server as a whole is already
     * gated by the token middleware and the configured authorization callback.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function allows(Authenticatable $user, string $modelClass, string $action, ?Model $record = null): bool
    {
        $target = $record ?? $modelClass;
        $policy = Gate::getPolicyFor($modelClass);

        if ($policy !== null && method_exists($policy, $action)) {
            return Gate::forUser($user)->inspect($action, $target)->allowed();
        }

        return true;
    }
}

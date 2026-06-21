<?php

namespace Mattiasgeniar\FilamentMcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainText = $request->bearerToken();

        if ($plainText === null) {
            return $this->unauthorized('Bearer token required.');
        }

        $token = FilamentMcpToken::findByPlainText($plainText);

        if ($token === null) {
            return $this->unauthorized('Invalid or revoked token.');
        }

        $user = $token->user;

        if ($user === null) {
            return $this->unauthorized('Token has no associated user.');
        }

        if (! FilamentMcp::authorize($user)) {
            return $this->forbidden('You are not allowed to use the MCP server.');
        }

        $request->setUserResolver(fn () => $user);
        Auth::setUser($user);

        $token->markAsUsed();

        return $next($request);
    }

    private function unauthorized(string $description): Response
    {
        return response()->json([
            'error' => 'unauthorized',
            'error_description' => $description,
        ], 401);
    }

    private function forbidden(string $description): Response
    {
        return response()->json([
            'error' => 'forbidden',
            'error_description' => $description,
        ], 403);
    }
}

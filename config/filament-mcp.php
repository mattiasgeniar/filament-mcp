<?php

// config for Mattiasgeniar/FilamentMcp

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch. When false, no route is registered and the MCP server is
    | unreachable. Handy to keep the server off in environments where you do
    | not want AI agents touching your data.
    |
    */

    'enabled' => env('FILAMENT_MCP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    |
    | The path the MCP server is mounted on, and any extra middleware to append
    | after the package's own token authentication. The server speaks the
    | streamable HTTP transport, so point your MCP client at this URL.
    |
    */

    'path' => env('FILAMENT_MCP_PATH', 'filament-mcp'),

    'middleware' => [
        'throttle:60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Server identity
    |--------------------------------------------------------------------------
    */

    'server' => [
        'name' => env('FILAMENT_MCP_NAME', 'Filament MCP'),
        'instructions' => 'Manage application content through the configured Filament resources. '
            . 'Each resource exposes list, get, create, update, and delete tools. '
            . 'Only text-like fields are supported; file uploads and custom components are not exposed.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access token
    |--------------------------------------------------------------------------
    |
    | Tokens are hashed before storage. The prefix makes them easy to spot and
    | lets the server reject obviously malformed tokens before hitting the
    | database. Generate one with `php artisan filament-mcp:token`.
    |
    */

    'token_prefix' => 'fmcp_',

    /*
    |--------------------------------------------------------------------------
    | Exposed resources
    |--------------------------------------------------------------------------
    |
    | The Filament resources you want to expose, and which abilities to enable
    | for each. The shorthand form (a bare class string) enables every ability:
    |
    |     \App\Filament\Resources\PostResource::class,
    |
    | The expanded form lets you scope abilities and attach an optional data
    | preparer (a class implementing Contracts\PreparesRecordData) to mirror any
    | logic that normally lives in your Filament page (e.g. slug generation):
    |
    |     \App\Filament\Resources\PostResource::class => [
    |         'read' => true,
    |         'create' => true,
    |         'update' => true,
    |         'delete' => false,
    |         'prepare' => \App\Mcp\PreparePostData::class,
    |     ],
    |
    */

    'resources' => [
        //
    ],

];

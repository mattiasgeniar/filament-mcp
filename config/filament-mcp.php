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
    | Filament panel context
    |--------------------------------------------------------------------------
    |
    | MCP requests run outside Filament's normal panel routes, so the package
    | establishes the panel context before any resource queries run. Leave this
    | null to use the current/default panel, or set an explicit panel id when
    | your app has multiple panels.
    |
    | If that panel has Filament tenancy enabled, clients must send the tenant
    | route key in this header. The user resolved from the token must be allowed
    | to access that tenant before any tools run.
    |
    */

    'panel' => env('FILAMENT_MCP_PANEL'),

    'tenant_header' => 'X-Filament-Mcp-Tenant',

    /*
    |--------------------------------------------------------------------------
    | Server identity
    |--------------------------------------------------------------------------
    */

    'server' => [
        'name' => env('FILAMENT_MCP_NAME', 'Filament MCP'),
        'instructions' => 'Manage application content through the configured Filament resources. '
            . 'Each resource exposes tools according to its Filament pages and MCP resource config. '
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
    | for each. Built-in tools are only generated when matching Filament pages
    | exist. The shorthand form (a bare class string) enables list/get,
    | create, and update where those pages exist. Delete is always opt-in:
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

    /*
    |--------------------------------------------------------------------------
    | Panel UI
    |--------------------------------------------------------------------------
    |
    | A Filament page where users who can access the panel manage their own
    | personal MCP tokens (generate and revoke). Register the plugin on your
    | panel to enable it:
    |
    |     use Mattiasgeniar\FilamentMcp\Filament\FilamentMcpPlugin;
    |
    |     $panel->plugin(FilamentMcpPlugin::make());
    |
    | The page is only visible to users your authorization gate/callback allows,
    | the same rule that guards the MCP server itself. Set `enabled` to false to
    | hide the page even where the plugin is registered.
    |
    | `show_setup_guide` renders a tabbed "Connect a client" section below the
    | token table with ready-to-paste config for Claude Code, Cursor, and VS Code.
    | Set it to false to hide that guide.
    |
    */

    'ui' => [
        'enabled' => true,

        'show_setup_guide' => true,

        'navigation' => [
            'group' => 'MCP',
            'label' => 'Tokens',
            'icon' => 'heroicon-o-key',
            'sort' => null,
        ],
    ],

];

# Filament MCP

[![Tests](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/tests.yml/badge.svg)](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/tests.yml)
[![PHPStan](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/phpstan.yml/badge.svg)](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/phpstan.yml)

Expose your [Filament](https://filamentphp.com) resources to AI agents over the
[Model Context Protocol](https://modelcontextprotocol.io), with per-record
**create, read, update, and delete** tools generated straight from your existing
resource forms.

Point Claude Code, Cursor, or any MCP client at your app and let it manage your
content the same way a human would in the admin panel, gated by a token and an
authorization callback you control.

## Why

Filament's own AI tooling helps you *write* Filament code. This package does the
opposite: it lets an agent *operate* a running panel. Unlike the read/bulk-action
oriented alternatives, it generates real per-record CRUD tools on the official
[`laravel/mcp`](https://github.com/laravel/mcp) server, enforces your policies,
and is configured almost entirely from a single config file.

- **Generated, not hand-written.** Each resource's form is introspected, so a new
  field shows up as a tool argument automatically.
- **Safe by default.** Access is fail-closed: no one gets in until you say who can.
- **Honest about scope.** Only text-like fields are exposed (see [Limitations](#limitations)).
- **Audited.** Every tool call is recorded.

## Requirements

- PHP 8.2+
- Laravel 12 or 13
- Filament 5

## Installation

```bash
composer require mattiasgeniar/filament-mcp
php artisan migrate
```

The config file is published automatically via the install command, or manually:

```bash
php artisan filament-mcp:install
# or
php artisan vendor:publish --tag=filament-mcp-config
```

## Configuration

Everything lives in `config/filament-mcp.php`. The two things you must set are
**who can access the server** and **which resources to expose**.

### 1. Who has access

Authorization is fail-closed: until you define it, nobody can connect. Pick one:

**A `viewFilamentMcp` gate** (recommended, cache-safe):

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

Gate::define('viewFilamentMcp', fn ($user) => $user->is_admin);
```

**Or a callback** (handy when the rule does not belong in a gate):

```php
use Mattiasgeniar\FilamentMcp\FilamentMcp;

FilamentMcp::authorizeUsing(fn ($user) => $user->is_admin);
```

### 2. Which resources to expose

```php
// config/filament-mcp.php
'resources' => [
    // Shorthand: enables list/get/create/update/delete
    \App\Filament\Resources\PostResource::class,

    // Expanded: scope abilities and attach an optional data preparer
    \App\Filament\Resources\PageResource::class => [
        'read' => true,
        'create' => true,
        'update' => true,
        'delete' => false,
        'prepare' => \App\Mcp\PreparePageData::class,
    ],
],
```

`prepare` points at a class implementing
[`PreparesRecordData`](src/Contracts/PreparesRecordData.php). Use it to mirror
logic that normally lives in your Filament page (for example slug generation):

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mattiasgeniar\FilamentMcp\Contracts\PreparesRecordData;

class PreparePageData implements PreparesRecordData
{
    public function __invoke(array $data, ?Model $record): array
    {
        $data['slug'] ??= Str::slug($data['title']);

        return $data;
    }
}
```

### Other options

| Key            | Default         | Purpose                                            |
| -------------- | --------------- | -------------------------------------------------- |
| `enabled`      | `true`          | Master switch; when false no route is registered.  |
| `path`         | `filament-mcp`  | The URL the server is mounted on.                  |
| `middleware`   | `throttle:60,1` | Extra middleware appended after token auth.        |
| `token_prefix` | `fmcp_`         | Prefix for generated tokens.                       |

## Issuing a token

Tokens are hashed before storage and shown only once. Generate one from the CLI:

```bash
php artisan filament-mcp:token user@example.com --name="My laptop"
```

The command refuses to issue a token to a user who is not authorized (override
with `--force`).

## Connecting an MCP client

The server speaks the streamable HTTP transport. Point your client at the URL and
pass the token as a bearer header. For Claude Code (`.mcp.json`):

```json
{
  "mcpServers": {
    "my-app": {
      "type": "http",
      "url": "https://your-app.test/filament-mcp",
      "headers": { "Authorization": "Bearer fmcp_..." }
    }
  }
}
```

Each exposed resource produces `list_*`, `get_*`, `create_*`, `update_*`, and
`delete_*` tools, named from the model (e.g. `create_post`).

## Security model

1. **Token** — every request needs a valid, non-revoked bearer token.
2. **Authorization** — the resolved user must pass your gate/callback (fail-closed).
3. **Policies** — each tool call also respects the model's Filament policy when one
   exists.
4. **Query scoping** — records are read and written through the resource's
   `getEloquentQuery()`, so your tenant scopes and soft-delete filters apply.
5. **Audit** — every call is logged to `filament_mcp_tool_calls`.

Revoke a token by setting `revoked_at` on its `filament_mcp_tokens` row.

## Limitations

This is v1 and intentionally scoped:

- **Text-like fields only.** Text, textarea, markdown/rich editors, selects,
  toggles/checkboxes and date pickers are mapped. File uploads, custom components,
  and fields the form would not persist (`disabled()`, `dehydrated(false)`) are
  skipped, so the exposed surface matches what a real save writes.
- **Closure-based form validation is not enforced** at the MCP layer; database
  constraints and model events remain the backstop.
- **Page-level logic** is honored only through a `prepare` class.
- **Token management is CLI-only.** A panel UI is planned for v2.

## Testing

```bash
composer test
```

## Credits

- [Mattias Geniar](https://github.com/mattiasgeniar)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

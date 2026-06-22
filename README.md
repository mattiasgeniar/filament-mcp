# Filament MCP

[![Tests](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/tests.yml/badge.svg)](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/tests.yml)
[![PHPStan](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/phpstan.yml/badge.svg)](https://github.com/mattiasgeniar/filament-mcp/actions/workflows/phpstan.yml)

Expose your [Filament](https://filamentphp.com) resources to AI agents over the
[Model Context Protocol](https://modelcontextprotocol.io), with per-record
**create, read, and update** tools generated straight from your existing
resource forms. Delete tools are available as an explicit per-resource opt-in.

Point Claude Code, Cursor, or any MCP client at your app and let it manage your
content the same way a human would in the admin panel, gated by a token and an
authorization callback you control.

## Why

This package lets an AI agent do everything a human could already do in the
Filament dashboard. Where Filament's own AI tooling helps you *write* Filament
code, this does the opposite: it lets an agent *operate* a running panel. Unlike
the read/bulk-action oriented alternatives, it generates real per-record CRUD
tools on the official [`laravel/mcp`](https://github.com/laravel/mcp) server,
enforces your policies, and is configured almost entirely from a single config
file.

- **Generated, not hand-written.** Each resource's form is introspected, so a new
  field shows up as a tool argument automatically.
- **Safe by default.** Access is forbidden until you explicitly grant it in the config.
- **Text fields only.** Only text-like fields are exposed (see [Limitations](#limitations)).
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

Access is forbidden until you explicitly grant it in the config; nobody can
connect until you define who is allowed. Pick one:

**A `useFilamentMcp` gate** (recommended, cache-safe):

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

Gate::define('useFilamentMcp', fn ($user) => $user->is_admin);
```

**Or a callback** (handy when the rule does not belong in a gate):

```php
use Mattiasgeniar\FilamentMcp\FilamentMcp;

FilamentMcp::authorizeUsing(fn ($user) => $user->is_admin);
```

### 2. Which resources to expose

```php
// config/filament-mcp.php
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PostResource;
use App\Mcp\PreparePageData;

// ...

'resources' => [
    // Shorthand: enables list/get/create/update when matching Filament pages exist
    PostResource::class,

    // Expanded: scope abilities, opt in to delete, and attach an optional data preparer
    PageResource::class => [
        'read' => true,
        'create' => true,
        'update' => true,
        'delete' => false,
        'prepare' => PreparePageData::class,
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

### Custom actions

Expose a custom per-record action as its own tool (the guardrailed way to mirror
a Filament action or bulk action). Add an `actions` map and point each entry at a
class extending [`ResourceAction`](src/Actions/ResourceAction.php):

```php
\App\Filament\Resources\PostResource::class => [
    'actions' => [
        'publish' => \App\Mcp\PublishPost::class, // becomes the publish_post tool
    ],
],
```

```php
use Illuminate\Database\Eloquent\Model;
use Mattiasgeniar\FilamentMcp\Actions\ResourceAction;

class PublishPost extends ResourceAction
{
    public function description(): string
    {
        return 'Publish the post.';
    }

    public function handle(Model $record, array $arguments): mixed
    {
        $record->update(['published' => true]);

        return ['published' => true];
    }
}
```

The action is authorized before it runs: the acting user must pass the resource
policy ability returned by `ability()` (defaults to `update`) against the target
record, exactly like the built-in write tools. Override `ability()` to map onto a
dedicated policy method.

Arguments are an allowlist: only keys you declare in `rules()` are validated and
forwarded to `handle()`, so an agent cannot smuggle undeclared attributes into the
action. Mirror whatever `schema()` advertises:

```php
public function schema(JsonSchema $schema): array
{
    return ['reason' => $schema->string()->description('Why it was published.')];
}

public function rules(): array
{
    return ['reason' => ['required', 'string', 'max:255']];
}
```

### Other options

| Key            | Default         | Purpose                                            |
| -------------- | --------------- | -------------------------------------------------- |
| `enabled`      | `true`          | Master switch; when false no route is registered.  |
| `path`         | `filament-mcp`  | The URL the server is mounted on.                  |
| `middleware`   | `throttle:60,1` | Extra middleware appended after token auth.        |
| `panel`        | `null`          | Filament panel id to run tools under.              |
| `tenant_header` | `X-Filament-Mcp-Tenant` | Header used to select the tenant on tenant panels. |
| `token_prefix` | `fmcp_`         | Prefix for generated tokens.                       |

## Issuing a token

Tokens are bound to the issuing Filament user model, hashed before storage, and
shown only once. Generate one from the CLI:

```bash
php artisan filament-mcp:token user@example.com --name="My laptop"
```

The command refuses to issue a token to a user who is not authorized (override
with `--force`).

### In the panel

Users can manage their own tokens from a Filament page instead of the CLI.
Register the plugin on your panel:

```php
use Mattiasgeniar\FilamentMcp\Filament\FilamentMcpPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentMcpPlugin::make());
}
```

This adds an **MCP → Tokens** page where each user generates and revokes their
own personal tokens (the plaintext is shown once, with a copy button). The page
is only visible to users your authorization gate/callback allows, the same rule
that guards the server. Customise the navigation, or turn the page off entirely,
under the `ui` config key.

If your panel uses a custom theme, do the one-time step in
[Using a custom Filament theme](#using-a-custom-filament-theme) so the page is styled.

## Using a custom Filament theme

The token page is built from Blade views that ship in this package. Filament
compiles a panel's CSS with Tailwind, which only generates the utility classes it
finds in the sources you tell it to scan. A **custom** theme does not scan
`vendor/`, so this package's classes are purged and the page renders unstyled
(text and the token field collapse onto one cramped row instead of stacking).

Register the package's views as a Tailwind source in your theme's CSS, then
rebuild your assets:

```css
/* resources/css/filament/<your-panel>/theme.css */
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../vendor/mattiasgeniar/filament-mcp/resources/views/**/*'; /* add this */
```

```bash
npm run build   # or: vite build
```

Path depth: the `@source` is relative to the theme CSS file. From the default
`resources/css/filament/<panel>/theme.css` location that is four levels up
(`../../../../`) to reach the project root, matching the Filament import above.

You only need this with a custom theme. The **default** Filament theme already
compiles every class the package uses, so it works with no extra setup.

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

Or add it with the Claude Code CLI:

```bash
claude mcp add --transport http my-app https://your-app.test/filament-mcp \
  --header "Authorization: Bearer fmcp_..."
```

Remove it again with:

```bash
claude mcp remove my-app
```

The token page renders this same ready-to-paste config for Claude Code, Cursor,
VS Code, and Codex under a **Connect a client** section, with the endpoint URL
already filled in. Hide it with `ui.show_setup_guide => false`.

If the configured/current Filament panel uses Filament tenancy, include the
tenant route key in the `X-Filament-Mcp-Tenant` header (or the configured
`tenant_header`). The token user must implement Filament's tenant contract and
pass `canAccessTenant()` for that tenant before any tools run.

Each exposed resource can produce `list_*`, `get_*`, `create_*`, `update_*`, and
custom action tools named from the model (e.g. `create_post`). Built-in
operations are only generated when the corresponding Filament resource page
exists: `list_*` requires an index or manage page, `get_*` requires a view,
index, or manage page (so a view-only resource is fetchable by id without
exposing a full listing), create requires a create or manage page, and update
requires an edit or manage page. `delete_*` tools are
never generated by shorthand config; set `'delete' => true` for a resource that
should expose deletes, and policy checks still run per record.

A single `describe_resources` tool lets an agent discover what is exposed
(resources, operations, actions, and fields) in one call.

**Reads vs writes.** Writes (`create`/`update`) are driven by the resource's
**form**, so the agent can only set fields the form allows. Reads (`list`/`get`)
return the **union** of the resource's **infolist** (what Filament shows on the
view page) and its writable form fields, so the agent can always read back what
it can write and still see view-only entries. A resource with only a form, only
an infolist, or both is fully readable either way. The model's `$hidden` and
`$visible` settings are enforced before tool schemas are built, so hidden
attributes are excluded from discovery, writes, reads, search, filters, and sort
options even if they appear in the form, infolist, or `read_fields`.

When a resource builds its view schema on the **page** (a `ViewRecord`) rather
than the resource, introspection finds nothing to read. List the readable
attributes explicitly with `read_fields`:

```php
\App\Filament\Resources\RunResource::class => [
    'write' => false,
    'read_fields' => ['check_id', 'result', 'started_at', 'ended_at'],
],
```

## Security model

1. **Token** — every request needs a valid, non-revoked bearer token.
2. **Authorization** — the resolved user must pass your gate/callback; access is denied until you grant it.
3. **Filament resource surface** — tools are generated only for operations that
   have matching resource pages, and delete is an explicit opt-in.
4. **Model attribute visibility** — fields hidden by the Eloquent model are not
   exposed in schemas, list controls, or responses.
5. **Policies** — each tool call also respects the model's Filament policy when one
   exists.
6. **Query scoping** — records are read and written through the resource's
   `getEloquentQuery()`, so your tenant scopes and soft-delete filters apply.
7. **Audit** — every call is logged to `filament_mcp_tool_calls`.

Revoke a token by setting `revoked_at` on its `filament_mcp_tokens` row.

## Limitations

This is v1 and intentionally scoped:

- **Text-like fields only for writes.** Text, textarea, markdown/rich editors,
  selects, toggles/checkboxes, numeric inputs (mapped to `integer`/`number`) and
  date pickers are mapped. File uploads, custom components, and fields the form
  would not persist (`disabled()`, `dehydrated(false)`) are skipped, so the
  writable surface matches what a real save writes. Reads union the infolist and
  these form fields (see "Reads vs
  writes" above).
- **Closure-based form validation is not enforced** at the MCP layer; database
  constraints and model events remain the backstop.
- **Page-level logic** is honored only through a `prepare` class.
- **Token management** is available both from the CLI and, when the plugin is
  registered, as a self-service Filament page per user.

## Testing

```bash
composer test
```

## Credits

- [Mattias Geniar](https://github.com/mattiasgeniar)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

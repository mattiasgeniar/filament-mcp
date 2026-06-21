# Decisions & open questions

Working notes from the initial build. Decisions I made autonomously are recorded
with the reasoning; open questions are collected at the bottom for us to settle.

## Guiding principle

What is possible via the Filament panel should be possible via MCP, with
guardrails that let the developer narrow or override what the MCP can do. The
panel is the source of truth for both shape (forms drive writes, infolists drive
reads) and access (policies + query scopes), and config opts resources in and
scopes their abilities.

## Decisions made

### Naming & namespace
- Package: `mattiasgeniar/filament-mcp`, PSR-4 namespace `Mattiasgeniar\FilamentMcp\`.
- Public manager class is `FilamentMcp` (static API, Horizon/Telescope style).

### Versions
- **Laravel 12 & 13 only, PHP 8.2+, Filament 5.** `laravel/mcp` requires
  `illuminate/json-schema ^12.41|^13`, which rules out Laravel 11. CI matrix
  matches (L12/L13 × PHP 8.2-8.4, excluding L13+PHP8.2).
- Built on the official `laravel/mcp`, not Kirschbaum's Laravel Loop, so it stays
  on Laravel's first-party MCP stack.

### Configuration is cache-safe (no closures in config)
- `php artisan config:cache` must keep working, so the config file holds only
  plain values. Authorization is therefore **not** a config closure. Instead:
  - a `viewFilamentMcp` Gate (recommended), or
  - `FilamentMcp::authorizeUsing(Closure)` registered in a service provider.
- Default is **fail-closed**: with neither set, every request is denied.
- Per-resource `prepare` hooks are **class names** implementing `PreparesRecordData`,
  not closures, for the same cache-safety reason.

### Resource exposure
- A bare class string enables **full CRUD** (mirrors what the panel allows). The
  expanded array form scopes `read`/`create`/`update`/`delete` (and accepts a
  `write` alias for create+update).

### Tokens & audit
- Tokens are random, prefixed (`fmcp_`, configurable), stored as a SHA-256 hash.
  The prefix is checked before hashing to reject malformed tokens cheaply.
- Issuance is **CLI-only** for v1 (`filament-mcp:token`); a panel UI is deferred
  to v2 per the brief.
- Tables `filament_mcp_tokens` and `filament_mcp_tool_calls`. `user_id` is a plain
  indexed `unsignedBigInteger` with **no foreign key** (the users table name/PK
  type varies between apps).
- Migrations include `down()` (a distributable package should support rollback,
  unlike the Oh Dear app's forward-only convention).

### Engine
- Only text-like fields are mapped (text/textarea/markdown/rich, select, toggle,
  checkbox, datetime). Everything else (file uploads, custom components) is
  skipped and surfaced via `ResourceSchema::$skippedFields`.
- `ResourceAuthorizer` mirrors Filament's non-strict default: no policy method =
  allowed; a present policy method is enforced for the acting user.
- The route is registered in `packageBooted()` via `Mcp::web()` on the configured
  path with `[Authenticate, ...config middleware]`.

## Security review outcome

Two independent reviews ran against the package. No critical issues. Fixed:

- **`write => false` now also disables `delete`** (previously delete ignored the
  `write` shorthand, the dangerous direction).
- **Disabled / non-dehydrated fields are skipped**, so the MCP surface can't write
  or read columns the form itself would never persist.
- **Queries go through `Resource::getEloquentQuery()`** (list/get/update/delete),
  so tenant scopes and soft-delete filters apply, no cross-scope access.
- **String/UUID primary keys** are supported (ids are no longer integer-only).
- **`prepare` classes are validated** to implement `PreparesRecordData`.
- **Audit log records denials/failures as `success = false`** (was always true).

Deferred (tracked as open questions below): audit-argument redaction (#4) and
audit-table pruning (#3).

## Competitive landscape (researched 2026-06-21)

How other admin panels approach MCP / AI:

- **Filament** — no first-party runtime MCP. `kirschbaum/laravel-loop-filament`
  is the only shipped one: list/describe/query + **bulk actions** (no per-record
  CRUD), built on Laravel Loop (not `laravel/mcp`), beta. Other "filament mcp"
  packages are **build-time** doc servers (help agents write Filament code).
- **Backpack v7** — announced an MCP server ("read, edit, delete data via any AI
  agent, automate workflows") but it's "coming in v7.x", no public config/auth
  detail yet. Closest in vision to us.
- **MoonShine 4** — AI is build-time/in-panel (Forty-Five assistant, MoonVibe
  generator), not an external MCP server exposing resources.
- **Nova / Orchid** — no resource-exposing MCP.

Where we already lead: per-record CRUD, official `laravel/mcp`, fail-closed auth
+ policy enforcement + `getEloquentQuery` scoping + audit, form-writes/infolist-reads.

Ideas worth adopting (roadmap, ordered):
1. **Table-aware list** — support the resource table's search, filters, sorting,
   and pagination in `list_*` (laravel-loop has querying; it's a real Filament
   capability we're missing).
2. **Actions as tools** — expose selected Filament actions / bulk actions per
   resource (Backpack "workflows", laravel-loop bulk actions).
3. **Relation managers** — expose a resource's relations for read (and maybe write).
4. **Discovery tool** — a `describe`/`list_resources` tool for agent self-discovery.
5. **Permission-system integration** — first-class Filament Shield / spatie
   permission mapping, beyond the single gate + policies.

## Open questions (for review)

1. **Default abilities.** A bare resource class currently enables delete too. Do
   we want the safe default to exclude `delete` (opt-in destructive)?
2. **Filament 4 support.** Currently `^5.0` only. Worth widening to `^4.0|^5.0`?
3. **Audit retention.** Add a `Prunable` trait + scheduled pruning now, or v2?
4. **Audit argument storage.** We log full tool arguments. Add optional field
   redaction for resources with sensitive content?
5. **Relationship selects.** Their options can be large/dynamic; right now they
   become an `enum`. Cap the option count or special-case relationship selects?
6. **Tool naming.** Derived from the model's snfrom-snake name (`create_post`).
   Want a per-resource name override in config?
7. **Packagist.** Publish publicly now, or keep private until v1 is tagged?
8. **License.** MIT assumed.
9. **Multi-panel.** Single global server today. Any need for per-panel servers?

# Changelog

All notable changes to `filament-mcp` will be documented in this file.

## Unreleased

### Added

- Tool calls are now attributed to the token that authenticated them (new nullable `filament_mcp_token_id` column on `filament_mcp_tool_calls`), not just to the user.
- An **Activity** action on each row of the token page opens that token's 50 most recent tool calls (name, success, duration, arguments). Scoped to your own tokens.

## 0.0.4 - 2026-06-22

### Added

- A **Connect a client** setup guide on the token page with ready-to-paste config for Claude Code, Cursor, VS Code, and Codex. The endpoint URL is prefilled and the server key follows `config('filament-mcp.server.name')`; tabs are collapsed until clicked. Hide it with `ui.show_setup_guide => false`.
- The token table is split into Active and Revoked tabs with counts, keeping long token lists readable.

## 0.0.3 - 2026-06-22

### Fixed

- MCP requests now initialize Filament panel and tenant context before tools run, so resource queries keep the same panel/tenant guardrails as the dashboard.
- MCP tokens are bound to the issuing authenticatable model instead of only a numeric user id, fixing multi-guard/custom-user collisions.
- Built-in MCP tools are generated only for operations exposed by the Filament resource pages, and delete tools are now an explicit per-resource opt-in.
- Eloquent `$hidden` / `$visible` settings are enforced before MCP schemas are built, excluding hidden attributes from discovery, writes, reads, search, filters, and sorting.
- Existing installs with a legacy non-null `user_id` token column can upgrade to morph-token storage and still issue new tokens.

## 0.0.2 - 2026-06-21

### Added

- A `FilamentMcpPlugin` that adds an MCP token page to a panel, where each user generates and revokes their own personal tokens (gated by the same authorization rule as the server).
- Numeric form inputs (`->numeric()`, `->integer()`) are exposed as JSON Schema `number`/`integer` instead of strings.
- Package migrations run automatically after `composer require`, so `php artisan migrate` is all that is needed.
- Create and update tools refresh the record before returning it, so database defaults and casts are reflected in the response.

## 0.0.1 - 2026-06-21

### Added

- Initial release.
- Generated MCP tools (`list`, `get`, `create`, `update`, `delete`) per configured Filament resource.
- Token authentication with hashed, prefixed personal tokens and a `filament-mcp:token` command.
- Fail-closed authorization via a `useFilamentMcp` gate or `FilamentMcp::authorizeUsing()` callback.
- Per-call audit logging.
- Optional per-resource data preparers via the `PreparesRecordData` contract.
- Read tools expose the union of the resource infolist and its writable form fields, so an agent can always read back what it can write; attributes the model marks `$hidden` are dropped from read output.
- `list_*` tools support search, field filters, sorting, and pagination.
- Custom per-record actions exposed as tools via the `actions` config and the `ResourceAction` base class. Actions are policy-gated (`ability()`, default `update`) and only arguments declared in `rules()` reach the handler.
- A `describe_resources` discovery tool that maps the exposed resources, operations, actions, and fields.
- A `read_fields` config option to declare readable fields explicitly for resources whose view schema lives on a page.

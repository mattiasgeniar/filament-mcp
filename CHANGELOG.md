# Changelog

All notable changes to `filament-mcp` will be documented in this file.

## Unreleased

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
- A `FilamentMcpPlugin` that adds an MCP token page to a panel, where each user generates and revokes their own personal tokens (gated by the same authorization rule as the server).

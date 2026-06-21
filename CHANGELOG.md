# Changelog

All notable changes to `filament-mcp` will be documented in this file.

## Unreleased

### Added

- Initial release.
- Generated MCP tools (`list`, `get`, `create`, `update`, `delete`) per configured Filament resource.
- Token authentication with hashed, prefixed personal tokens and a `filament-mcp:token` command.
- Fail-closed authorization via a `viewFilamentMcp` gate or `FilamentMcp::authorizeUsing()` callback.
- Per-call audit logging.
- Optional per-resource data preparers via the `PreparesRecordData` contract.
- Read tools driven by the resource infolist (falling back to the form), so view-only resources are readable.
- `list_*` tools support search, field filters, sorting, and pagination.

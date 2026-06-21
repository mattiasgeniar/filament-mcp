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

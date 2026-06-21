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
- Custom per-record actions exposed as tools via the `actions` config and the `ResourceAction` base class.
- A `describe_resources` discovery tool that maps the exposed resources, operations, actions, and fields.
- A `read_fields` config option to declare readable fields explicitly for resources whose view schema lives on a page.

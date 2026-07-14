# Changelog

## [Unreleased]

### Added

- Generator (`bin/generate-schemas.php` + `SCCoreTables\Generator\SchemaGenerator`) that
  introspects a live Sugar Calendar install via `information_schema` and emits one
  `berlindb/core` Schema class per SC table (`sc_events`, `sc_eventmeta`) into
  `src/Schemas/`, plus a manifest.
- `CapabilityTest` - strict, no allowlist: core builds a scratch table from each schema,
  which is re-introspected and compared against SC's live table.
- CI matrix over Sugar Calendar **stable** and **master** (SC runs from a plain checkout -
  no build step), a scheduled workflow that regenerates from SC's latest release, and a
  `repository_dispatch` trigger so `berlindb/core` canaries this suite.

### Status

**Green.** Both SC tables reproduce exactly. SC has no decimal columns, and its NOT NULL
`text` / `longtext` columns recreate cleanly on core with
[berlindb/core#244](https://github.com/berlindb/core/issues/244) and
[berlindb/core#245](https://github.com/berlindb/core/issues/245) fixed.

### Notes

- Structural parity only. SC's hand-coded BerlinDB semantics (sortable/searchable/
  date_query flags, the `uuid` pseudo-column, meta wiring) are not in the DDL and are not
  reproduced here.

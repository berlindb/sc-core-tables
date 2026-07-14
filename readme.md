# sc-core-tables

![Sugar Calendar readiness](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/berlindb/sc-core-tables/master/.readiness/sugar-calendar.json)

The **readiness** badge is the *behavioral* score from
[berlindb/readiness](https://github.com/berlindb/readiness): of the per-column flags
Sugar Calendar's fork declares, how many shared `berlindb/core` can express. A gap is a
concrete item on the path to reunifying SC onto shared core.

Sugar Calendar's core database tables, expressed as [`berlindb/core`](https://github.com/berlindb/core)
schemas - **auto-generated** by introspecting a live Sugar Calendar install, and
continuously tested to measure whether shared BerlinDB can faithfully reproduce them.

Sibling of [`berlindb/wp-core-tables`](https://github.com/berlindb/wp-core-tables) and
[`berlindb/edd-core-tables`](https://github.com/berlindb/edd-core-tables); Sugar Calendar
is the third BerlinDB consumer.

## Why this exists

Sugar Calendar vendors its own first-generation fork of BerlinDB (`Sugar_Calendar\Database`)
rather than consuming `berlindb/core`. This repo measures how faithfully today's shared
core can recreate SC's tables - the distance to reunifying SC onto shared BerlinDB.

## How it works

1. **Generate** (`bin/generate-schemas.php`) - reads each `sc_*` table from a live SC
   install via `information_schema` and emits a `berlindb/core` Schema class into
   [`src/Schemas/`](src/Schemas/).
2. **Capability test** (`tests/CapabilityTest.php`) - for each schema, core creates a
   scratch table from it, that table is re-introspected, and the result is compared
   against SC's live table. Strict: any structural difference core cannot reproduce
   turns the suite red.

### Structural parity only

Generation reads the DDL (columns + indexes). It does **not** capture SC's higher-level
BerlinDB semantics (sortable/searchable/date_query flags, the `uuid` pseudo-column, meta
wiring). This proves *structural* parity, not *behavioral* parity.

## Current status

**Green** - all of Sugar Calendar's tables (`sc_events`, `sc_eventmeta`) reproduce
exactly on today's `berlindb/core`. SC has no decimal columns (so it never hit
[core#244](https://github.com/berlindb/core/issues/244)), and its NOT NULL `text` /
`longtext` columns (`title`, `content`) recreate cleanly now that
[core#245](https://github.com/berlindb/core/issues/245) is fixed.

## Staying current

A scheduled workflow polls Sugar Calendar's latest release, regenerates the schemas, and
opens a PR when SC's schema changes. CI runs the capability test against SC stable and
master, and `berlindb/core` canaries this suite on every push to core master.

## Running locally

```bash
composer install
# point core at a local checkout while developing (do not commit):
composer config repositories.berlindb-core path ../path/to/berlindb-core && composer update berlindb/core
bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest
composer test
```

Regenerate against a WordPress install that has Sugar Calendar active:

```bash
wp eval-file bin/generate-schemas.php
```

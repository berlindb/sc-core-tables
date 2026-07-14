<?php
/**
 * Sugar Calendar's relationship / meta capability matrix (curated).
 *
 * SC's fork is small - a flat `events` table plus WordPress-style `eventmeta` - so its
 * only cross-table behavior is metadata (via Sugar_Calendar\...\Queries\Meta). Column
 * flags are auto-scanned; this file is the curated inventory of the relationship/meta
 * patterns, mapped to the shared berlindb/core feature that would express them.
 * berlindb/readiness scores each entry against the installed core.
 *
 * `requires` values are berlindb/readiness CoreFeatures keys:
 *   relationship.belongs_to | relationship.has_many | relationship.many_to_many |
 *   relationship.get_related | meta.store | meta.preset
 *
 * @package SCCoreTables
 */

return array(

	// WordPress-style metadata: the eventmeta sibling table via the fork's Meta query.
	array(
		'name'     => 'metadata (eventmeta)',
		'kind'     => 'meta',
		'requires' => 'meta.store',
		'note'     => 'event -> eventmeta, WordPress-shaped metadata via the fork Meta query',
	),
);

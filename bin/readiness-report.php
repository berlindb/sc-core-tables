<?php
/**
 * Compute Sugar Calendar's behavioral (column-flag) readiness and write its badge JSON.
 *
 * Run inside a WordPress environment that has Sugar Calendar active:
 *   wp eval-file bin/readiness-report.php
 *
 * SC vendors its own first-generation BerlinDB fork, so its behavioral surface - the
 * per-column flags (sortable / searchable / in / date_query / ...) - lives in SC's fork
 * Schema classes, NOT in this repo's generated (structural-only) schemas. This scores
 * those fork classes against shared berlindb/core and writes a shields endpoint badge to
 * .readiness/sugar-calendar.json. See https://github.com/berlindb/readiness.
 *
 * @package SCCoreTables
 */

// No declare(strict_types) here: wp eval-file evals this file, where a strict_types
// declaration is a fatal ("must be the very first statement in the script").

use BerlinDB\Readiness\Badge;
use BerlinDB\Readiness\CapabilityReadiness;
use BerlinDB\Readiness\CoreCapabilities;
use BerlinDB\Readiness\CoreFeatures;
use BerlinDB\Readiness\FlagReadiness;
use BerlinDB\Readiness\Report;
use BerlinDB\Readiness\SchemaSurface;

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Must run inside WordPress (wp eval-file).\n" );
	exit( 1 );
}

require_once __DIR__ . '/../vendor/autoload.php';

$fork_base = 'Sugar_Calendar\\Database\\Schema';

// Force-load the known SC fork schema (autoload), then discover any siblings loaded.
$known   = array( 'Sugar_Calendar\\Event_Schema' );
$classes = array();

foreach ( $known as $class ) {
	if ( class_exists( $class ) ) {
		$classes[] = $class;
	}
}

foreach ( get_declared_classes() as $class ) {
	if ( is_subclass_of( $class, $fork_base ) && ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}
}

if ( empty( $classes ) ) {
	fwrite( STDERR, "No Sugar Calendar fork Schema classes loaded (looked for {$fork_base} subclasses).\n" );
	exit( 1 );
}

$supported = CoreCapabilities::fromCore();
$declared  = SchemaSurface::fromClasses( $classes );
$flags     = FlagReadiness::score( 'Sugar Calendar', $supported, $declared );

// Relationships / meta: score the curated matrix against core in two dimensions -
// behavioral (can core RUN SC's queries) vs modeling (can core MODEL SC's schema).
$matrix_file = __DIR__ . '/../.readiness/capabilities.php';
$matrix      = is_readable( $matrix_file ) ? (array) require $matrix_file : array();
$features    = CoreFeatures::fromCore();

$behavioral = Report::combine( 'Sugar Calendar', $flags, CapabilityReadiness::score( 'Sugar Calendar', $features, $matrix, 'behavioral' ) );
$modeling   = Report::combine( 'Sugar Calendar', $flags, CapabilityReadiness::score( 'Sugar Calendar', $features, $matrix, 'modeling' ) );

printf( "\n== Sugar Calendar reunification readiness ==\n" );
printf( "  fork schemas: %d   flags: %d   relationship/meta entries: %d\n\n", count( $classes ), $flags->total(), count( $matrix ) );
foreach ( $modeling->rows() as $name => $row ) {
	$status = ( Report::GAP === $row['status'] ) ? 'GAP' : $row['status'];
	printf( "  %-42s %-11s %s\n", $name, $status, $row['via'] );
}
printf( "\n  BEHAVIORAL: %s%%  (%d/%d)   MODELING: %s%%  (%d/%d)\n",
	$behavioral->percent(), $behavioral->covered(), $behavioral->total(),
	$modeling->percent(), $modeling->covered(), $modeling->total()
);
printf( "  MODELING GAPS: %s\n\n", $modeling->is_ready() ? '(none)' : implode( ', ', $modeling->gaps() ) );

$out = __DIR__ . '/../.readiness';
if ( ! is_dir( $out ) ) {
	mkdir( $out, 0777, true );
}
file_put_contents( $out . '/sugar-calendar.json', Badge::toJson( $behavioral, 'behavioral' ) );
file_put_contents( $out . '/sugar-calendar-modeling.json', Badge::toJson( $modeling, 'modeling' ) );
printf( "  wrote %s/sugar-calendar.json (behavioral) + sugar-calendar-modeling.json\n\n", $out );

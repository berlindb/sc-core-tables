<?php
/**
 * Regenerate the Sugar Calendar schema classes from a live SC install.
 *
 * Run inside a WordPress environment that has Sugar Calendar (core/Lite) active:
 *   wp eval-file bin/generate-schemas.php
 *
 * It introspects every `{$prefix}sc_*` table and writes one berlindb/core Schema class
 * per table into src/Schemas/, plus a manifest mapping class name -> unprefixed table.
 *
 * Scope: every `{$prefix}sc_*` table. In CI only Sugar Calendar is active, so that is
 * exactly SC's set (sc_events + sc_eventmeta). Set SCCT_ONLY to a comma-separated list
 * of unprefixed names to restrict it locally.
 *
 * @package SCCoreTables
 */

declare( strict_types = 1 );

use SCCoreTables\Generator\SchemaGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Must run inside WordPress (wp eval-file).\n" );
	exit( 1 );
}

require_once __DIR__ . '/../vendor/autoload.php';

global $wpdb;

$schemas_dir = __DIR__ . '/../src/Schemas';

/*
 * Ensure SC's tables exist. Sugar Calendar has no separate installer function - the
 * Table constructor self-installs only under the test constant - so force install()
 * for any missing table (this runs under wp-cli, where that constant is absent).
 */
foreach ( array( '\\Sugar_Calendar\\Events_Table', '\\Sugar_Calendar\\Meta_Table' ) as $class ) {
	if ( ! class_exists( $class ) ) {
		continue;
	}

	$table = new $class();
	if ( method_exists( $table, 'exists' ) && ! $table->exists() && method_exists( $table, 'install' ) ) {
		$table->install();
	}
}

// Discover target tables.
$like   = $wpdb->esc_like( $wpdb->prefix . 'sc_' ) . '%';
$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

$only = getenv( 'SCCT_ONLY' );
if ( ! empty( $only ) ) {
	$allow  = array_map( 'trim', explode( ',', $only ) );
	$prefix = $wpdb->prefix . 'sc_';
	$tables = array_values( array_filter(
		$tables,
		static function ( $t ) use ( $allow, $prefix ) {
			return in_array( substr( $t, strlen( $prefix ) ), $allow, true );
		}
	) );
}

if ( empty( $tables ) ) {
	fwrite( STDERR, "No sc_ tables found. Is Sugar Calendar active and installed?\n" );
	exit( 1 );
}

$generator = new SchemaGenerator( $wpdb );
$prefix    = $wpdb->prefix . 'sc_';
$manifest  = array();

foreach ( $tables as $physical ) {
	$unprefixed = substr( $physical, strlen( $wpdb->prefix ) ); // e.g. sc_events
	$bare       = substr( $physical, strlen( $prefix ) );       // e.g. events
	$class      = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $bare ) ) );

	file_put_contents( "{$schemas_dir}/{$class}.php", $generator->generate( $physical, $class ) );
	$manifest[ $class ] = $unprefixed;

	echo "generated {$class} <- {$physical}\n";
}

ksort( $manifest );
$entries = '';
foreach ( $manifest as $class => $table ) {
	$entries .= "\t'{$class}' => '{$table}',\n";
}

$manifest_src = "<?php\n/**\n * GENERATED - class name => unprefixed SC table name.\n *\n"
	. " * @package SCCoreTables\\Schemas\n */\n\ndeclare( strict_types = 1 );\n\nreturn array(\n{$entries});\n";

file_put_contents( "{$schemas_dir}/manifest.php", $manifest_src );

echo 'wrote ' . count( $manifest ) . " schema(s) + manifest.\n";

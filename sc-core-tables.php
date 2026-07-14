<?php
/**
 * Plugin Name:  SC Core Tables (BerlinDB parity)
 * Description:  Sugar Calendar's core tables expressed as berlindb/core schemas, auto-generated from a live SC install to measure structural parity.
 * Author:       BerlinDB
 * License:      GPL-2.0-or-later
 * Requires PHP: 8.1
 *
 * @package SCCoreTables
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * A parity harness (generated Schemas + capability tests + the generator), not a runtime
 * feature plugin, so booting only means making the generated classes autoloadable. Sugar
 * Calendar itself owns and installs these tables.
 */
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

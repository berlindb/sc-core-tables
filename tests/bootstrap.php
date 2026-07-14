<?php
/**
 * PHPUnit bootstrap: load WP test suite, berlindb/core, Sugar Calendar, and this
 * package, then make sure SC's tables exist for the capability test to compare against.
 *
 * @package SCCoreTables\Tests
 */

declare( strict_types = 1 );

use Yoast\WPTestUtils\WPIntegration;

$scct_root = dirname( __DIR__ );

require_once $scct_root . '/vendor/autoload.php';
require_once $scct_root . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$_tests_dir = WPIntegration\get_path_to_wp_test_dir();

require_once $_tests_dir . '/includes/functions.php';

// Load Sugar Calendar (core/Lite) and this package as must-use plugins.
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $scct_root ) {
		// SC is checked out next to this plugin by CI (SC_PLUGIN_FILE).
		$sc = getenv( 'SC_PLUGIN_FILE' );
		if ( ! empty( $sc ) && is_readable( $sc ) ) {
			require_once $sc;
		}

		require_once $scct_root . '/sc-core-tables.php';
	}
);

// SC's Table constructor only self-installs under its own test constant, which the
// Yoast/WP test scaffold does not set, so force the install for any missing table.
// This runs during bootstrap (before the per-test transaction + temporary-table
// rewrite), so the tables are created for real.
tests_add_filter(
	'wp_loaded',
	static function () {
		foreach ( array( '\\Sugar_Calendar\\Events_Table', '\\Sugar_Calendar\\Meta_Table' ) as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}

			$table = new $class();
			if ( method_exists( $table, 'exists' ) && ! $table->exists() && method_exists( $table, 'install' ) ) {
				$table->install();
			}
		}
	}
);

WPIntegration\bootstrap_it();

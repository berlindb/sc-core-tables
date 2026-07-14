<?php
/**
 * Capability / parity test: can berlindb/core faithfully reproduce EDD's tables?
 *
 * For each EDD table it introspects the LIVE table (in this environment's own engine),
 * has core create a scratch table from that introspected schema, re-introspects the
 * scratch table with the same generator, and compares. A match means core can express
 * that EDD table exactly.
 *
 * Introspecting live (rather than trusting the committed src/Schemas snapshot) keeps the
 * signal to REAL core gaps: both sides use the same engine and the same generator, so an
 * engine difference between where the snapshot was generated and where CI runs cannot
 * masquerade as a core gap.
 *
 * STRICT: no allowlist. Any column or index core cannot reproduce turns the suite red.
 * (Presently red on the money decimals - core cannot express decimal(P,S) scale,
 * berlindb/core#244 - and any NOT NULL TEXT/BLOB column, where core forces a DEFAULT
 * that MySQL rejects.)
 *
 * @package SCCoreTables\Tests
 */

declare( strict_types = 1 );

namespace SCCoreTables\Tests;

use SCCoreTables\Generator\SchemaGenerator;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * @since 0.1.0
 */
class CapabilityTest extends TestCase {

	/**
	 * class name => unprefixed EDD table (e.g. 'Orders' => 'edd_orders').
	 *
	 * @return array<string, string>
	 */
	private static function manifest(): array {
		return require __DIR__ . '/../src/Schemas/manifest.php';
	}

	/**
	 * One case per known EDD table.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function table_provider(): array {
		$cases = array();
		foreach ( self::manifest() as $class => $unprefixed ) {
			$cases[ $class ] = array( $class, $unprefixed );
		}
		return $cases;
	}

	/**
	 * Core must recreate each live EDD table exactly.
	 *
	 * @dataProvider table_provider
	 *
	 * @param string $class      Schema class name (used only to name scratch artifacts).
	 * @param string $unprefixed Unprefixed EDD table (e.g. 'edd_orders').
	 */
	public function test_core_reproduces_edd_table( string $class, string $unprefixed ): void {
		global $wpdb;

		$live      = $wpdb->prefix . $unprefixed;
		$scratch   = $wpdb->prefix . 'scct_cap_' . $class;
		$tmp_class = 'ScctTmp_' . $class;
		$generator = new SchemaGenerator( $wpdb );

		$this->assertNotEmpty(
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $live ) ),
			"SC live table {$live} is missing - is Sugar Calendar installed?"
		);

		// 1. Introspect the live table into a berlindb/core Schema class (this engine).
		$src_live = $generator->generate( $live, $tmp_class );
		$this->load_schema_class( $tmp_class, $src_live );

		// 2. Have core render and create a scratch table from that schema.
		$fqcn = '\\SCCoreTables\\Schemas\\' . $tmp_class;
		$body = ( new $fqcn() )->get_create_table_string();

		/*
		 * WP_UnitTestCase installs a `query` filter that rewrites CREATE/DROP TABLE into
		 * their TEMPORARY forms, and temporary tables are invisible to SHOW TABLES and
		 * information_schema (what the generator reads back). Drop those filters so the
		 * scratch table is real and introspectable, then always restore them and drop it.
		 */
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		try {
			$wpdb->query( "DROP TABLE IF EXISTS `{$scratch}`" );

			$error = '';
			try {
				$result = $wpdb->query( "CREATE TABLE `{$scratch}` ( {$body} )" );
				if ( false === $result ) {
					$error = $wpdb->last_error;
				}
			} catch ( \Throwable $e ) {
				$error = $e->getMessage();
			}

			$this->assertNotEmpty(
				$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $scratch ) ),
				"core's CREATE for {$live} was rejected: {$error}\nDDL:\n{$body}"
			);

			// 3. Re-introspect the scratch table the same way and compare.
			$norm = static function ( string $src ) use ( $live, $scratch ): string {
				return str_replace( array( $live, $scratch ), 'TABLE', $src );
			};
			$src_scratch = $generator->generate( $scratch, $tmp_class );

			$this->assertSame(
				$norm( $src_live ),
				$norm( $src_scratch ),
				"berlindb/core cannot reproduce {$live} exactly (e.g. decimal scale, core#244)."
			);
		} finally {
			$wpdb->query( "DROP TABLE IF EXISTS `{$scratch}`" );
			add_filter( 'query', array( $this, '_create_temporary_tables' ) );
			add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		}
	}

	/**
	 * Define a generated Schema class from source, once per PHP process.
	 *
	 * @param string $class  Unqualified class name.
	 * @param string $source Full PHP source emitted by the generator.
	 */
	private function load_schema_class( string $class, string $source ): void {
		if ( class_exists( '\\SCCoreTables\\Schemas\\' . $class, false ) ) {
			return;
		}

		$file = tempnam( sys_get_temp_dir(), 'eddct_' ) . '.php';
		file_put_contents( $file, $source );
		require $file;
	}
}

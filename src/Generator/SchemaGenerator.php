<?php
/**
 * Generates berlindb/core Schema classes by introspecting live EDD tables.
 *
 * @package SCCoreTables\Generator
 */

declare( strict_types = 1 );

namespace SCCoreTables\Generator;

use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a live table's structure from information_schema and renders a
 * `berlindb/core` Schema subclass that declares the same columns and indexes.
 *
 * information_schema is used as the source of truth rather than BerlinDB's own
 * Schema::from_table(), because from_table() drops decimal scale (reads
 * decimal(18,9) as length 18) and index prefix lengths. COLUMN_TYPE carries the
 * exact type incl. scale/unsigned, and STATISTICS.SUB_PART carries index prefixes.
 *
 * Faithfulness is intentionally STRUCTURAL only: columns + indexes. The higher-level
 * BerlinDB semantics EDD hand-codes (sortable/searchable/in/date_query/validate/
 * transition/cache_key flags, meta wiring, relationships) are not present in the DDL
 * and are therefore not recoverable here - see the repo README.
 *
 * @since 0.1.0
 */
final class SchemaGenerator {

	/**
	 * @since 0.1.0
	 * @var   wpdb
	 */
	private $db;

	/**
	 * @since 0.1.0
	 * @param wpdb $db WordPress database handle.
	 */
	public function __construct( wpdb $db ) {
		$this->db = $db;
	}

	/**
	 * Render a Schema class for one physical table.
	 *
	 * @since 0.1.0
	 *
	 * @param string $physical_table Full table name incl. WP prefix (e.g. wp_edd_orders).
	 * @param string $class_name     PHP class name to emit (e.g. Orders).
	 * @return string PHP source for the Schema class.
	 */
	public function generate( string $physical_table, string $class_name ): string {
		$columns = array_map( array( $this, 'render_column' ), $this->read_columns( $physical_table ) );
		$indexes = array_map( array( $this, 'render_index' ), $this->read_indexes( $physical_table ) );

		$columns_src = implode( "\n", $columns );
		$indexes_src = implode( "\n", $indexes );

		return <<<PHP
<?php
/**
 * GENERATED FILE - do not edit by hand.
 *
 * Introspected from the live EDD table `{$physical_table}` (structure only).
 * Regenerate with `bin/generate-schemas.php`.
 *
 * @package SCCoreTables\\Schemas
 */

declare( strict_types = 1 );

namespace SCCoreTables\\Schemas;

use BerlinDB\\Database\\Kern\\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * @since 0.1.0
 */
class {$class_name} extends Schema {

	/** @var array<int, array<string, mixed>> */
	public \$columns = array(
{$columns_src}
	);

	/** @var array<int, array<string, mixed>> */
	public \$indexes = array(
{$indexes_src}
	);
}

PHP;
	}

	/**
	 * Read a table's columns from information_schema, in ordinal order.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table Physical table name.
	 * @return array<int, object> Column rows.
	 */
	private function read_columns( string $table ): array {
		return $this->db->get_results( $this->db->prepare(
			"SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_KEY
			 FROM information_schema.COLUMNS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
			 ORDER BY ORDINAL_POSITION",
			$table
		) );
	}

	/**
	 * Read a table's indexes, grouped by index name with parts in sequence order.
	 *
	 * @since 0.1.0
	 *
	 * @param string $table Physical table name.
	 * @return array<string, array<string, mixed>> Index groups keyed by index name.
	 */
	private function read_indexes( string $table ): array {
		$rows = $this->db->get_results( $this->db->prepare(
			"SELECT INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME, NON_UNIQUE, SUB_PART
			 FROM information_schema.STATISTICS
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
			 ORDER BY INDEX_NAME, SEQ_IN_INDEX",
			$table
		) );

		$groups = array();

		foreach ( $rows as $row ) {
			$name = $row->INDEX_NAME;

			if ( ! isset( $groups[ $name ] ) ) {
				$groups[ $name ] = array(
					'name'    => $name,
					'unique'  => ( '0' === (string) $row->NON_UNIQUE ),
					'columns' => array(),
				);
			}

			$groups[ $name ]['columns'][] = ( null !== $row->SUB_PART )
				? $row->COLUMN_NAME . '(' . $row->SUB_PART . ')'
				: $row->COLUMN_NAME;
		}

		return $groups;
	}

	/**
	 * Render one column row into a BerlinDB config-array literal.
	 *
	 * @since 0.1.0
	 *
	 * @param object $col Column row from information_schema.
	 * @return string One indented `array( ... ),` line.
	 */
	private function render_column( $col ): string {
		$parsed = $this->parse_type( $col->COLUMN_TYPE );
		$parts  = array( "'name' => '" . $col->COLUMN_NAME . "'" );
		$parts[] = "'type' => '" . $parsed['type'] . "'";

		if ( '' !== $parsed['length'] ) {
			$parts[] = "'length' => '" . $parsed['length'] . "'";
		}
		// A decimal's scale is a separate key ($length is the precision).
		if ( '' !== $parsed['scale'] ) {
			$parts[] = "'scale' => '" . $parsed['scale'] . "'";
		}
		// For numeric types emit `unsigned` EXPLICITLY (true or false). BerlinDB's
		// Column defaults numeric columns to unsigned, so a signed column that omitted
		// the flag would be silently widened to unsigned when core recreates it.
		if ( $this->is_numeric_type( $parsed['type'] ) ) {
			$parts[] = "'unsigned' => " . ( $parsed['unsigned'] ? 'true' : 'false' );
		}
		if ( false !== stripos( (string) $col->EXTRA, 'auto_increment' ) ) {
			$parts[] = "'extra' => 'auto_increment'";
		}
		if ( 'PRI' === $col->COLUMN_KEY ) {
			$parts[] = "'primary' => true";
		}

		$nullable = ( 'YES' === $col->IS_NULLABLE );
		if ( $nullable ) {
			$parts[] = "'allow_null' => true";
		}

		// An auto_increment column never carries a default (core omits it already), so
		// only non-auto columns emit any default at all.
		if ( false === stripos( (string) $col->EXTRA, 'auto_increment' ) ) {
			if ( $this->is_lob_type( $parsed['type'] ) ) {
				// TEXT/BLOB/JSON cannot carry a non-NULL DEFAULT in MySQL. A nullable one
				// still needs an explicit `default null` (else core supplies its own ''
				// default, which MySQL rejects); a NOT NULL one gets no default at all.
				if ( $nullable ) {
					$parts[] = "'default' => null";
				}
			} else {
				$parts = array_merge( $parts, $this->render_default( $col->COLUMN_DEFAULT, $nullable ) );
			}
		}

		return "\t\t\tarray( " . implode( ', ', $parts ) . " ),";
	}

	/**
	 * Render the default-value fragment(s) for a column.
	 *
	 * @since 0.1.0
	 *
	 * @param string|null $default  COLUMN_DEFAULT from information_schema.
	 * @param bool        $nullable Whether the column allows NULL.
	 * @return array<int, string> Zero or one `'default' => ...` fragment.
	 */
	private function render_default( $default, bool $nullable ): array {
		// No default recorded. MySQL reports this as SQL NULL (PHP null); MariaDB
		// reports the literal string "NULL" - treat both the same. A nullable column
		// gets DEFAULT NULL; a NOT NULL column with no default is declared with
		// 'default' => false so core omits the clause (rather than supplying '' / 0).
		if ( null === $default || 'NULL' === strtoupper( trim( (string) $default ) ) ) {
			return array( $nullable ? "'default' => null" : "'default' => false" );
		}

		// Function/expression defaults (e.g. current_timestamp()) are rendered raw so
		// a reviewer can see them; whether core can reproduce them is what the
		// capability test measures.
		if ( preg_match( '/^current_timestamp(\(\))?$/i', $default ) ) {
			return array( "'default' => 'CURRENT_TIMESTAMP'" );
		}

		// MariaDB quotes string literals in COLUMN_DEFAULT ('' or 'pending'); MySQL
		// does not. Strip a single layer of surrounding single quotes if present.
		if ( strlen( $default ) >= 2 && "'" === $default[0] && "'" === substr( $default, -1 ) ) {
			$default = str_replace( "''", "'", substr( $default, 1, -1 ) );
		}

		return array( "'default' => '" . $this->esc( $default ) . "'" );
	}

	/**
	 * Render one index group into a BerlinDB config-array literal.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $index Grouped index (name, unique, columns).
	 * @return string One indented `array( ... ),` line.
	 */
	private function render_index( array $index ): string {
		$cols  = array_map(
			static function ( $c ) {
				return "'" . $c . "'";
			},
			$index['columns']
		);
		$parts = array();

		if ( 'PRIMARY' === $index['name'] ) {
			$parts[] = "'type' => 'primary'";
		} elseif ( $index['unique'] ) {
			$parts[] = "'type' => 'unique'";
			$parts[] = "'name' => '" . $index['name'] . "'";
		} else {
			$parts[] = "'type' => 'key'";
			$parts[] = "'name' => '" . $index['name'] . "'";
		}

		$parts[] = "'columns' => array( " . implode( ', ', $cols ) . " )";

		return "\t\t\tarray( " . implode( ', ', $parts ) . " ),";
	}

	/**
	 * Split a COLUMN_TYPE into a BerlinDB type, length, and unsigned flag.
	 *
	 * Examples: `bigint(20) unsigned` -> [bigint, 20, true]; `decimal(18,9)` ->
	 * [decimal, "18,9", false]; `datetime` -> [datetime, "", false].
	 *
	 * @since 0.1.0
	 *
	 * @param string $column_type The raw COLUMN_TYPE.
	 * @return array{type: string, length: string, unsigned: bool}
	 */
	private function parse_type( string $column_type ): array {
		$type     = $column_type;
		$length   = '';
		$scale    = '';
		$unsigned = false;

		if ( false !== stripos( $column_type, 'unsigned' ) ) {
			$unsigned = true;
		}

		if ( preg_match( '/^([a-z]+)\s*(?:\(([^)]+)\))?/i', $column_type, $m ) ) {
			$type = strtolower( $m[1] );
			$args = $m[2] ?? '';
			$comma = strpos( $args, ',' );

			// Only decimal-family types split their args into precision,scale; an
			// enum/set value list also contains commas but is not a length.
			if ( ( false !== $comma ) && in_array( $type, array( 'decimal', 'numeric', 'float', 'double' ), true ) ) {
				$length = substr( $args, 0, $comma );
				$scale  = substr( $args, $comma + 1 );
			} else {
				$length = $args;
			}
		}

		return array(
			'type'     => $type,
			'length'   => $length,
			'scale'    => $scale,
			'unsigned' => $unsigned,
		);
	}

	/**
	 * Whether a BerlinDB column type is numeric (and thus carries an unsigned flag).
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Lowercased BerlinDB type.
	 * @return bool
	 */
	private function is_numeric_type( string $type ): bool {
		return in_array(
			$type,
			array( 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double', 'numeric', 'bit' ),
			true
		);
	}

	/**
	 * Whether a type is a large-object type (TEXT/BLOB/JSON/GEOMETRY) that cannot carry
	 * a DEFAULT in MySQL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Lowercased BerlinDB type.
	 * @return bool
	 */
	private function is_lob_type( string $type ): bool {
		return in_array(
			$type,
			array( 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'json', 'geometry' ),
			true
		);
	}

	/**
	 * Escape a value for embedding in a single-quoted PHP string literal.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Raw value.
	 * @return string Escaped value.
	 */
	private function esc( string $value ): string {
		return str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value );
	}
}

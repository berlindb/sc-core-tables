<?php
/**
 * GENERATED FILE - do not edit by hand.
 *
 * Introspected from the live EDD table `wp_sc_discounts` (structure only).
 * Regenerate with `bin/generate-schemas.php`.
 *
 * @package SCCoreTables\Schemas
 */

declare( strict_types = 1 );

namespace SCCoreTables\Schemas;

use BerlinDB\Database\Kern\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * @since 0.1.0
 */
class Discounts extends Schema {

	/** @var array<int, array<string, mixed>> */
	public $columns = array(
			array( 'name' => 'id', 'type' => 'bigint', 'unsigned' => true, 'extra' => 'auto_increment', 'primary' => true ),
			array( 'name' => 'code', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
			array( 'name' => 'code_key', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
			array( 'name' => 'name', 'type' => 'varchar', 'length' => '255', 'default' => '' ),
			array( 'name' => 'amount', 'type' => 'decimal', 'length' => '18', 'scale' => '9', 'unsigned' => false, 'default' => '0.000000000' ),
			array( 'name' => 'type', 'type' => 'varchar', 'length' => '20', 'default' => 'percentage' ),
			array( 'name' => 'start_date', 'type' => 'date', 'allow_null' => true, 'default' => null ),
			array( 'name' => 'end_date', 'type' => 'date', 'allow_null' => true, 'default' => null ),
			array( 'name' => 'usage_limit', 'type' => 'int', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'paused', 'type' => 'tinyint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'date_created', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'date_modified', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'uuid', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
	);

	/** @var array<int, array<string, mixed>> */
	public $indexes = array(
			array( 'type' => 'key', 'name' => 'code_key', 'columns' => array( 'code_key' ) ),
			array( 'type' => 'primary', 'columns' => array( 'id' ) ),
	);
}

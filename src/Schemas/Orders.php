<?php
/**
 * GENERATED FILE - do not edit by hand.
 *
 * Introspected from the live EDD table `wp_sc_orders` (structure only).
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
class Orders extends Schema {

	/** @var array<int, array<string, mixed>> */
	public $columns = array(
			array( 'name' => 'id', 'type' => 'bigint', 'unsigned' => true, 'extra' => 'auto_increment', 'primary' => true ),
			array( 'name' => 'transaction_id', 'type' => 'varchar', 'length' => '100', 'allow_null' => true, 'default' => null ),
			array( 'name' => 'status', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'currency', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'discount_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'email', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
			array( 'name' => 'first_name', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'last_name', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'subtotal', 'type' => 'decimal', 'length' => '18', 'scale' => '9', 'unsigned' => false, 'default' => '0.000000000' ),
			array( 'name' => 'discount', 'type' => 'decimal', 'length' => '18', 'scale' => '9', 'unsigned' => false, 'default' => '0.000000000' ),
			array( 'name' => 'tax', 'type' => 'decimal', 'length' => '18', 'scale' => '9', 'unsigned' => false, 'default' => '0.000000000' ),
			array( 'name' => 'total', 'type' => 'decimal', 'length' => '18', 'scale' => '9', 'unsigned' => false, 'default' => '0.000000000' ),
			array( 'name' => 'event_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'occurrence_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'event_date', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'checkout_type', 'type' => 'varchar', 'length' => '20', 'default' => 'core' ),
			array( 'name' => 'checkout_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'date_created', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'date_modified', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'date_paid', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'uuid', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
			array( 'name' => 'gateway', 'type' => 'varchar', 'length' => '20', 'default' => 'stripe' ),
	);

	/** @var array<int, array<string, mixed>> */
	public $indexes = array(
			array( 'type' => 'key', 'name' => 'email', 'columns' => array( 'email' ) ),
			array( 'type' => 'primary', 'columns' => array( 'id' ) ),
			array( 'type' => 'unique', 'name' => 'transaction_id', 'columns' => array( 'transaction_id' ) ),
	);
}

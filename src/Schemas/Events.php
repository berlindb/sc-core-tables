<?php
/**
 * GENERATED FILE - do not edit by hand.
 *
 * Introspected from the live EDD table `wp_sc_events` (structure only).
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
class Events extends Schema {

	/** @var array<int, array<string, mixed>> */
	public $columns = array(
			array( 'name' => 'id', 'type' => 'bigint', 'unsigned' => true, 'extra' => 'auto_increment', 'primary' => true ),
			array( 'name' => 'object_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'object_type', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'object_subtype', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'title', 'type' => 'text' ),
			array( 'name' => 'content', 'type' => 'longtext' ),
			array( 'name' => 'status', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'start', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'start_tz', 'type' => 'varchar', 'length' => '155', 'default' => '' ),
			array( 'name' => 'end', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'end_tz', 'type' => 'varchar', 'length' => '155', 'default' => '' ),
			array( 'name' => 'all_day', 'type' => 'tinyint', 'length' => '1', 'unsigned' => false, 'default' => '0' ),
			array( 'name' => 'recurrence', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'recurrence_interval', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'recurrence_count', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
			array( 'name' => 'recurrence_end', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'recurrence_end_tz', 'type' => 'varchar', 'length' => '20', 'default' => '' ),
			array( 'name' => 'date_created', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'date_modified', 'type' => 'datetime', 'default' => '0000-00-00 00:00:00' ),
			array( 'name' => 'uuid', 'type' => 'varchar', 'length' => '100', 'default' => '' ),
			array( 'name' => 'venue_id', 'type' => 'bigint', 'unsigned' => true, 'default' => '0' ),
	);

	/** @var array<int, array<string, mixed>> */
	public $indexes = array(
			array( 'type' => 'key', 'name' => 'event_recur', 'columns' => array( 'recurrence' ) ),
			array( 'type' => 'key', 'name' => 'event_recur_times', 'columns' => array( 'recurrence_end', 'recurrence_end_tz' ) ),
			array( 'type' => 'key', 'name' => 'event_status', 'columns' => array( 'status' ) ),
			array( 'type' => 'key', 'name' => 'event_times', 'columns' => array( 'start', 'end', 'start_tz(50)', 'end_tz(50)' ) ),
			array( 'type' => 'key', 'name' => 'event_venue', 'columns' => array( 'venue_id' ) ),
			array( 'type' => 'key', 'name' => 'object', 'columns' => array( 'object_id', 'object_type', 'object_subtype' ) ),
			array( 'type' => 'primary', 'columns' => array( 'id' ) ),
	);
}

<?php
/**
 * Custom order table schema.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create (or upgrade) the {$wpdb->prefix}woc_orders table on plugin activation.
 */
function wpc_create_order_table() {
	global $wpdb;

	$table   = $wpdb->prefix . 'woc_orders';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT UNSIGNED NOT NULL,
        customer_name VARCHAR(200),
        total DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'processing',
        workflow_status VARCHAR(20) DEFAULT 'new',
        priority VARCHAR(10) DEFAULT 'normal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY order_id (order_id),
        KEY workflow_status (workflow_status),
        KEY priority (priority)
    ) $charset;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}


/**
 * Re-run the table schema after a plugin update.
 *
 * The activation hook does not fire on updates, so sites updating from a release
 * without the workflow columns would otherwise never get them.
 */
function wpc_maybe_upgrade_order_table() {
	$db_version = '5.1.0';

	if ( get_option( 'wpc_db_version' ) === $db_version ) {
		return;
	}

	wpc_create_order_table();
	update_option( 'wpc_db_version', $db_version );
}
add_action( 'plugins_loaded', 'wpc_maybe_upgrade_order_table' );

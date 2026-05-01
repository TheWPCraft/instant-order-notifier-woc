<?php
if (!defined('ABSPATH')) exit;

function wpc_create_order_table(){
    global $wpdb;

    $table = $wpdb->prefix.'woc_orders';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT UNSIGNED NOT NULL,
        customer_name VARCHAR(200),
        total DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'processing',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY order_id (order_id)
    ) $charset;";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

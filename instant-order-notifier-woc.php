<?php
/**
 * Plugin Name: Instant Order Notification for WooCommerce – Get Audio Alert on new Orders
 * Plugin URI:  https://thewpcraft.com/instant-order-notifier-woc
 * Description: Real-time order notification with sound alert, popup, and auto-refresh table for WooCommerce stores.
 * Version:     1.4.2
 * Author:      TheWpCraft
 * Author URI:  https://thewpcraft.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: instant-order-notifier-woc
 * Requires at least: 5.6
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.3
 * Requires Plugins: woocommerce
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPC_WCON_URL', plugin_dir_url( __FILE__ ) );
define( 'WPC_WCON_PATH', plugin_dir_path( __FILE__ ) );


// Add "Settings" link on Plugins page.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpc_order_notification_settings_link' );

/**
 * Prepend a "Settings" link to this plugin's row on the Plugins screen.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function wpc_order_notification_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=woc-order-notification' ) ) . '">' . esc_html__( 'Settings', 'instant-order-notifier-woc' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}


// DB.
require_once WPC_WCON_PATH . 'includes/woc-db.php';
register_activation_hook( __FILE__, 'wpc_create_order_table' );

// Admin menu.
require_once WPC_WCON_PATH . 'includes/woc-admin-menu.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
// WooCommerce hooks.
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'WooCommerce' ) ) {
			require_once WPC_WCON_PATH . 'includes/class-wpc-wcon-hooks.php';
		}
	}
);

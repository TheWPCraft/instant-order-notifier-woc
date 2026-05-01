<<<<<<< HEAD
<?php

/**

 * Plugin Name: Instant Order Notification for WooCommerce – Get Audio Alert on new Orders
 * Plugin URI:  https://thewpcraft.com/instant-order-notifier-woc
 * Description: Real-time order notification with sound alert, popup, and auto-refresh table for WooCommerce stores.
 * Version:     1.3.2
 * Author:      TheWpCraft
 * Author URI:  https://thewpcraft.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: instant-order-notifier-woc
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.9
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

define('WPC_WCON_URL', plugin_dir_url(__FILE__));
define('WPC_WCON_PATH', plugin_dir_path(__FILE__));


// Add "Settings" link on Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpc_order_notification_settings_link');

function wpc_order_notification_settings_link($links)
{
    // Settings link - Recent Orders page પર જાય
    $settings_link = '<a href="' . admin_url('admin.php?page=woc-order-notification') . '">' . __('Settings', 'woc-order-notification') . '</a>';

    array_unshift($links, $settings_link);

    return $links;
}


// DB
require_once WPC_WCON_PATH . 'includes/woc-db.php';
register_activation_hook(__FILE__, 'wpc_create_order_table');

// Admin menu
require_once WPC_WCON_PATH . 'includes/woc-admin-menu.php';

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
// WooCommerce hooks
add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        require_once WPC_WCON_PATH . 'includes/class-woc-hooks.php';
    }
});
=======
<?php

/**

 * Plugin Name: Instant Order Notification for WooCommerce – Get Audio Alert on new Orders
 * Plugin URI:  https://thewpcraft.com/instant-order-notifier-woc
 * Description: Real-time order notification with sound alert, popup, and auto-refresh table for WooCommerce stores.
 * Version:     1.3.2
 * Author:      TheWpCraft
 * Author URI:  https://thewpcraft.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: instant-order-notifier-woc
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.9
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

define('WPC_WCON_URL', plugin_dir_url(__FILE__));
define('WPC_WCON_PATH', plugin_dir_path(__FILE__));


// Add "Settings" link on Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpc_order_notification_settings_link');

function wpc_order_notification_settings_link($links)
{
    // Settings link - Recent Orders page પર જાય
    $settings_link = '<a href="' . admin_url('admin.php?page=woc-order-notification') . '">' . __('Settings', 'woc-order-notification') . '</a>';

    array_unshift($links, $settings_link);

    return $links;
}


// DB
require_once WPC_WCON_PATH . 'includes/woc-db.php';
register_activation_hook(__FILE__, 'wpc_create_order_table');

// Admin menu
require_once WPC_WCON_PATH . 'includes/woc-admin-menu.php';

add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
// WooCommerce hooks
add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        require_once WPC_WCON_PATH . 'includes/class-woc-hooks.php';
    }
});
>>>>>>> 5c7546f462bf6541f40498371819b5c8294c24c2

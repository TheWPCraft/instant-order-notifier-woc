<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'wpc_add_admin_menu');

function wpc_add_admin_menu()
{
    add_menu_page(
        'Order Notifier',
        'Order Notifier',
        'manage_options',
        'woc-order-notification',
        'wpc_recent_orders_page',
        'dashicons-bell',
        26
    );

    add_submenu_page(
        'woc-order-notification',
        'Recent Orders',
        'Recent Orders',
        'manage_options',
        'woc-order-notification',
        'wpc_recent_orders_page'
    );

    add_submenu_page(
        'woc-order-notification',
        'General Settings',
        'General Settings',
        'manage_options',
        'woc-general-settings',
        'wpc_general_settings_page'
    );
}



function wpc_recent_orders_page()
{
    require_once WPC_WCON_PATH . 'includes/sidebar.php';
    require_once WPC_WCON_PATH . 'includes/pages/recent-orders.php';
    require_once WPC_WCON_PATH . 'includes/pages/footer.php';
}

function wpc_general_settings_page()
{
    if (
        isset($_POST['wpc_save_settings']) &&
        check_admin_referer('wpc_save_settings_nonce')
    ) {

        $ringtone = isset($_POST['ringtone'])
            ? sanitize_text_field(wp_unslash($_POST['ringtone']))
            : '1';

        $check_speed = isset($_POST['check_speed'])
            ? sanitize_text_field(wp_unslash($_POST['check_speed']))
            : 'normal';

        $options = [
            'notification_enabled' => isset($_POST['notification_enabled']) ? '1' : '0',
            'desktop_notifications_enabled' => isset($_POST['desktop_notifications_enabled']) ? '1' : '0', // NEW
            'ringtone'             => $ringtone,
            'check_speed'          => $check_speed,
        ];

        update_option('wpc_notification_settings', $options);

        echo '<div class="notice notice-success is-dismissible mt-3 ms-0">
                <p>Settings saved successfully!</p>
              </div>';
    }

    require_once WPC_WCON_PATH . 'includes/sidebar.php';
    require_once WPC_WCON_PATH . 'includes/pages/general-settings.php';
    require_once WPC_WCON_PATH . 'includes/pages/footer.php';
}

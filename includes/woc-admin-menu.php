<?php
if (!defined('ABSPATH')) exit;

/**
 * Normalize phone for settings storage: strip spaces/symbols, keep digits, force +E164 shape.
 *
 * @param string $raw Raw POST value.
 * @return string Empty string if too short; otherwise +digits only.
 */
function wpc_sanitize_e164_phone_setting($raw)
{
    if (!is_string($raw)) {
        return '';
    }

    // Caller should pass wp_unslash() values from $_POST.
    return wpc_normalize_e164_phone($raw);
}

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

    add_submenu_page(
        'woc-order-notification',
        'Advanced Settings',
        'Advanced Settings',
        'manage_options',
        'woc-advanced-settings',
        'wpc_advanced_settings_page'
    );
    add_submenu_page(
        'woc-order-notification',
        'WhatsApp Notification Settings',
        'WhatsApp Notification Settings',
        'manage_options',
        'woc-whatsapp-notification',
        'wpc_whatsapp_notification_page'
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

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to save these settings.', 'instant-order-notifier-woc'));
        }

        $ringtone = isset($_POST['ringtone'])
            ? sanitize_text_field(wp_unslash($_POST['ringtone']))
            : '1';

        $check_speed = isset($_POST['check_speed'])
            ? sanitize_text_field(wp_unslash($_POST['check_speed']))
            : 'normal';

        $existing = get_option('wpc_notification_settings', []);
        if (!is_array($existing)) {
            $existing = [];
        }

        // General only — WhatsApp / Twilio keys stay on Advanced Settings page.
        $options = array_merge(
            $existing,
            [
                'notification_enabled'          => isset($_POST['notification_enabled']) ? '1' : '0',
                'desktop_notifications_enabled' => isset($_POST['desktop_notifications_enabled']) ? '1' : '0',
                'ringtone'                        => $ringtone,
                'check_speed'                     => $check_speed,
            ]
        );

        update_option('wpc_notification_settings', $options);

        echo '<div class="notice notice-success is-dismissible mt-3 ms-0">
                <p>Settings saved successfully!</p>
              </div>';
    }

    require_once WPC_WCON_PATH . 'includes/sidebar.php';
    require_once WPC_WCON_PATH . 'includes/pages/general-settings.php';
    require_once WPC_WCON_PATH . 'includes/pages/footer.php';
}
function wpc_advanced_settings_page()
{

    require_once WPC_WCON_PATH . 'includes/sidebar.php';
    require_once WPC_WCON_PATH . 'includes/pages/advanced-settings.php';
    require_once WPC_WCON_PATH . 'includes/pages/footer.php';
}
function wpc_whatsapp_notification_page()
{
    if (
        isset($_POST['wpc_save_whatsapp_settings']) &&
        check_admin_referer('wpc_save_whatsapp_settings_nonce')
    ) {

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'instant-order-notifier-woc'));
        }

        $whatsapp_enabled     = isset($_POST['whatsapp_enabled']) ? '1' : '0';
        $whatsapp_number      = isset($_POST['whatsapp_number']) 
            ? wpc_sanitize_whatsapp_recipients_setting(wp_unslash($_POST['whatsapp_number'])) 
            : '';
        $twilio_account_sid   = isset($_POST['twilio_account_sid']) 
            ? sanitize_text_field(wp_unslash($_POST['twilio_account_sid'])) 
            : '';
        $twilio_auth_token_in = isset($_POST['twilio_auth_token']) 
            ? sanitize_text_field(wp_unslash($_POST['twilio_auth_token'])) 
            : '';
        $twilio_whatsapp_from = isset($_POST['twilio_whatsapp_from']) 
            ? wpc_sanitize_e164_phone_setting(wp_unslash($_POST['twilio_whatsapp_from'])) 
            : '';

        $existing = get_option('wpc_notification_settings', []);
        if (!is_array($existing)) $existing = [];

        $twilio_auth_token = $twilio_auth_token_in !== '' 
            ? $twilio_auth_token_in 
            : ($existing['twilio_auth_token'] ?? '');

        $options = array_merge($existing, [
            'whatsapp_enabled'     => $whatsapp_enabled,
            'whatsapp_number'      => $whatsapp_number,
            'twilio_account_sid'   => $twilio_account_sid,
            'twilio_auth_token'    => $twilio_auth_token,
            'twilio_whatsapp_from' => $twilio_whatsapp_from,
        ]);

        update_option('wpc_notification_settings', $options);

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    require_once WPC_WCON_PATH . 'includes/sidebar.php';
    require_once WPC_WCON_PATH . 'includes/pages/whatsapp-notification.php';
    require_once WPC_WCON_PATH . 'includes/pages/footer.php';
}
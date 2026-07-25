<?php

/**
 * WPC Order Notification Hooks Class
 *
 * @package WPC_Order_Notification
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WPC_WCON_Hooks
{
    public function __construct()
    {
        add_action('woocommerce_order_status_changed', [$this, 'wpc_save_order_status'], 10, 4);
        add_action('admin_enqueue_scripts', [$this, 'wpc_enqueue_admin_assets']);
        add_action('wp_ajax_wpc_check_order', [$this, 'wpc_ajax_check_order']);
        add_action('wp_ajax_wpc_check_new_order', [$this, 'wpc_ajax_check_new_order']);
        add_action('wp_ajax_wpc_mark_order_seen', [$this, 'wpc_mark_order_seen']);
        add_action('wp_ajax_wpc_get_orders_json', [$this, 'wpc_ajax_get_orders_json']);
        add_action('wp_ajax_wpc_delete_order', [$this, 'wpc_ajax_delete_order']);

        add_action('admin_head', [$this, 'wpc_global_bell_icon_customization']);

        add_action('wp_ajax_wpc_get_dashboard_stats', [$this, 'wpc_get_dashboard_stats']);
        add_action('admin_init', [$this, 'wpc_save_general_settings']);
    }

    /**
     * Add ringing bell animation only on our menu item
     */
    public function wpc_global_bell_icon_customization()
    {
?>
        <style type="text/css">
            li.toplevel_page_woc-order-notification>a>div.wp-menu-image::before {
                animation: wpc-bell-ring 2.2s ease-in-out infinite;
                -webkit-animation: wpc-bell-ring 2.2s ease-in-out infinite;
                transform-origin: center top;
            }

            li.toplevel_page_woc-order-notification.current>a>div.wp-menu-image::before,
            li.toplevel_page_woc-order-notification.wp-menu-open>a>div.wp-menu-image::before {
                animation: wpc-bell-ring 2.2s ease-in-out infinite;
                -webkit-animation: wpc-bell-ring 2.2s ease-in-out infinite;
            }

            @keyframes wpc-bell-ring {
                0% {
                    transform: rotate(0deg);
                }

                10% {
                    transform: rotate(14deg);
                }

                20% {
                    transform: rotate(-12deg);
                }

                30% {
                    transform: rotate(10deg);
                }

                40% {
                    transform: rotate(-8deg);
                }

                50% {
                    transform: rotate(6deg);
                }

                60% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(0deg);
                }
            }
        </style>
<?php
    }

    /**
     * Save/Update order in our custom table when status changes
     */

    public function wpc_save_order_status($order_id, $old_status, $new_status, $order = null)
    {
        // Debug: hook trigger થયો કે નહીં જોવા માટે log
        error_log("[WPC_DEBUG] Order status changed hook fired for order #$order_id | Old: $old_status → New: $new_status");

        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order_id);
        }

        if (!$order || is_wp_error($order)) {
            error_log("[WPC_ERROR] Invalid order object for ID: $order_id");
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        // Table exists છે કે નહીં ચેક (debug માટે)
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            error_log("[WPC_ERROR] Table $table does NOT exist!");
            return;
        }

        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $customer_name = $customer_name ?: __('Guest', 'instant-order-notifier-woc');

        $data = [
            'order_id'      => $order_id,
            'customer_name' => $customer_name,
            'total'         => (float) $order->get_total(),
            'status'        => $new_status,
            'created_at'    => current_time('mysql', true),  // UTC નહીં local time માટે true
        ];

        $format = ['%d', '%s', '%f', '%s', '%s'];

        // Existence check
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE order_id = %d LIMIT 1", $order_id)
        );

        if ($exists) {
            // Update
            $result = $wpdb->update($table, $data, ['order_id' => $order_id], $format, ['%d']);
            if ($result === false) {
                error_log("[WPC_DB_ERROR] Update failed for order #$order_id | " . $wpdb->last_error);
            } else {
                error_log("[WPC_DEBUG] Order #$order_id UPDATED successfully");
            }
        } else {
            // Insert
            $result = $wpdb->insert($table, $data, $format);
            if ($result === false) {
                error_log("[WPC_DB_ERROR] Insert failed for order #$order_id | " . $wpdb->last_error);
            } else {
                error_log("[WPC_DEBUG] Order #$order_id INSERTED successfully (ID: $wpdb->insert_id)");

                // Cache invalidate
                wp_cache_set('wpc_orders_last_changed', microtime(), 'wpc_order_notification');

                update_option('wpc_last_order_time', time(), false);
                update_option('wpc_last_order_id', $order_id, false);
            }
        }

        // Cache key માટે group consistent રાખો
        wp_cache_delete('wpc_order_exists_' . $order_id, 'wpc_order_notification');
    }

    public function wpc_enqueue_admin_assets($hook)
    {
        $allowed_hooks = [
            'toplevel_page_woc-order-notification',
            'order-notifier_page_woc-general-settings',
            'order-notifier_page_woc-advanced-settings',
            'order-notifier_page_woc-whatsapp-notification',
        ];

        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        wp_enqueue_style('bootstrap-css', WPC_WCON_URL . 'assets/bootstrap/css/bootstrap.min.css', [], '5.3.3');
        wp_enqueue_style('bootstrap-icons', WPC_WCON_URL . 'assets/bootstrap/css/bootstrap-icons.min.css', [], '1.11.3');
        wp_enqueue_style('fontawsome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', [], '7.0.1');
        wp_enqueue_style('wpc-admin-css', WPC_WCON_URL . 'assets/css/wpc-admin.css', [], '1.1');

        wp_enqueue_script('wpc-admin-js', WPC_WCON_URL . 'assets/js/wpc-admin.js', ['jquery'], '1.1', true);

        $settings = get_option('wpc_notification_settings', [
            'notification_enabled' => '1',
            'ringtone'             => '1',
            'check_speed'          => 'normal',
        ]);

        wp_localize_script('wpc-admin-js', 'wpcData', [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'audio'            => WPC_WCON_URL . 'assets/audio/notification-1.wav',
            'last_seen_order_id' => (int) get_option('wpc_last_seen_order_id', 0),
            'nonce'            => wp_create_nonce('wpc_nonce'),
            'settings'         => $settings,
            'audio_urls'       => [
                '1' => WPC_WCON_URL . 'assets/audio/notification-1.wav',
                '2' => WPC_WCON_URL . 'assets/audio/notification-2.wav',
                '3' => WPC_WCON_URL . 'assets/audio/notification-3.wav',
                'custom' => !empty($settings['custom_ringtone_url']) ? $settings['custom_ringtone_url'] : '',
                'custom2' => !empty($settings['custom_ringtone_url_2']) ? $settings['custom_ringtone_url_2'] : ''
            ],
        ]);

        wp_enqueue_script('bootstrap-js', WPC_WCON_URL . 'assets/bootstrap/js/bootstrap.min.js', [], '5.3.3', true);
    }

    public function wpc_ajax_check_order()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        $time = (int) get_option('wpc_last_order_time', 0);
        $id   = (int) get_option('wpc_last_order_id', 0);

        wp_send_json_success(['time' => $time, 'id' => $id]);
    }

    public function wpc_ajax_check_new_order()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        $orders = wc_get_orders([
            'limit'   => 1,
            'orderby' => 'date',
            'order'   => 'DESC',
            'status'  => ['processing', 'pending', 'on-hold'],
        ]);

        if (empty($orders)) {
            wp_send_json_error(['message' => 'No new orders']);
            return;
        }

        $order = $orders[0];

        $full_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $full_name = $full_name ?: esc_html__('Guest', 'instant-order-notifier-woc');

        wp_send_json_success([
            'time'  => $order->get_date_created()->getTimestamp(),
            'id'    => $order->get_id(),
            'name'  => $full_name,
            'total' => $order->get_total(),
        ]);
    }

    public function wpc_mark_order_seen()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        update_option('wpc_last_seen_order_id', $order_id, false);

        wp_send_json_success();
    }

    public function wpc_ajax_get_orders_json()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $page     = max(1, isset($_POST['page'])     ? absint($_POST['page'])     : 1);
        $per_page = max(1, isset($_POST['per_page']) ? absint($_POST['per_page']) : 10);
        $offset   = ($page - 1) * $per_page;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $where = "1=1";

        if (!empty($status)) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }

        // Get last changed time to invalid cache when needed
        $last_changed = wp_cache_get('wpc_orders_last_changed', 'wpc_order_notification');
        if (!$last_changed) {
            $last_changed = microtime();
            wp_cache_set('wpc_orders_last_changed', $last_changed, 'wpc_order_notification');
        }

        $cache_key_count = 'wpc_orders_count_' . md5($last_changed . $status);
        $total           = wp_cache_get($cache_key_count, 'wpc_order_notification');

        if (false === $total) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            // $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE $where");
            wp_cache_set($cache_key_count, $total, 'wpc_order_notification');
        }

        $cache_key_items = 'wpc_orders_list_' . md5($last_changed . $page . $per_page . $status);
        $orders          = wp_cache_get($cache_key_items, 'wpc_order_notification');

        if (false === $orders) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $orders = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM {$table} WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
            );
            wp_cache_set($cache_key_items, $orders, 'wpc_order_notification');
        }

        $data = [];
        foreach ($orders as $o) {
            $timestamp = strtotime($o->created_at);
            $formatted_date = $timestamp ? date_i18n('d/m/Y', $timestamp) : '-';

            $data[] = [
                'order_id'      => (int) $o->order_id,
                'customer_name' => $o->customer_name ? esc_html($o->customer_name) : esc_html__('Guest', 'instant-order-notifier-woc'),
                'total'         => wc_price((float) $o->total),
                'status'        => esc_html($o->status),
                'created_at'    => $formatted_date,
                'edit_url'      => esc_url(admin_url('post.php?post=' . absint($o->order_id) . '&action=edit')),
            ];
        }

        wp_send_json_success([
            'orders' => $data,
            'total'  => $total,
            'pages'  => ceil($total / $per_page),
        ]);
    }

    /**
     * AJAX handler - Delete order record from custom table
     */
    public function wpc_ajax_delete_order()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $deleted = $wpdb->delete($table, ['order_id' => $order_id], ['%d']);

        // Invalidate list cache
        if ($deleted !== false && $deleted > 0) {
            //  wp_cache_set('wpc_orders_last_changed', microtime(), 'woc-order-notification');
            wp_cache_set('wpc_orders_last_changed', microtime(), 'wpc_order_notification');
            wp_cache_delete('wpc_order_exists_' . $order_id, 'woc-order-notification');
        }

        if ($deleted !== false && $deleted > 0) {
            wp_send_json_success(['message' => 'Order removed']);
        }

        wp_send_json_error(['message' => 'Failed to remove order']);
    }

    /**
     * Save General Settings + Custom Ringtone Upload + Remove
     */
    public function wpc_save_general_settings()
    {
        if (!isset($_POST['wpc_save_settings'])) {
            return;
        }

        check_admin_referer('wpc_save_settings_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'instant-order-notifier-woc'));
        }

        // ←←← Pehla settings load karo
        $settings = get_option('wpc_notification_settings', [
            'notification_enabled' => '1',
            'ringtone'             => '1',
            'check_speed'          => 'normal',
            'desktop_notifications_enabled' => '0'
        ]);

        // Basic Settings
        $settings['notification_enabled']          = isset($_POST['notification_enabled']) ? '1' : '0';
        $settings['desktop_notifications_enabled'] = isset($_POST['desktop_notifications_enabled']) ? '1' : '0';
        $settings['ringtone']                      = sanitize_text_field($_POST['ringtone'] ?? '1');
        $settings['check_speed']                   = sanitize_text_field($_POST['check_speed'] ?? 'normal');

        // ==================== REMOVE CUSTOM SOUND 1 ====================
        if (isset($_POST['remove_custom_sound']) && $_POST['remove_custom_sound'] == '1') {
            if (!empty($settings['custom_ringtone_url'])) {
                $filename = basename($settings['custom_ringtone_url']);
                $file_path = WPC_WCON_PATH . 'assets/audio/' . $filename;

                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            unset($settings['custom_ringtone_url']);
            if ($settings['ringtone'] === 'custom') $settings['ringtone'] = '1';

            update_option('wpc_notification_settings', $settings);
            add_settings_error('wpc_settings', 'removed', 'Custom sound 1 removed!', 'success');
            return;
        }

        // ==================== REMOVE CUSTOM SOUND 2 ====================
        if (isset($_POST['remove_custom_sound_2']) && $_POST['remove_custom_sound_2'] == '1') {
            if (!empty($settings['custom_ringtone_url_2'])) {
                $filename = basename($settings['custom_ringtone_url_2']);
                $file_path = WPC_WCON_PATH . 'assets/audio/' . $filename;

                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            unset($settings['custom_ringtone_url_2']);
            if ($settings['ringtone'] === 'custom2') $settings['ringtone'] = '1';

            update_option('wpc_notification_settings', $settings);
            add_settings_error('wpc_settings', 'removed', 'Custom sound 2 removed!', 'success');
            return;
        }

        // ==================== UPLOAD CUSTOM SOUNDS (Single Multiple Input) ====================
        if (!empty($_FILES['custom_ringtones']['name'][0])) {
            $files = $_FILES['custom_ringtones'];
            $audio_dir = WPC_WCON_PATH . 'assets/audio/';
            if (!file_exists($audio_dir)) {
                wp_mkdir_p($audio_dir);
            }

            $file_count = count(array_filter($files['name']));

            if ($file_count >= 2) {
                // Upload both files sequentially to Slot 1 and Slot 2
                for ($i = 0; $i < 2; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $slot_key = ($i === 0) ? 'custom_ringtone_url' : 'custom_ringtone_url_2';
                    $ringtone_val = ($i === 0) ? 'custom' : 'custom2';

                    // Delete old file
                    if (!empty($settings[$slot_key])) {
                        $old_file = $audio_dir . basename($settings[$slot_key]);
                        if (file_exists($old_file)) unlink($old_file);
                    }

                    $filename = ($i === 1 ? 'v2-' : '') . time() . '-' . sanitize_file_name($files['name'][$i]);
                    if (move_uploaded_file($files['tmp_name'][$i], $audio_dir . $filename)) {
                        $settings[$slot_key] = WPC_WCON_URL . 'assets/audio/' . $filename;
                        $settings['ringtone'] = $ringtone_val;
                    }
                }
            } else {
                // Upload single file - Intelligent slot assignment
                if ($files['error'][0] === UPLOAD_ERR_OK) {
                    $target_slot = 'custom_ringtone_url';
                    $target_ringtone = 'custom';

                    // If user has selected Slot 2, or Slot 1 is full but Slot 2 is empty, use Slot 2
                    if ($settings['ringtone'] === 'custom2' || (!empty($settings['custom_ringtone_url']) && empty($settings['custom_ringtone_url_2']))) {
                        $target_slot = 'custom_ringtone_url_2';
                        $target_ringtone = 'custom2';
                    }

                    // Delete old file in targeted slot
                    if (!empty($settings[$target_slot])) {
                        $old_file = $audio_dir . basename($settings[$target_slot]);
                        if (file_exists($old_file)) unlink($old_file);
                    }

                    $filename = ($target_slot === 'custom_ringtone_url_2' ? 'v2-' : '') . time() . '-' . sanitize_file_name($files['name'][0]);
                    if (move_uploaded_file($files['tmp_name'][0], $audio_dir . $filename)) {
                        $settings[$target_slot] = WPC_WCON_URL . 'assets/audio/' . $filename;
                        $settings['ringtone'] = $target_ringtone;
                    }
                }
            }
        }

        update_option('wpc_notification_settings', $settings);
    }

    public function wpc_get_dashboard_stats()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $today = date('Y-m-d');

        // Today orders
        $today_orders = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", $today)
        );

        // Status wise count (today only)
        $processing = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'processing', $today)
        );

        $completed = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'completed', $today)
        );

        $cancelled = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'cancelled', $today)
        );

        wp_send_json_success([
            'today'      => (int) $today_orders,
            'processing' => (int) $processing,
            'completed'  => (int) $completed,
            'cancelled'  => (int) $cancelled,
        ]);
    }
}

// Initialize
new WPC_WCON_Hooks();

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

        add_action('wp_ajax_wpc_get_workflow_queue', [$this, 'wpc_ajax_get_workflow_queue']);
        add_action('wp_ajax_wpc_update_workflow_status', [$this, 'wpc_ajax_update_workflow_status']);
        add_action('wp_ajax_wpc_get_next_order', [$this, 'wpc_ajax_get_next_order']);
        add_action('wp_ajax_wpc_get_workflow_stats', [$this, 'wpc_ajax_get_workflow_stats']);
        add_action('wp_ajax_wpc_bulk_update_workflow_status', [$this, 'wpc_ajax_bulk_update_workflow_status']);
        add_action('wp_ajax_wpc_get_order_details', [$this, 'wpc_ajax_get_order_details']);
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
            'order-notifier_page_woc-order-workflow',
            'order-notifier_page_woc-general-settings',
            'order-notifier_page_woc-advanced-settings',
            'order-notifier_page_woc-whatsapp-notification',
        ];

        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-css',
            WPC_WCON_URL . 'assets/bootstrap/css/bootstrap.min.css',
            [],
            '5.3.3'
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'bootstrap-icons',
            WPC_WCON_URL . 'assets/bootstrap/css/bootstrap-icons.min.css',
            [],
            '1.11.3'
        );

        // Font Awesome
        wp_enqueue_style(
            'fontawsome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css',
            [],
            '7.0.1'
        );

        // Plugin Admin CSS
        wp_enqueue_style(
            'wpc-admin-css',
            WPC_WCON_URL . 'assets/css/wpc-admin.css',
            [],
            '1.2'
        );

        // Plugin settings
        $settings = get_option(
            'wpc_notification_settings',
            [
                'notification_enabled' => '1',
                'ringtone'             => '1',
                'check_speed'          => 'normal',
            ]
        );

        /*
     * Common data for admin JS and workflow JS
     */
        $wpcData = [
            'ajax_url'          => admin_url('admin-ajax.php'),
            'audio'             => WPC_WCON_URL . 'assets/audio/notification-1.wav',
            'last_seen_order_id' => (int) get_option('wpc_last_seen_order_id', 0),
            'nonce'             => wp_create_nonce('wpc_nonce'),
            'settings'          => $settings,

            'audio_urls' => [
                '1'      => WPC_WCON_URL . 'assets/audio/notification-1.wav',
                '2'      => WPC_WCON_URL . 'assets/audio/notification-2.wav',
                '3'      => WPC_WCON_URL . 'assets/audio/notification-3.wav',
                'custom' => !empty($settings['custom_ringtone_url'])
                    ? $settings['custom_ringtone_url']
                    : '',
                'custom2' => !empty($settings['custom_ringtone_url_2'])
                    ? $settings['custom_ringtone_url_2']
                    : '',
            ],
        ];

        /*
     * Bootstrap JS
     * Required on admin pages where plugin assets are loaded.
     */
        wp_enqueue_script(
            'bootstrap-js',
            WPC_WCON_URL . 'assets/bootstrap/js/bootstrap.min.js',
            [],
            '5.3.3',
            true
        );

        /*
     * Workflow assets
     * Load ONLY on Workflow page.
     */
        $is_workflow_page = ('order-notifier_page_woc-order-workflow' === $hook);

        if ($is_workflow_page) {

            wp_enqueue_style(
                'wpc-workflow-css',
                WPC_WCON_URL . 'assets/css/wpc-workflow.css',
                [],
                '1.2'
            );

            wp_enqueue_script(
                'wpc-workflow-js',
                WPC_WCON_URL . 'assets/js/wpc-workflow.js',
                ['jquery', 'bootstrap-js'],
                '1.3',
                true
            );

            /*
         * Pass wpcData to workflow JS
         */
            wp_localize_script(
                'wpc-workflow-js',
                'wpcData',
                $wpcData
            );
        }

        /*
     * Advanced Settings page
     */
        $is_advanced_page = ('order-notifier_page_woc-advanced-settings' === $hook);

        if ($is_advanced_page && class_exists('WooCommerce')) {

            wp_enqueue_style('woocommerce_admin_styles');

            wp_enqueue_script('selectWoo');

            wp_enqueue_style('select2');

            wp_enqueue_script(
                'wpc-advanced-settings-js',
                WPC_WCON_URL . 'assets/js/wpc-advanced-settings.js',
                ['jquery', 'selectWoo'],
                '1.2',
                true
            );

            wp_localize_script(
                'wpc-advanced-settings-js',
                'wpcAdvancedData',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('wpc_advanced_nonce'),
                ]
            );
        }

        /*
     * Main Admin JS
     */
        wp_enqueue_script(
            'wpc-admin-js',
            WPC_WCON_URL . 'assets/js/wpc-admin.js',
            ['jquery', 'bootstrap-js'],
            '1.4',
            true
        );

        /*
     * Pass wpcData to main admin JS
     */
        wp_localize_script(
            'wpc-admin-js',
            'wpcData',
            $wpcData
        );
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

        $uploaded = false;

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
                        $uploaded = true;
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
                        $uploaded = true;
                    }
                }
            }
        }

        update_option('wpc_notification_settings', $settings);

        if ($uploaded) {
            /**
             * Fires when a custom ringtone was uploaded and selected during this request,
             * so the General Settings page keeps that selection instead of the submitted radio.
             */
            do_action('wpc_custom_ringtone_uploaded');
        }
    }

    public function wpc_ajax_get_workflow_queue()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $is_pro = (function_exists('wpc_fs') && wpc_fs()->can_use_premium_code());
        $status_filter = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $date_filter = isset($_POST['date_filter']) ? sanitize_text_field($_POST['date_filter']) : 'today';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'oldest';

        $where = "workflow_status != 'completed'";

        if ($date_filter === 'today') {
            $today = current_time('Y-m-d');
            $where .= $wpdb->prepare(" AND DATE(created_at) = %s", $today);
        }

        if ($status_filter === 'high_priority') {
            $where .= " AND priority = 'high'";
        } elseif (!empty($status_filter)) {
            $where .= $wpdb->prepare(" AND workflow_status = %s", $status_filter);
        }

        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (order_id LIKE %s OR customer_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $total_active = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status != 'completed'");

        // Ordering: Priority first, then age
        $age_order = ($sort === 'newest') ? 'DESC' : 'ASC';
        $order_by = "CASE
        WHEN priority = 'high' THEN 1
        WHEN priority = 'medium' THEN 2
        ELSE 3 END ASC, created_at $age_order";

        // LITE VERSION: limit = 5 (Pro માં 1000)
        $limit = $is_pro ? 1000 : 5;

        $orders = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY $order_by LIMIT $limit");

        $data = [];
        foreach ($orders as $o) {
            $wc_order = wc_get_order($o->order_id);
            $data[] = [
                'id' => $o->order_id,
                'customer_name' => $o->customer_name,
                'total' => wc_price($o->total),
                'status' => $o->status,
                'workflow_status' => $o->workflow_status,
                'priority' => $o->priority,
                'created_at' => $o->created_at,
                'human_time' => human_time_diff(strtotime($o->created_at), current_time('timestamp', true)) . ' ago',
                'edit_url' => admin_url('post.php?post=' . $o->order_id . '&action=edit'),
                'wc_status_label' => $wc_order ? wc_get_order_status_name($wc_order->get_status()) : $o->status
            ];
        }

        wp_send_json_success([
            'orders' => $data,
            'total_active' => $total_active,
            'is_pro' => $is_pro,
            'limit' => $limit
        ]);
    }

    public function wpc_ajax_update_workflow_status()
    {
        check_ajax_referer('wpc_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $order_id = absint($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['workflow_status']);

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $wpdb->update($table, ['workflow_status' => $new_status], ['order_id' => $order_id]);

        // Sync with WooCommerce status if workflow is 'completed'
        if ($new_status === 'completed') {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('completed', __('Order completed via Workflow Queue.', 'instant-order-notifier-woc'));
            }
        }

        wp_send_json_success(['next_status' => $new_status]);
    }

    public function wpc_ajax_get_next_order()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $is_pro = (function_exists('wpc_fs') && wpc_fs()->can_use_premium_code());
        $limit = $is_pro ? 1000 : 5;
        $status_filter = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'newest';
        $date_filter = isset($_POST['date_filter']) ? sanitize_text_field($_POST['date_filter']) : 'today';
        $exclude_id = isset($_POST['exclude_id']) ? absint($_POST['exclude_id']) : 0;

        // Same ordering logic as queue
        $age_order = ($sort === 'newest') ? 'DESC' : 'ASC';
        $order_by = "CASE
            WHEN priority = 'high' THEN 1
            WHEN priority = 'medium' THEN 2
            ELSE 3 END ASC, created_at $age_order";

        // Build where clause - matching the main queue logic
        $where = "(workflow_status != 'completed'";
        if ($exclude_id) {
            $where .= $wpdb->prepare(" OR order_id = %d", $exclude_id);
        }
        $where .= ")";

        if ($date_filter === 'today') {
            $today = current_time('Y-m-d');
            $where .= $wpdb->prepare(" AND DATE(created_at) = %s", $today);
        }

        if ($status_filter === 'high_priority') {
            $where .= " AND priority = 'high'";
        } elseif (!empty($status_filter)) {
            $where .= $wpdb->prepare(" AND workflow_status = %s", $status_filter);
        }

        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (order_id LIKE %s OR customer_name LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Fetch all active IDs in current sort and filters
        $all_ids = $wpdb->get_col("SELECT order_id FROM $table WHERE $where ORDER BY $order_by LIMIT $limit");

        if (empty($all_ids)) {
            wp_send_json_error(['message' => 'No orders left']);
        }

        $next_id = 0;
        if ($exclude_id === 0) {
            $next_id = $all_ids[0];
        } else {
            $current_index = array_search($exclude_id, $all_ids);
            if ($current_index !== false && isset($all_ids[$current_index + 1])) {
                $next_id = $all_ids[$current_index + 1];
            }
        }

        if ($next_id) {
            wp_send_json_success(['order_id' => $next_id]);
        } else {
            wp_send_json_error(['message' => 'No more orders in current queue']);
        }
    }

    public function wpc_ajax_get_workflow_stats()
    {
        check_ajax_referer('wpc_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';
        $date_filter = isset($_POST['date_filter']) ? sanitize_text_field($_POST['date_filter']) : 'today';

        $where = "1=1";
        if ($date_filter === 'today') {
            $today = current_time('Y-m-d');
            $where = $wpdb->prepare("DATE(created_at) = %s", $today);
        }

        $stats = [
            'new' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status = 'new' AND $where"),
            'need_attention' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status = 'new' AND priority = 'high' AND $where"),
            'processing' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status = 'processing' AND $where"),
            'ready' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status = 'ready' AND $where"),
            'total_active' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE workflow_status != 'completed' AND $where"),
        ];

        wp_send_json_success($stats);
    }

    public function wpc_ajax_bulk_update_workflow_status()
    {
        check_ajax_referer('wpc_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied']);

        $is_pro = (function_exists('wpc_fs') && wpc_fs()->can_use_premium_code());
        if (!$is_pro) {
            wp_send_json_error(['message' => 'Bulk actions are a Pro feature. Please upgrade to use this functionality.', 'pro_required' => true]);
        }

        $order_ids = isset($_POST['order_ids']) ? array_map('absint', (array) $_POST['order_ids']) : [];
        $new_status = isset($_POST['workflow_status']) ? sanitize_text_field($_POST['workflow_status']) : '';

        if (empty($order_ids) || empty($new_status)) {
            wp_send_json_error(['message' => 'Missing data']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';

        $placeholders = array_fill(0, count($order_ids), '%d');
        $format = implode(',', $placeholders);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare("UPDATE $table SET workflow_status = %s WHERE order_id IN ($format)", array_merge([$new_status], $order_ids));
        $wpdb->query($query);

        // Sync with WooCommerce status for bulk 'completed'
        if ($new_status === 'completed') {
            foreach ($order_ids as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status('completed', __('Order completed via Bulk Workflow action.', 'instant-order-notifier-woc'));
                }
            }
        }

        wp_send_json_success(['message' => sprintf('%d orders updated.', count($order_ids))]);
    }

    public function wpc_ajax_get_order_details()
    {
        check_ajax_referer('wpc_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied']);

        $order_id = absint($_POST['order_id']);
        if (!$order_id) wp_send_json_error(['message' => 'Invalid order ID']);

        $order = wc_get_order($order_id);
        if (!$order) wp_send_json_error(['message' => 'Order not found']);

        global $wpdb;
        $table = $wpdb->prefix . 'woc_orders';
        $woc_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE order_id = %d", $order_id));

        if (!$woc_order) wp_send_json_error(['message' => 'Order metadata not found']);

        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $items[] = [
                'name'     => $item->get_name(),
                'qty'      => $item->get_quantity(),
                'subtotal' => wc_price($item->get_subtotal()),
            ];
        }

        $data = [
            'id'              => $order_id,
            'customer_name'   => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'customer_email'  => $order->get_billing_email(),
            'customer_phone'  => $order->get_billing_phone(),
            'total'           => wc_price($order->get_total()),
            'items'           => $items,
            'status'          => $order->get_status(),
            'wc_status_label' => wc_get_order_status_name($order->get_status()),
            'workflow_status' => $woc_order->workflow_status,
            'priority'        => $woc_order->priority,
            'edit_url'        => admin_url('post.php?post=' . $order_id . '&action=edit'),
            'date'            => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order->get_date_created()))
        ];

        wp_send_json_success($data);
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

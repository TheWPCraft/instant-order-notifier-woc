<?php
/**
 * WPC Order Notification Hooks Class
 *
 * @package Instant_Order_Notifier_Woc
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers WooCommerce/admin hooks and the plugin's AJAX endpoints.
 */
class WPC_WCON_Hooks {

	/**
	 * Wire up all WordPress/WooCommerce hooks used by this plugin.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', [ $this, 'wpc_save_order_status' ], 10, 4 );
		add_action( 'admin_enqueue_scripts', [ $this, 'wpc_enqueue_admin_assets' ] );
		add_action( 'wp_ajax_wpc_check_order', [ $this, 'wpc_ajax_check_order' ] );
		add_action( 'wp_ajax_wpc_check_new_order', [ $this, 'wpc_ajax_check_new_order' ] );
		add_action( 'wp_ajax_wpc_mark_order_seen', [ $this, 'wpc_mark_order_seen' ] );
		add_action( 'wp_ajax_wpc_get_orders_json', [ $this, 'wpc_ajax_get_orders_json' ] );
		add_action( 'wp_ajax_wpc_delete_order', [ $this, 'wpc_ajax_delete_order' ] );

		add_action( 'admin_head', [ $this, 'wpc_global_bell_icon_customization' ] );

		add_action( 'wp_ajax_wpc_get_dashboard_stats', [ $this, 'wpc_get_dashboard_stats' ] );
	}

	/**
	 * Add ringing bell animation only on our menu item
	 */
	public function wpc_global_bell_icon_customization() {
		?>
		<style type="text/css">
			li.toplevel_page_woc-order-notification > a > div.wp-menu-image::before {
				animation: wpc-bell-ring 2.2s ease-in-out infinite;
				-webkit-animation: wpc-bell-ring 2.2s ease-in-out infinite;
				transform-origin: center top;
			}

			li.toplevel_page_woc-order-notification.current > a > div.wp-menu-image::before,
			li.toplevel_page_woc-order-notification.wp-menu-open > a > div.wp-menu-image::before {
				animation: wpc-bell-ring 2.2s ease-in-out infinite;
				-webkit-animation: wpc-bell-ring 2.2s ease-in-out infinite;
			}

			@keyframes wpc-bell-ring {
				0%   { transform: rotate(0deg); }
				10%  { transform: rotate(14deg); }
				20%  { transform: rotate(-12deg); }
				30%  { transform: rotate(10deg); }
				40%  { transform: rotate(-8deg); }
				50%  { transform: rotate(6deg); }
				60%  { transform: rotate(0deg); }
				100% { transform: rotate(0deg); }
			}
		</style>
		<?php
	}

	/**
	 * Save/Update order in our custom table when status changes.
	 *
	 * @param int           $order_id   Order ID.
	 * @param string        $old_status Previous order status.
	 * @param string        $new_status New order status.
	 * @param WC_Order|null $order    Order object, when supplied by the hook.
	 */
	public function wpc_save_order_status( $order_id, $old_status, $new_status, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order || is_wp_error( $order ) ) {
			$this->wpc_debug_log( "Invalid order object for ID: $order_id" );
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix, not user input; a one-off startup check, not worth caching.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->wpc_debug_log( "Table $table does NOT exist!" );
			return;
		}

		$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$customer_name = '' !== $customer_name ? $customer_name : __( 'Guest', 'instant-order-notifier-woc' );

		$data = [
			'order_id'      => $order_id,
			'customer_name' => $customer_name,
			'total'         => (float) $order->get_total(),
			'status'        => $new_status,
			'created_at'    => current_time( 'mysql', true ), // GMT/UTC timestamp, not site local time.
		];

		$format = [ '%d', '%s', '%f', '%s', '%s' ];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; single existence check, not worth caching; $table is derived from $wpdb->prefix, not user input.
		$exists = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is derived from $wpdb->prefix, not user input.
			$wpdb->prepare( "SELECT id FROM $table WHERE order_id = %d LIMIT 1", $order_id )
		);

		if ( $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom plugin table; $wpdb->update() parameterizes internally.
			$result = $wpdb->update( $table, $data, [ 'order_id' => $order_id ], $format, [ '%d' ] );
			if ( false === $result ) {
				$this->wpc_debug_log( "Update failed for order #$order_id | " . $wpdb->last_error );
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom plugin table; $wpdb->insert() parameterizes internally.
			$result = $wpdb->insert( $table, $data, $format );
			if ( false === $result ) {
				$this->wpc_debug_log( "Insert failed for order #$order_id | " . $wpdb->last_error );
			} else {
				// Cache invalidate.
				wp_cache_set( 'wpc_orders_last_changed', microtime(), 'wpc_order_notification' );

				update_option( 'wpc_last_order_time', time(), false );
				update_option( 'wpc_last_order_id', $order_id, false );
			}
		}

		// Keep cache group name consistent with the rest of the class.
		wp_cache_delete( 'wpc_order_exists_' . $order_id, 'wpc_order_notification' );
	}

	/**
	 * Log a message only when WP_DEBUG is enabled, so production sites
	 * never leak order data into the server error log by default.
	 *
	 * @param string $message Message to log.
	 */
	private function wpc_debug_log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG for troubleshooting only.
			error_log( '[WPC] ' . $message );
		}
	}

	/**
	 * Enqueue admin CSS/JS only on this plugin's own admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function wpc_enqueue_admin_assets( $hook ) {
		$allowed_hooks = [
			'toplevel_page_woc-order-notification',
			'order-notifier_page_woc-general-settings',
			'order-notifier_page_woc-advanced-settings',
			'order-notifier_page_woc-whatsapp-notification',
		];

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style( 'bootstrap-css', WPC_WCON_URL . 'assets/bootstrap/css/bootstrap.min.css', [], '5.3.3' );
		wp_enqueue_style( 'bootstrap-icons', WPC_WCON_URL . 'assets/bootstrap/css/bootstrap-icons.min.css', [], '1.11.3' );
		wp_enqueue_style( 'wpc-admin-css', WPC_WCON_URL . 'assets/css/wpc-admin.css', [], '1.2' );

		wp_enqueue_script( 'wpc-admin-js', WPC_WCON_URL . 'assets/js/wpc-admin.js', [ 'jquery' ], '1.2', true );

		$stored_settings = get_option(
			'wpc_notification_settings',
			[
				'notification_enabled' => '1',
				'ringtone'             => '1',
				'check_speed'          => 'normal',
			]
		);

		if ( ! is_array( $stored_settings ) ) {
			$stored_settings = [];
		}

		// Only pass the fields the browser actually needs — $stored_settings may also
		// hold Twilio/WhatsApp credentials, which must never reach page source/JS.
		$public_settings = [
			'notification_enabled'          => $stored_settings['notification_enabled'] ?? '1',
			'desktop_notifications_enabled' => $stored_settings['desktop_notifications_enabled'] ?? '0',
			'ringtone'                      => $stored_settings['ringtone'] ?? '1',
			'check_speed'                   => $stored_settings['check_speed'] ?? 'normal',
		];

		wp_localize_script(
			'wpc-admin-js',
			'wpcData',
			[
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'audio'              => WPC_WCON_URL . 'assets/audio/notification-1.wav',
				'last_seen_order_id' => (int) get_option( 'wpc_last_seen_order_id', 0 ),
				'nonce'              => wp_create_nonce( 'wpc_nonce' ),
				'settings'           => $public_settings,
				'audio_urls'         => [
					'1' => WPC_WCON_URL . 'assets/audio/notification-1.wav',
					'2' => WPC_WCON_URL . 'assets/audio/notification-2.wav',
					'3' => WPC_WCON_URL . 'assets/audio/notification-3.wav',
				],
			]
		);

		wp_enqueue_script( 'bootstrap-js', WPC_WCON_URL . 'assets/bootstrap/js/bootstrap.min.js', [], '5.3.3', true );
	}

	/**
	 * AJAX handler - Lightweight poll for whether a new order has arrived.
	 */
	public function wpc_ajax_check_order() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		$time = (int) get_option( 'wpc_last_order_time', 0 );
		$id   = (int) get_option( 'wpc_last_order_id', 0 );

		wp_send_json_success(
			[
				'time' => $time,
				'id'   => $id,
			]
		);
	}

	/**
	 * AJAX handler - Fetch details of the most recent new order for the popup.
	 */
	public function wpc_ajax_check_new_order() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		$orders = wc_get_orders(
			[
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => [ 'processing', 'pending', 'on-hold' ],
			]
		);

		if ( empty( $orders ) ) {
			wp_send_json_error( [ 'message' => 'No new orders' ] );
			return;
		}

		$order = $orders[0];

		$full_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$full_name = '' !== $full_name ? $full_name : __( 'Guest', 'instant-order-notifier-woc' );

		wp_send_json_success(
			[
				'time'  => $order->get_date_created()->getTimestamp(),
				'id'    => $order->get_id(),
				// Billing name is customer-supplied at checkout; always escape before it reaches the browser.
				'name'  => esc_html( $full_name ),
				'total' => $order->get_total(),
			]
		);
	}

	/**
	 * AJAX handler - Remember the last order ID the admin has seen the popup for.
	 */
	public function wpc_mark_order_seen() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => 'Invalid order ID' ] );
		}

		update_option( 'wpc_last_seen_order_id', $order_id, false );

		wp_send_json_success();
	}

	/**
	 * AJAX handler - Paginated, status-filterable order list for the Recent Orders table.
	 */
	public function wpc_ajax_get_orders_json() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$page     = max( 1, isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1 );
		$per_page = max( 1, isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10 );
		$offset   = ( $page - 1 ) * $per_page;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		$where = '1=1';

		if ( ! empty( $status ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		// Get last changed time to invalidate cache when needed.
		$last_changed = wp_cache_get( 'wpc_orders_last_changed', 'wpc_order_notification' );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'wpc_orders_last_changed', $last_changed, 'wpc_order_notification' );
		}

		$cache_key_count = 'wpc_orders_count_' . md5( $last_changed . $status );
		$total           = wp_cache_get( $cache_key_count, 'wpc_order_notification' );

		if ( false === $total ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix and $where's dynamic part was already escaped via $wpdb->prepare() above; results are cached below.
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE $where" );
			wp_cache_set( $cache_key_count, $total, 'wpc_order_notification' );
		}

		$cache_key_items = 'wpc_orders_list_' . md5( $last_changed . $page . $per_page . $status );
		$orders          = wp_cache_get( $cache_key_items, 'wpc_order_notification' );

		if ( false === $orders ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; result is cached below via wp_cache_set(); $table/$where are not raw user input.
			$orders = $wpdb->get_results(
				$wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix and $where's dynamic part was already escaped via $wpdb->prepare() above.
					"SELECT * FROM {$table} WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				)
			);
			wp_cache_set( $cache_key_items, $orders, 'wpc_order_notification' );
		}

		$data = [];
		foreach ( $orders as $o ) {
			$timestamp      = strtotime( $o->created_at );
			$formatted_date = $timestamp ? date_i18n( 'd/m/Y', $timestamp ) : '-';

			$data[] = [
				'order_id'      => (int) $o->order_id,
				'customer_name' => $o->customer_name ? esc_html( $o->customer_name ) : esc_html__( 'Guest', 'instant-order-notifier-woc' ),
				'total'         => wc_price( (float) $o->total ),
				'status'        => esc_html( $o->status ),
				'created_at'    => $formatted_date,
				'edit_url'      => esc_url( admin_url( 'post.php?post=' . absint( $o->order_id ) . '&action=edit' ) ),
			];
		}

		wp_send_json_success(
			[
				'orders' => $data,
				'total'  => $total,
				'pages'  => ceil( $total / $per_page ),
			]
		);
	}

	/**
	 * AJAX handler - Delete order record from custom table
	 */
	public function wpc_ajax_delete_order() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ] );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => 'Invalid order ID' ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $order_id is sanitized via absint() above; $wpdb->delete() parameterizes the query internally.
		$deleted = $wpdb->delete( $table, [ 'order_id' => $order_id ], [ '%d' ] );

		// Invalidate list cache.
		if ( false !== $deleted && $deleted > 0 ) {
			wp_cache_set( 'wpc_orders_last_changed', microtime(), 'wpc_order_notification' );
			wp_cache_delete( 'wpc_order_exists_' . $order_id, 'wpc_order_notification' );
		}

		if ( false !== $deleted && $deleted > 0 ) {
			wp_send_json_success( [ 'message' => 'Order removed' ] );
		}

		wp_send_json_error( [ 'message' => 'Failed to remove order' ] );
	}

	/**
	 * AJAX handler - Today's order counts for the dashboard summary cards.
	 */
	public function wpc_get_dashboard_stats() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$today = current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table, single stats fetch per request; $table is derived from $wpdb->prefix, not user input.
		$today_orders = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix, not user input; values are parameterized via $wpdb->prepare().
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", $today )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table, single stats fetch per request; $table is derived from $wpdb->prefix, not user input.
		$processing = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix, not user input; values are parameterized via $wpdb->prepare().
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'processing', $today )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table, single stats fetch per request; $table is derived from $wpdb->prefix, not user input.
		$completed = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix, not user input; values are parameterized via $wpdb->prepare().
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'completed', $today )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table, single stats fetch per request; $table is derived from $wpdb->prefix, not user input.
		$cancelled = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is derived from $wpdb->prefix, not user input; values are parameterized via $wpdb->prepare().
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s AND DATE(created_at) = %s", 'cancelled', $today )
		);

		wp_send_json_success(
			[
				'today'      => (int) $today_orders,
				'processing' => (int) $processing,
				'completed'  => (int) $completed,
				'cancelled'  => (int) $cancelled,
			]
		);
	}
}

// Initialize.
new WPC_WCON_Hooks();
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
		add_action( 'admin_init', [ $this, 'wpc_save_general_settings' ] );

		add_action( 'wp_ajax_wpc_get_workflow_queue', [ $this, 'wpc_ajax_get_workflow_queue' ] );
		add_action( 'wp_ajax_wpc_update_workflow_status', [ $this, 'wpc_ajax_update_workflow_status' ] );
		add_action( 'wp_ajax_wpc_get_next_order', [ $this, 'wpc_ajax_get_next_order' ] );
		add_action( 'wp_ajax_wpc_get_workflow_stats', [ $this, 'wpc_ajax_get_workflow_stats' ] );
		add_action( 'wp_ajax_wpc_bulk_update_workflow_status', [ $this, 'wpc_ajax_bulk_update_workflow_status' ] );
		add_action( 'wp_ajax_wpc_get_order_details', [ $this, 'wpc_ajax_get_order_details' ] );
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
			'order-notifier_page_woc-order-workflow',
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

		wp_enqueue_script( 'wpc-admin-js', WPC_WCON_URL . 'assets/js/wpc-admin.js', [ 'jquery', 'bootstrap-js' ], '1.4', true );

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

		$wpc_data = [
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'audio'              => WPC_WCON_URL . 'assets/audio/notification-1.wav',
			'last_seen_order_id' => (int) get_option( 'wpc_last_seen_order_id', 0 ),
			'nonce'              => wp_create_nonce( 'wpc_nonce' ),
			'settings'           => $public_settings,
			'audio_urls'         => [
				'1'       => WPC_WCON_URL . 'assets/audio/notification-1.wav',
				'2'       => WPC_WCON_URL . 'assets/audio/notification-2.wav',
				'3'       => WPC_WCON_URL . 'assets/audio/notification-3.wav',
				'custom'  => ! empty( $stored_settings['custom_ringtone_url'] ) ? esc_url_raw( $stored_settings['custom_ringtone_url'] ) : '',
				'custom2' => ! empty( $stored_settings['custom_ringtone_url_2'] ) ? esc_url_raw( $stored_settings['custom_ringtone_url_2'] ) : '',
			],
		];

		wp_localize_script( 'wpc-admin-js', 'wpcData', $wpc_data );

		// The Order Workflow page has its own assets, which use the same data.
		if ( 'order-notifier_page_woc-order-workflow' === $hook ) {
			wp_enqueue_style( 'wpc-workflow-css', WPC_WCON_URL . 'assets/css/wpc-workflow.css', [], '1.2' );
			wp_enqueue_script( 'wpc-workflow-js', WPC_WCON_URL . 'assets/js/wpc-workflow.js', [ 'jquery', 'bootstrap-js' ], '1.3', true );
			wp_localize_script( 'wpc-workflow-js', 'wpcData', $wpc_data );
		}

		wp_enqueue_script( 'bootstrap-js', WPC_WCON_URL . 'assets/bootstrap/js/bootstrap.min.js', [], '5.3.3', true );
	}

	/**
	 * AJAX handler - Lightweight poll for whether a new order has arrived.
	 */
	public function wpc_ajax_check_order() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
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
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
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
			wp_send_json_error( [ 'message' => __( 'No new orders', 'instant-order-notifier-woc' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order ID', 'instant-order-notifier-woc' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
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
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ] );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order ID', 'instant-order-notifier-woc' ) ] );
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
			wp_send_json_success( [ 'message' => __( 'Order removed', 'instant-order-notifier-woc' ) ] );
		}

		wp_send_json_error( [ 'message' => __( 'Failed to remove order', 'instant-order-notifier-woc' ) ] );
	}

	/**
	 * Handle custom ringtone uploads and removals from the General Settings form.
	 *
	 * The other General Settings fields are saved by wpc_general_settings_page().
	 * Uploads are limited to audio files (mp3, wav, ogg) of up to 2 MB and are
	 * stored in the uploads folder so they survive plugin updates.
	 */
	public function wpc_save_general_settings() {
		if ( ! isset( $_POST['wpc_save_settings'] ) ) {
			return;
		}

		check_admin_referer( 'wpc_save_settings_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'instant-order-notifier-woc' ) );
		}

		$settings = get_option( 'wpc_notification_settings', [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$slots = [
			'custom'  => [
				'url_key' => 'custom_ringtone_url',
				'remove'  => 'remove_custom_sound',
			],
			'custom2' => [
				'url_key' => 'custom_ringtone_url_2',
				'remove'  => 'remove_custom_sound_2',
			],
		];

		foreach ( $slots as $ringtone => $slot ) {
			if ( isset( $_POST[ $slot['remove'] ] ) && '1' === sanitize_text_field( wp_unslash( $_POST[ $slot['remove'] ] ) ) ) {
				$this->wpc_delete_custom_ringtone( $settings[ $slot['url_key'] ] ?? '' );
				unset( $settings[ $slot['url_key'] ] );
				if ( ( $settings['ringtone'] ?? '' ) === $ringtone ) {
					$settings['ringtone'] = '1';
				}
			}
		}

		$uploaded = false;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		if ( ! empty( $_FILES['custom_ringtones']['name'][0] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above; each file is validated by wp_handle_upload().
			$files = $_FILES['custom_ringtones'];
			$count = min( 2, count( $files['name'] ) );

			for ( $i = 0; $i < $count; $i++ ) {
				if ( UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
					continue;
				}

				// One file goes to the selected slot (or slot 2 when slot 1 is taken); two files fill both.
				if ( 1 === $count ) {
					$use_second = 'custom2' === ( $settings['ringtone'] ?? '' )
						|| ( ! empty( $settings['custom_ringtone_url'] ) && empty( $settings['custom_ringtone_url_2'] ) );
				} else {
					$use_second = 1 === $i;
				}

				$ringtone = $use_second ? 'custom2' : 'custom';
				$url_key  = $slots[ $ringtone ]['url_key'];

				$url = $this->wpc_store_custom_ringtone(
					[
						'name'     => $files['name'][ $i ],
						'type'     => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error'    => $files['error'][ $i ],
						'size'     => $files['size'][ $i ],
					]
				);

				if ( '' === $url ) {
					continue;
				}

				$this->wpc_delete_custom_ringtone( $settings[ $url_key ] ?? '' );
				$settings[ $url_key ] = $url;
				$settings['ringtone'] = $ringtone;
				$uploaded             = true;
			}
		}

		update_option( 'wpc_notification_settings', $settings, false );

		if ( $uploaded ) {
			/**
			 * Fires when a custom ringtone was uploaded and selected during this request,
			 * so the General Settings page keeps that selection instead of the submitted radio.
			 */
			do_action( 'wpc_custom_ringtone_uploaded' );
		}
	}

	/**
	 * Validate an uploaded ringtone and move it into the plugin's uploads folder.
	 *
	 * @param array $file A single entry shaped like an item of $_FILES.
	 * @return string URL of the stored file, or an empty string when rejected.
	 */
	private function wpc_store_custom_ringtone( $file ) {
		if ( $file['size'] > 2 * MB_IN_BYTES ) {
			return '';
		}

		$redirect = static function ( $dirs ) {
			$dirs['subdir'] = '/instant-order-notifier-woc';
			$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
			return $dirs;
		};

		add_filter( 'upload_dir', $redirect );
		$result = wp_handle_upload(
			$file,
			[
				'test_form' => false,
				'mimes'     => [
					'mp3' => 'audio/mpeg',
					'wav' => 'audio/wav',
					'ogg' => 'audio/ogg',
				],
			]
		);
		remove_filter( 'upload_dir', $redirect );

		return isset( $result['url'] ) ? esc_url_raw( $result['url'] ) : '';
	}

	/**
	 * Delete a previously stored custom ringtone, only if it lives in this plugin's uploads folder.
	 *
	 * @param string $url URL saved in the settings.
	 */
	private function wpc_delete_custom_ringtone( $url ) {
		if ( '' === $url ) {
			return;
		}

		$uploads = wp_get_upload_dir();
		$prefix  = trailingslashit( $uploads['baseurl'] ) . 'instant-order-notifier-woc/';

		if ( 0 !== strpos( $url, $prefix ) ) {
			return;
		}

		wp_delete_file( trailingslashit( $uploads['basedir'] ) . 'instant-order-notifier-woc/' . basename( $url ) );
	}

	// The Order Workflow handlers below verify the nonce and capability in wpc_workflow_check_request().
	// phpcs:disable WordPress.Security.NonceVerification.Missing

	/**
	 * Allowed workflow statuses.
	 *
	 * @return string[]
	 */
	private function wpc_workflow_statuses() {
		return [ 'new', 'acknowledged', 'processing', 'ready', 'completed' ];
	}

	/**
	 * Whether the Pro version is active.
	 *
	 * @return bool
	 */
	private function wpc_is_pro() {
		return function_exists( 'wpc_fs' ) && wpc_fs()->can_use_premium_code();
	}

	/**
	 * Verify the nonce and require manage_options for a workflow AJAX request.
	 */
	private function wpc_workflow_check_request() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
		}
	}

	/**
	 * Build the WHERE clause shared by the workflow queue and next-order lookups.
	 *
	 * @param int $include_id Order ID to keep in the result even if completed (0 for none).
	 * @return string SQL fragment; every dynamic value is parameterized via $wpdb->prepare().
	 */
	private function wpc_workflow_where( $include_id = 0 ) {
		global $wpdb;

		$status_filter = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$date_filter   = isset( $_POST['date_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['date_filter'] ) ) : 'today';
		$search        = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$where = "workflow_status != 'completed'";
		if ( $include_id ) {
			$where = $wpdb->prepare( "(workflow_status != 'completed' OR order_id = %d)", $include_id );
		}

		if ( 'today' === $date_filter ) {
			$where .= $wpdb->prepare( ' AND DATE(created_at) = %s', current_time( 'Y-m-d' ) );
		}

		if ( 'high_priority' === $status_filter ) {
			$where .= " AND priority = 'high'";
		} elseif ( in_array( $status_filter, $this->wpc_workflow_statuses(), true ) ) {
			$where .= $wpdb->prepare( ' AND workflow_status = %s', $status_filter );
		}

		if ( '' !== $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare( ' AND (order_id LIKE %s OR customer_name LIKE %s)', $like, $like );
		}

		return $where;
	}

	/**
	 * ORDER BY clause: priority first, then age.
	 *
	 * @return string Static SQL fragment ("ASC"/"DESC" is picked from a whitelist).
	 */
	private function wpc_workflow_order_by() {
		$sort      = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'oldest';
		$age_order = ( 'newest' === $sort ) ? 'DESC' : 'ASC';

		return "CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END ASC, created_at {$age_order}";
	}

	/**
	 * AJAX handler - Active orders for the Order Workflow queue.
	 */
	public function wpc_ajax_get_workflow_queue() {
		$this->wpc_workflow_check_request();

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$where    = $this->wpc_workflow_where();
		$order_by = $this->wpc_workflow_order_by();
		$is_pro   = $this->wpc_is_pro();
		$limit    = $is_pro ? 1000 : 5;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and $where/$order_by are built from prepared or whitelisted fragments.
		$total_active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE workflow_status != 'completed'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and $where/$order_by are built from prepared or whitelisted fragments.
		$orders = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY {$order_by} LIMIT {$limit}" );

		$data = [];
		foreach ( $orders as $o ) {
			$wc_order = wc_get_order( $o->order_id );
			$data[]   = [
				'id'              => absint( $o->order_id ),
				'customer_name'   => $o->customer_name ? esc_html( $o->customer_name ) : esc_html__( 'Guest', 'instant-order-notifier-woc' ),
				'total'           => wc_price( $o->total ),
				'status'          => esc_html( $o->status ),
				'workflow_status' => esc_html( $o->workflow_status ),
				'priority'        => esc_html( $o->priority ),
				'created_at'      => esc_html( $o->created_at ),
				'human_time'      => esc_html(
					sprintf(
						/* translators: %s: human-readable time difference, e.g. "5 mins". */
						__( '%s ago', 'instant-order-notifier-woc' ),
						human_time_diff( strtotime( $o->created_at ), time() )
					)
				),
				'edit_url'        => esc_url( admin_url( 'post.php?post=' . absint( $o->order_id ) . '&action=edit' ) ),
				'wc_status_label' => esc_html( $wc_order ? wc_get_order_status_name( $wc_order->get_status() ) : $o->status ),
			];
		}

		wp_send_json_success(
			[
				'orders'       => $data,
				'total_active' => $total_active,
				'is_pro'       => $is_pro,
				'limit'        => $limit,
			]
		);
	}

	/**
	 * AJAX handler - Move a single order to a new workflow status.
	 */
	public function wpc_ajax_update_workflow_status() {
		$this->wpc_workflow_check_request();

		$order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$new_status = isset( $_POST['workflow_status'] ) ? sanitize_text_field( wp_unslash( $_POST['workflow_status'] ) ) : '';

		if ( ! $order_id || ! in_array( $new_status, $this->wpc_workflow_statuses(), true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request', 'instant-order-notifier-woc' ) ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom plugin table; $wpdb->update() parameterizes the query internally.
		$wpdb->update( $table, [ 'workflow_status' => $new_status ], [ 'order_id' => $order_id ], [ '%s' ], [ '%d' ] );

		// Sync with the WooCommerce status when the workflow is completed.
		if ( 'completed' === $new_status ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_status( 'completed', __( 'Order completed via Workflow Queue.', 'instant-order-notifier-woc' ) );
			}
		}

		wp_send_json_success( [ 'next_status' => $new_status ] );
	}

	/**
	 * AJAX handler - ID of the order that follows the current one in the queue.
	 */
	public function wpc_ajax_get_next_order() {
		$this->wpc_workflow_check_request();

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$exclude_id = isset( $_POST['exclude_id'] ) ? absint( $_POST['exclude_id'] ) : 0;
		$where      = $this->wpc_workflow_where( $exclude_id );
		$order_by   = $this->wpc_workflow_order_by();
		$limit      = $this->wpc_is_pro() ? 1000 : 5;

		// Fetch all active IDs in the current sort and filters.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and $where/$order_by are built from prepared or whitelisted fragments.
		$all_ids = $wpdb->get_col( "SELECT order_id FROM {$table} WHERE {$where} ORDER BY {$order_by} LIMIT {$limit}" );

		if ( empty( $all_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No orders left', 'instant-order-notifier-woc' ) ] );
		}

		$next_id = 0;
		if ( 0 === $exclude_id ) {
			$next_id = absint( $all_ids[0] );
		} else {
			$current_index = array_search( (string) $exclude_id, array_map( 'strval', $all_ids ), true );
			if ( false !== $current_index && isset( $all_ids[ $current_index + 1 ] ) ) {
				$next_id = absint( $all_ids[ $current_index + 1 ] );
			}
		}

		if ( $next_id ) {
			wp_send_json_success( [ 'order_id' => $next_id ] );
		}

		wp_send_json_error( [ 'message' => __( 'No more orders in current queue', 'instant-order-notifier-woc' ) ] );
	}

	/**
	 * AJAX handler - Counts per workflow status for the Order Workflow cards.
	 */
	public function wpc_ajax_get_workflow_stats() {
		$this->wpc_workflow_check_request();

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$date_filter = isset( $_POST['date_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['date_filter'] ) ) : 'today';

		$where = '1=1';
		if ( 'today' === $date_filter ) {
			$where = $wpdb->prepare( 'DATE(created_at) = %s', current_time( 'Y-m-d' ) );
		}

		// One query for all counts.
		$counts = "SUM( workflow_status = 'new' ) AS new_count, SUM( workflow_status = 'new' AND priority = 'high' ) AS need_attention, SUM( workflow_status = 'processing' ) AS processing, SUM( workflow_status = 'ready' ) AS ready, SUM( workflow_status != 'completed' ) AS total_active";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and $where is prepared above.
		$row = $wpdb->get_row( "SELECT {$counts} FROM {$table} WHERE {$where}" );

		wp_send_json_success(
			[
				'new'            => (int) ( $row->new_count ?? 0 ),
				'need_attention' => (int) ( $row->need_attention ?? 0 ),
				'processing'     => (int) ( $row->processing ?? 0 ),
				'ready'          => (int) ( $row->ready ?? 0 ),
				'total_active'   => (int) ( $row->total_active ?? 0 ),
			]
		);
	}

	/**
	 * AJAX handler - Move several orders to a new workflow status (Pro).
	 */
	public function wpc_ajax_bulk_update_workflow_status() {
		$this->wpc_workflow_check_request();

		if ( ! $this->wpc_is_pro() ) {
			wp_send_json_error(
				[
					'message'      => __( 'Bulk actions are a Pro feature. Please upgrade to use this functionality.', 'instant-order-notifier-woc' ),
					'pro_required' => true,
				]
			);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized with absint() below.
		$order_ids  = isset( $_POST['order_ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['order_ids'] ) ) ) : [];
		$new_status = isset( $_POST['workflow_status'] ) ? sanitize_text_field( wp_unslash( $_POST['workflow_status'] ) ) : '';

		if ( empty( $order_ids ) || ! in_array( $new_status, $this->wpc_workflow_statuses(), true ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing data', 'instant-order-notifier-woc' ) ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		$format = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and the IN() list is built from %d placeholders.
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET workflow_status = %s WHERE order_id IN ({$format})", array_merge( [ $new_status ], $order_ids ) ) );

		// Sync with the WooCommerce status for bulk "completed".
		if ( 'completed' === $new_status ) {
			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$order->update_status( 'completed', __( 'Order completed via Bulk Workflow action.', 'instant-order-notifier-woc' ) );
				}
			}
		}

		wp_send_json_success(
			[
				'message' => sprintf(
					/* translators: %d: number of orders updated. */
					_n( '%d order updated.', '%d orders updated.', count( $order_ids ), 'instant-order-notifier-woc' ),
					count( $order_ids )
				),
			]
		);
	}

	/**
	 * AJAX handler - Order details for the Quick Process modal.
	 */
	public function wpc_ajax_get_order_details() {
		$this->wpc_workflow_check_request();

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order ID', 'instant-order-notifier-woc' ) ] );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => __( 'Order not found', 'instant-order-notifier-woc' ) ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'woc_orders';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom plugin table; $table comes from $wpdb->prefix and the value is parameterized via $wpdb->prepare().
		$woc_order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d", $order_id ) );

		if ( ! $woc_order ) {
			wp_send_json_error( [ 'message' => __( 'Order metadata not found', 'instant-order-notifier-woc' ) ] );
		}

		$items = [];
		foreach ( $order->get_items() as $item ) {
			$items[] = [
				'name'     => esc_html( $item->get_name() ),
				'qty'      => (int) $item->get_quantity(),
				'subtotal' => wc_price( $item->get_subtotal() ),
			];
		}

		$date_created = $order->get_date_created();

		wp_send_json_success(
			[
				'id'              => $order_id,
				'customer_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'customer_email'  => $order->get_billing_email(),
				'customer_phone'  => $order->get_billing_phone(),
				'total'           => wc_price( $order->get_total() ),
				'items'           => $items,
				'status'          => $order->get_status(),
				'wc_status_label' => wc_get_order_status_name( $order->get_status() ),
				'workflow_status' => esc_html( $woc_order->workflow_status ),
				'priority'        => esc_html( $woc_order->priority ),
				'edit_url'        => esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ),
				'date'            => $date_created ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date_created->getTimestamp() ) : '',
			]
		);
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing

	/**
	 * AJAX handler - Today's order counts for the dashboard summary cards.
	 */
	public function wpc_get_dashboard_stats() {
		check_ajax_referer( 'wpc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'instant-order-notifier-woc' ) ], 403 );
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
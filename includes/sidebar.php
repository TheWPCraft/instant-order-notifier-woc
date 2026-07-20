<?php
/**
 * Shared admin page sidebar/navigation.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = '';

if ( function_exists( 'get_current_screen' ) ) {
	$screen = get_current_screen();
	if ( ! empty( $screen->base ) ) {
		$current_page = $screen->base;
	}
}

$is_recent   = ( strpos( $current_page, 'woc-order-notification' ) !== false );
$is_settings = ( strpos( $current_page, 'woc-general-settings' ) !== false );
$is_advanced = ( strpos( $current_page, 'woc-advanced-settings' ) !== false );
$is_whatsapp = ( strpos( $current_page, 'woc-whatsapp-notification' ) !== false );

$recent_orders_url     = esc_url( admin_url( 'admin.php?page=woc-order-notification' ) );
$general_settings_url  = esc_url( admin_url( 'admin.php?page=woc-general-settings' ) );
$advanced_settings_url = esc_url( admin_url( 'admin.php?page=woc-advanced-settings' ) );
$whatsapp_settings_url = esc_url( admin_url( 'admin.php?page=woc-whatsapp-notification' ) );
?>
<!-- Page Content Will Be Included Here -->
<main>
	<div class="container-fluid ps-0 py-0">
		<nav class="navbar navbar-expand-sm navbar-light mb-3 shadow-sm py-2">
			<div class="px-2 d-flex justify-content-between align-items-center w-100">
				<a class="navbar-brand py-0" href="#"><?php esc_html_e( 'Woo Order Notification', 'instant-order-notifier-woc' ); ?></a>
				<ul class="navbar-nav ms-auto mt-2 mt-lg-0 gap-2">
					<li class="nav-item">
						<a class="nav-link"
							href="https://mail.google.com/mail/?view=cm&fs=1&to=support@thewpcraft.com&su=Support&body=Hello"
							target="_blank"><?php esc_html_e( 'Support', 'instant-order-notifier-woc' ); ?></a>
					</li>
					<!-- <li class="nav-item">
						<a class="nav-link" href="#">Documentations</a>
					</li> -->
				</ul>
			</div>
		</nav>
	</div>
	<div class="container-fluid ps-0 py-0">
		<div class="d-flex all-main-part ">

			<div class="sidbar-main">
				<div class="p-2">
					<nav class="nav nav-pills flex-column">
						<a class="nav-link mb-3 rounded <?php echo $is_recent ? 'active' : ''; ?>"
							href="<?php echo esc_url( $recent_orders_url ); ?>">
							<span><?php esc_html_e( 'Recent Orders', 'instant-order-notifier-woc' ); ?></span>
						</a>
						<span class="nav-link-main-text"> <?php esc_html_e( 'settings', 'instant-order-notifier-woc' ); ?> </span>
						<a class="nav-link mb-2 rounded <?php echo $is_settings ? 'active' : ''; ?>"
							href="<?php echo esc_url( $general_settings_url ); ?>">
							<span><?php esc_html_e( 'General Settings', 'instant-order-notifier-woc' ); ?></span>
						</a>
						<a class="pro-nav-item nav-link mb-2 rounded <?php echo $is_advanced ? 'active' : ''; ?>"
							href="<?php echo esc_url( $advanced_settings_url ); ?>">
							<span><?php esc_html_e( 'Advanced Settings', 'instant-order-notifier-woc' ); ?></span>
							<i class="bi bi-lock-fill"></i>
						</a>
						<a class="pro-nav-item nav-link mb-2 rounded <?php echo $is_whatsapp ? 'active' : ''; ?>"
							href="<?php echo esc_url( $whatsapp_settings_url ); ?>">
							<span><?php esc_html_e( 'WhatsApp Notification Settings', 'instant-order-notifier-woc' ); ?></span>
							<i class="bi bi-lock-fill"></i>
						</a>
					</nav>
				</div>
			</div>
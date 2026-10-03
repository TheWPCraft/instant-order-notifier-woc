<?php
/**
 * WhatsApp Notification Settings admin page template (Pro upsell in the free version).
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="right-side-main pro-item">
	<div class="pro-btn-icon">
		<div class="pro-icon">
			<i class="bi bi-lock-fill"></i>
		</div>
		<div class="pro-text">
			<h5>Available in Pro Version</h5>
			<p>Upgrade to unlock all premium features.</p>
			<ul>
				<li>Get 10% OFF on Instant Order Notification for WooCommerce Pro.</li>
				<li>Use Coupon: <strong>THEWPCRAFT10</strong></li>
			</ul>
		</div>
		<div class="pro-btn w-100">
			<a href="https://thewpcraft.com/plugins-details/instant-order-notification" target="_blank"
				class="btn btn-theme w-100"><i class="bi bi-cart-fill me-2"></i> Buy Now</a>
		</div>
	</div>
	<div class="card">
		<form method="post">
			<div class="card-header">
				<div class="d-flex align-items-center justify-content-between">
					<h5 class="mb-0">
						<?php esc_html_e( 'WhatsApp Notification Settings', 'instant-order-notifier-woc' ); ?>
					</h5>
					<!-- <button type="submit" name="wpc_save_whatsapp_settings" class="btn btn-theme">
						<i class="bi bi-save me-1"></i>
						<?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
					</button> -->
				</div>
			</div>
			<hr class="my-0">
			<div class="card-body">
				<div class="whatsapp-notification-settings-main">
					<?php wp_nonce_field( 'wpc_save_whatsapp_settings_nonce' ); ?>

					<div class="card">
						<div class="card-body d-flex justify-content-between align-items-center">
							<div>
								<h6 class="card-title mb-1">
									<?php esc_html_e( 'Enable WhatsApp Notifications', 'instant-order-notifier-woc' ); ?>
								</h6>
								<p class="text-muted mb-0 small">
									<?php esc_html_e( 'Uses the WooCommerce “new order” hook — one WhatsApp per order.', 'instant-order-notifier-woc' ); ?>
								</p>
							</div>
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" id="whatsapp_enabled"
									name="whatsapp_enabled">
							</div>
						</div>
					</div>

					<div class="card">
						<div class="card-body">
							<div class="row g-3">
								<div class="col-12 col-md-6">
									<label class="form-label" for="whatsapp_number">
										<?php esc_html_e( 'Receive WhatsApp Alerts On', 'instant-order-notifier-woc' ); ?>
									</label>
									<input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number"
										value="" placeholder="+919876543210, +447123456789" autocomplete="tel">
								</div>
								<div class="col-12 col-md-6">
									<label class="form-label" for="twilio_whatsapp_from">
										<?php esc_html_e( 'Twilio WhatsApp Sender (From) (Optional)', 'instant-order-notifier-woc' ); ?>
									</label>
									<input type="text" class="form-control" id="twilio_whatsapp_from"
										name="twilio_whatsapp_from" value="" placeholder="+14155238886"
										autocomplete="off">
								</div>
								<div class="col-12 col-md-6">
									<label class="form-label" for="twilio_account_sid">
										<?php esc_html_e( 'Twilio Account SID', 'instant-order-notifier-woc' ); ?>
									</label>
									<input type="text" class="form-control" id="twilio_account_sid"
										name="twilio_account_sid" value=""
										placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off">
								</div>
								<div class="col-12 col-md-6">
									<label class="form-label" for="twilio_auth_token">
										<?php esc_html_e( 'Twilio Auth Token', 'instant-order-notifier-woc' ); ?>
									</label>
									<input type="password" class="form-control" id="twilio_auth_token"
										name="twilio_auth_token" value="" placeholder="Enter Your Twilio Auth Token"
										autocomplete="new-password">
								</div>
							</div>

							<div class="alert alert-info mt-3 small">
								<i class="bi bi-info-circle me-1"></i>
								<strong>Sandbox Note:</strong> Send "join" + your sandbox keyword to the Twilio number
								from your WhatsApp before testing.
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- <hr class="my-0">
			<div class="card-footer text-end">
				<button type="submit" name="" class="btn btn-theme">
					<i class="bi bi-save me-2"></i> <?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
				</button>
			</div> -->
		</form>
	</div>
</div>
</div>

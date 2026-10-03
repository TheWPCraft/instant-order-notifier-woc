<?php
/**
 * Advanced Settings admin page template (Pro upsell in the free version).
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
		<form method="post" id="wpc-advanced-rules-form">
			<div class="card-header">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
					<div>
						<h5 class="mb-0"><?php esc_html_e( 'Advanced Settings', 'instant-order-notifier-woc' ); ?></h5>
						<p class="text-muted mb-0 small">
							<?php esc_html_e( 'Configure notification rules — all selected conditions must match (AND logic).', 'instant-order-notifier-woc' ); ?>
						</p>
					</div>
					<button type="submit" name="wpc_save_advanced_rules" class="btn btn-theme">
						<i class="bi bi-save me-1"></i>
						<?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
					</button>
				</div>
			</div>
			<hr class="my-0">
			<div class="card-body">
				<?php wp_nonce_field( 'wpc_save_advanced_rules_nonce' ); ?>

				<!-- Enable Rules -->
				<div class="card mb-3">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<h6 class="card-title mb-1"><?php esc_html_e( 'Enable Rules', 'instant-order-notifier-woc' ); ?></h6>
							<p class="text-muted mb-0 small">
								<?php esc_html_e( 'When disabled, notifications behave as before without rule filtering.', 'instant-order-notifier-woc' ); ?>
							</p>
						</div>
						<div class="form-check form-switch">
							<input class="form-check-input" type="checkbox" id="rules_enabled" name="rules_enabled"
								<?php checked( $rules['enabled'], '1' ); ?>>
						</div>
					</div>
				</div>

				<div id="wpc-rules-sections" class="<?php echo '1' !== $rules['enabled'] ? 'wpc-rules-disabled' : ''; ?>">

					<!-- Product Filters -->
					<div class="card mb-3">
						<div class="card-header bg-transparent">
							<h6 class="mb-0"><?php esc_html_e( 'Product Filters', 'instant-order-notifier-woc' ); ?></h6>
							<p class="text-muted mb-0 small">
								<?php esc_html_e( 'Leave empty to allow all. When set, the order must match each configured filter.', 'instant-order-notifier-woc' ); ?>
							</p>
						</div>
						<hr class="my-0">
						<div class="card-body">
							<div class="row g-3">
								<div class="col-12">
									<label class="form-label" for="included_products">
										<?php esc_html_e( 'Products Included', 'instant-order-notifier-woc' ); ?>
									</label>
									<select class="wpc-select2 form-control" id="included_products" name="included_products[]"
										multiple="multiple" data-type="products" data-placeholder="<?php esc_attr_e( 'Search products…', 'instant-order-notifier-woc' ); ?>">
										<?php foreach ( $product_options as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt['id'] ); ?>" selected="selected">
												<?php echo esc_html( $opt['text'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="col-12 col-md-6">
									<label class="form-label" for="included_categories">
										<?php esc_html_e( 'Product Categories Included', 'instant-order-notifier-woc' ); ?>
									</label>
									<select class="wpc-select2 form-control" id="included_categories" name="included_categories[]"
										multiple="multiple" data-type="categories" data-placeholder="<?php esc_attr_e( 'Search categories…', 'instant-order-notifier-woc' ); ?>">
										<?php foreach ( $category_options as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt['id'] ); ?>" selected="selected">
												<?php echo esc_html( $opt['text'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="col-12 col-md-6">
									<label class="form-label" for="included_tags">
										<?php esc_html_e( 'Product Tags Included', 'instant-order-notifier-woc' ); ?>
									</label>
									<select class="wpc-select2 form-control" id="included_tags" name="included_tags[]"
										multiple="multiple" data-type="tags" data-placeholder="<?php esc_attr_e( 'Search tags…', 'instant-order-notifier-woc' ); ?>">
										<?php foreach ( $tag_options as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt['id'] ); ?>" selected="selected">
												<?php echo esc_html( $opt['text'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Minimum Order Amount -->
					<div class="card mb-3">
						<div class="card-header bg-transparent">
							<h6 class="mb-0"><?php esc_html_e( 'Minimum Order Amount', 'instant-order-notifier-woc' ); ?></h6>
						</div>
						<hr class="my-0">
						<div class="card-body">
							<div class="row g-3 align-items-end">
								<div class="col-12 col-md-4">
									<label class="form-label" for="minimum_order_amount">
										<?php esc_html_e( 'Minimum total', 'instant-order-notifier-woc' ); ?>
									</label>
									<div class="input-group">
										<span class="input-group-text"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
										<input type="number" class="form-control" id="minimum_order_amount"
											name="minimum_order_amount" step="0.01" min="0"
											value="<?php echo esc_attr( $rules['minimum_order_amount'] ); ?>"
											placeholder="<?php esc_attr_e( 'No minimum', 'instant-order-notifier-woc' ); ?>">
									</div>
									<p class="text-muted small mb-0 mt-1">
										<?php esc_html_e( 'Notify only when order total is greater than or equal to this amount.', 'instant-order-notifier-woc' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- Rule Logic Info -->
					<!-- <div class="alert alert-info mb-0">
						<h6 class="alert-heading mb-2">
							<i class="bi bi-diagram-3 me-1"></i>
							<?php esc_html_e( 'Rule Logic', 'instant-order-notifier-woc' ); ?>
						</h6>
						<p class="mb-2 small">
							<?php esc_html_e( 'All configured conditions must match before any notification is triggered (AND logic). Empty filters are ignored.', 'instant-order-notifier-woc' ); ?>
						</p>
						<p class="mb-0 small text-muted">
							<?php esc_html_e( 'Example: Category = Clothing AND Total ≥ 100 AND Status = Processing → Popup + Desktop + WhatsApp (if each channel is enabled).', 'instant-order-notifier-woc' ); ?>
						</p>
					</div> -->

				</div><!-- #wpc-rules-sections -->
			</div>
			<hr class="my-0">
			<div class="card-footer text-end">
				<button type="submit" name="wpc_save_advanced_rules" class="btn btn-theme">
					<i class="bi bi-save me-2"></i>
					<?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
</div>

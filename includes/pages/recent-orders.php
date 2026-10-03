<?php
/**
 * Recent Orders admin page template.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="right-side-main">

	<div class="card">
		<div class="card-header">
			<h5 class="mb-0"><?php esc_html_e( 'Recent Orders', 'instant-order-notifier-woc' ); ?></h5>
		</div>
		<hr class="my-0">
		<div class="card-body">
			<div class="recent-order-main">
				<div class="recent-order-buttons-main">
					<div class="search-icon">
						<i class="bi bi-search"></i>
					</div>
					<div class="recent-order-buttons">
						<button id="wpc-reload-btn" class="btn" title="<?php esc_attr_e( 'Refresh', 'instant-order-notifier-woc' ); ?>">
							<i class="bi bi-arrow-clockwise fs-5"></i>
						</button>
						<button id="wpc-pause-btn" class="btn" title="<?php esc_attr_e( 'Pause', 'instant-order-notifier-woc' ); ?>">
							<i class="bi bi-pause-fill fs-5"></i>
						</button>
						<!-- Sound Button with Animated Unmute Text -->
						<div class="position-relative d-inline-block">
							<button id="wpc-sound-btn" class="btn" title="<?php esc_attr_e( 'Sound', 'instant-order-notifier-woc' ); ?>">
								<i class="bi bi-volume-mute-fill fs-5"></i>
							</button>

							<span id="wpc-unmute-reminder" class="unmute-text "
								style="">
								<i class="bi bi-arrow-left me-1"></i> <?php esc_html_e( 'Click to unmute sound', 'instant-order-notifier-woc' ); ?>
							</span>
							<div id="wpc-full-unmute-overlay" class="full-unmute-overlay"></div>
						</div>
					</div>
				</div>
				<!-- Dashboard Summary -->
				<div class="wpc-dashboard mb-3">
					<div class="row g-2">

						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1"><?php esc_html_e( 'Today Orders', 'instant-order-notifier-woc' ); ?></h6>
									<h4 id="wpc-today-orders">0</h4>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1"><?php esc_html_e( 'Processing', 'instant-order-notifier-woc' ); ?></h6>
									<h4 id="wpc-processing-orders">0</h4>
								</div>
							</div>
						</div>

					
						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1"><?php esc_html_e( 'Cancelled', 'instant-order-notifier-woc' ); ?></h6>
									<h4 id="wpc-cancelled-orders">0</h4>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1"><?php esc_html_e( 'Completed', 'instant-order-notifier-woc' ); ?></h6>
									<h4 id="wpc-completed-orders">0</h4>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="recent-order-table">
					<div class="table-responsive">
						<div class="entry-data-main d-flex align-items-center gap-2">
							<span><?php esc_html_e( 'Show', 'instant-order-notifier-woc' ); ?></span>
							<select id="wpc-per-page" class="form-select form-select-sm">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
							<span><?php esc_html_e( 'Data', 'instant-order-notifier-woc' ); ?></span>
							<select id="wpc-status-filter" class="form-select form-select ms-2 w-auto">
								<option value=""><?php esc_html_e( 'All Status', 'instant-order-notifier-woc' ); ?></option>
								<option value="processing"><?php esc_html_e( 'Processing', 'instant-order-notifier-woc' ); ?></option>
								<option value="completed"><?php esc_html_e( 'Completed', 'instant-order-notifier-woc' ); ?></option>
								<option value="pending"><?php esc_html_e( 'Pending', 'instant-order-notifier-woc' ); ?></option>
								<option value="on-hold"><?php esc_html_e( 'On Hold', 'instant-order-notifier-woc' ); ?></option>
								<option value="cancelled"><?php esc_html_e( 'Cancelled', 'instant-order-notifier-woc' ); ?></option>
								<option value="refunded"><?php esc_html_e( 'Refunded', 'instant-order-notifier-woc' ); ?></option>
								<option value="failed"><?php esc_html_e( 'Failed', 'instant-order-notifier-woc' ); ?></option>
							</select>
						</div>
						<table class="table table-bordered mb-0 mt-0" id="wpc-orders-table">
							<thead class="">
								<tr>
									<th><?php esc_html_e( '# Order ID', 'instant-order-notifier-woc' ); ?></th>
									<th><?php esc_html_e( 'Customer', 'instant-order-notifier-woc' ); ?></th>
									<th><?php esc_html_e( 'Total', 'instant-order-notifier-woc' ); ?></th>
									<th><?php esc_html_e( 'Status', 'instant-order-notifier-woc' ); ?></th>
									<th><?php esc_html_e( 'Order Date', 'instant-order-notifier-woc' ); ?></th>
									<th><?php esc_html_e( 'Action', 'instant-order-notifier-woc' ); ?></th>
								</tr>
							</thead>
							<tbody id="wpc-table-body">
								<tr>
									<td colspan="6" class="text-center py-3">
										<div class="spinner-border text-theme"></div>
										<p class="mt-3 mb-0 text-muted"><?php esc_html_e( 'Loading orders...', 'instant-order-notifier-woc' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="wpc-pagination" class="mt-3"></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Popup Modal -->
	<div class="modal fade" id="wpc-popup-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content text-center shadow">
				<div class="modal-body py-3">
					<!-- <i class="bi bi-bell-fill text-warning" style="font-size:60px;"></i> -->
					<img src="<?php echo esc_url( WPC_WCON_URL . 'assets/images/bell.gif' ); ?>"
						alt="<?php esc_attr_e( 'New Order Notification', 'instant-order-notifier-woc' ); ?>"
						style="max-width: 100px; height: auto;" />
					<h3 class="mt-3 text-danger"><?php esc_html_e( 'New Order Received!', 'instant-order-notifier-woc' ); ?></h3>
					<div id="wpc-popup-text" class="fs-5 mt-3"></div>
					<button id="wpc-popup-ok" class="btn btn-theme px-4 py-2 mt-3"><?php esc_html_e( 'Got it!', 'instant-order-notifier-woc' ); ?></button>
				</div>
			</div>
		</div>
	</div>

</div>
</div>
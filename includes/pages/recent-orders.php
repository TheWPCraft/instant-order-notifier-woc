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
			<h5 class="mb-0">Recent Orders</h5>
		</div>
		<hr class="my-0">
		<div class="card-body">
			<div class="recent-order-main">
				<div class="recent-order-buttons-main">
					<div class="search-icon">
						<i class="bi bi-search"></i>
					</div>
					<div class="recent-order-buttons">
						<button id="wpc-reload-btn" class="btn" title="Refresh">
							<i class="bi bi-arrow-clockwise fs-5"></i>
						</button>
						<button id="wpc-pause-btn" class="btn" title="Pause">
							<i class="bi bi-pause-fill fs-5"></i>
						</button>
						<!-- Sound Button with Animated Unmute Text -->
						<div class="position-relative d-inline-block">
							<button id="wpc-sound-btn" class="btn" title="Sound">
								<i class="bi bi-volume-mute-fill fs-5"></i>
							</button>

							<span id="wpc-unmute-reminder" class="unmute-text "
								style="">
								<i class="bi bi-arrow-left me-1"></i> Click to unmute sound
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
									<h6 class="mb-1">Today Orders</h6>
									<h4 id="wpc-today-orders">0</h4>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1">Processing</h6>
									<h4 id="wpc-processing-orders">0</h4>
								</div>
							</div>
						</div>

					
						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1">Cancelled</h6>
									<h4 id="wpc-cancelled-orders">0</h4>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="card text-center shadow-sm">
								<div class="card-body py-2">
									<h6 class="mb-1">Completed</h6>
									<h4 id="wpc-completed-orders">0</h4>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="recent-order-table">
					<div class="table-responsive">
						<div class="entry-data-main d-flex align-items-center gap-2">
							<span>Show</span>
							<select id="wpc-per-page" class="form-select form-select-sm">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
							<span>Data</span>
							<select id="wpc-status-filter" class="form-select form-select ms-2 w-auto">
								<option value="">All Status</option>
								<option value="processing">Processing</option>
								<option value="completed">Completed</option>
								<option value="pending">Pending</option>
								<option value="on-hold">On Hold</option>
								<option value="cancelled">Cancelled</option>
								<option value="refunded">Refunded</option>
								<option value="failed">Failed</option>
							</select>
						</div>
						<table class="table table-bordered mb-0 mt-0" id="wpc-orders-table">
							<thead class="">
								<tr>
									<th># Order ID</th>
									<th>Customer</th>
									<th>Total</th>
									<th>Status</th>
									<th>Order Date</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody id="wpc-table-body">
								<tr>
									<td colspan="6" class="text-center py-3">
										<div class="spinner-border text-theme"></div>
										<p class="mt-3 mb-0 text-muted">Loading orders...</p>
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
						alt="New Order Notification"
						style="max-width: 100px; height: auto;" />
					<h3 class="mt-3 text-danger">New Order Received!</h3>
					<div id="wpc-popup-text" class="fs-5 mt-3"></div>
					<button id="wpc-popup-ok" class="btn btn-theme px-4 py-2 mt-3">Got it!</button>
				</div>
			</div>
		</div>
	</div>

</div>
</div>
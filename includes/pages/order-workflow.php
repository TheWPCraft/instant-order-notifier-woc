<?php
/**
 * Order Workflow admin page.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="right-side-main">
	<div class="card wpc-workflow-container">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h5 class="mb-0">🚨 Order Workflow Queue</h5>
			<div id="wpc-workflow-stats-summary" class="d-flex gap-3 text-muted small">
				<!-- Loaded via JS -->
			</div>
		</div>

		<div class="card-body">
			<!-- Workflow Stats Cards -->
			<div class="row g-3 mb-4" id="wpc-workflow-stats-cards">
				<div class="col-md-3">
					<div class="card text-center shadow-sm wpc-workflow-stat-card" data-status="new">
						<div class="card-body py-2">
							<h6 class="mb-1">New Orders</h6>
							<h4 id="wpc-stat-new">0</h4>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card text-center shadow-sm wpc-workflow-stat-card border-danger" data-status="high_priority">
						<div class="card-body py-2">
							<h6 class="mb-1 text-danger">High Priority</h6>
							<h4 id="wpc-stat-attention" class="text-danger">0</h4>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card text-center shadow-sm wpc-workflow-stat-card" data-status="processing">
						<div class="card-body py-2">
							<h6 class="mb-1">Processing</h6>
							<h4 id="wpc-stat-processing">0</h4>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card text-center shadow-sm wpc-workflow-stat-card" data-status="ready">
						<div class="card-body py-2">
							<h6 class="mb-1">Ready</h6>
							<h4 id="wpc-stat-ready">0</h4>
						</div>
					</div>
				</div>
			</div>

			<!-- Filters, Sorting and Search -->
			<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 p-3 bg-white rounded shadow-sm border">
				<div class="d-flex gap-2 align-items-center flex-wrap">
					<select id="wpc-workflow-date-filter" class="form-select form-select-sm w-auto">
						<option value="today">Today's Orders</option>
						<option value="all">All Orders</option>
					</select>

					<select id="wpc-workflow-status-filter" class="form-select form-select-sm w-auto">
						<option value="">All Status</option>
						<option value="new">New</option>
						<option value="acknowledged">Acknowledged</option>
						<option value="processing">Processing</option>
						<option value="ready">Ready</option>
						<option value="high_priority">High Priority</option>
					</select>

					<select id="wpc-workflow-sort" class="form-select form-select-sm w-auto">
						<option value="newest">Newest First</option>
						<option value="oldest">Oldest First</option>
					</select>

					<div class="form-check form-switch ms-2">
						<input class="form-check-input" type="checkbox" id="wpc-auto-open-toggle">
						<label class="form-check-label small" for="wpc-auto-open-toggle">Auto-Open Next</label>
					</div>

					<button id="wpc-workflow-refresh" class="btn btn-sm btn-outline-secondary">
						<i class="bi bi-arrow-clockwise"></i>
					</button>
				</div>

				<div class="d-flex gap-2 align-items-center flex-wrap">
					<div class="search-box">
						<div class="input-group input-group-sm">
							<span class="input-group-text"><i class="bi bi-search"></i></span>
							<input type="text" id="wpc-workflow-search" class="form-control" placeholder="Search ID or Name...">
						</div>
					</div>
				</div>
			</div>

			<!-- Bulk Action Toolbar (Initially Hidden) -->
			<div id="wpc-bulk-toolbar" class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="display:none !important;">
				<div class="d-flex align-items-center gap-3">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="wpc-select-all">
						<label class="form-check-label small fw-bold" for="wpc-select-all">Select All</label>
					</div>
					<span class="small"><span id="wpc-selected-count">0</span> orders selected</span>
				</div>
				<div class="d-flex gap-2 align-items-center">
					<select id="wpc-bulk-action" class="form-select form-select-sm w-auto">
						<option value="">Bulk Actions</option>
						<option value="acknowledged">Mark Acknowledged</option>
						<option value="processing">Start Processing</option>
						<option value="ready">Mark Ready</option>
						<option value="completed">Mark Completed</option>
					</select>
					<button id="wpc-apply-bulk" class="btn btn-sm btn-primary">Apply</button>
				</div>
			</div>

			<!-- The Queue -->
			<div id="wpc-workflow-queue" class="wpc-workflow-queue-grid">
				<div class="text-center py-5">
					<div class="spinner-border text-primary" role="status"></div>
					<p class="mt-2">Loading your workflow...</p>
				</div>
			</div>

			<!-- Free Limit Footer -->
			<div id="wpc-free-limit-card" class="card mt-4 border-warning bg-light" style="display:none;">
				<div class="card-body text-center">
					<h5 class="card-title text-warning">🔒 Manage Unlimited Orders with Pro</h5>
					<p class="card-text">
						Showing <span id="wpc-free-count">5</span> of <span id="wpc-free-total">0</span> active orders.
						The Free version can manage up to 5 active orders.
					</p>
					<p class="small text-muted mb-3">
						Upgrade to Pro to manage your complete order queue and process orders one by one.
					</p>
					<a href="https://thewpcraft.com/plugins-details/instant-order-notification" target="_blank" class="btn btn-warning">Upgrade to Pro</a>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Next Order Notification -->
<div id="wpc-next-order-toast" class="toast-container position-fixed bottom-0 end-0 p-3">
	<div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="d-flex">
			<div class="toast-body">
				✓ Order status updated.
				<div class="mt-2 pt-2 border-top">
					<strong id="wpc-next-order-text">Next Order: #1255</strong><br>
					<button type="button" class="btn btn-light btn-sm mt-1" id="wpc-open-next-order">Open Next Order</button>
				</div>
			</div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
	</div>
</div>

<!-- Quick Process Modal -->
<div class="modal fade" id="wpc-quick-process-modal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content border-0 shadow-lg">
			<div class="modal-header bg-light border-bottom-0 py-3">
				<h5 class="modal-title fw-bold" id="wpc-modal-order-id">Order #----</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-0">
				<div id="wpc-modal-loader" class="text-center py-5">
					<div class="spinner-border text-primary" role="status"></div>
					<p class="mt-2">Loading details...</p>
				</div>
				<div id="wpc-modal-content" style="display:none;">
					<div class="p-4">
						<div class="row g-4">
							<div class="col-md-7">
								<h6 class="text-uppercase text-muted small fw-bold mb-3">Order Items</h6>
								<div id="wpc-modal-items-list" class="list-group list-group-flush mb-3">
									<!-- Items loaded via JS -->
								</div>
								<div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
									<span class="fw-bold">Total Amount</span>
									<span class="fs-4 fw-bold text-primary" id="wpc-modal-total">₹0.00</span>
								</div>
							</div>
							<div class="col-md-5 border-start ps-md-4">
								<h6 class="text-uppercase text-muted small fw-bold mb-3">Customer Details</h6>
								<div class="mb-3">
									<div class="fw-bold fs-5" id="wpc-modal-customer-name">---</div>
									<div class="text-muted small" id="wpc-modal-customer-email">---</div>
									<div class="text-muted small" id="wpc-modal-customer-phone">---</div>
								</div>
								<hr>
								<div class="mb-3">
									<span class="text-muted small d-block">WooCommerce Status</span>
									<span class="badge bg-secondary" id="wpc-modal-wc-status">---</span>
								</div>
								<div class="mb-4">
									<span class="text-muted small d-block">Internal Workflow</span>
									<span class="badge" id="wpc-modal-workflow-status">---</span>
								</div>

								<div id="wpc-modal-actions" class="d-grid gap-2">
									<!-- Action buttons loaded via JS -->
								</div>

								<div class="mt-3 text-center">
									<a href="#" id="wpc-modal-view-wc" target="_blank" class="text-decoration-none small">
										<i class="bi bi-box-arrow-up-right me-1"></i> View in WooCommerce
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Next Order Promo (Inside Modal) -->
					<div id="wpc-modal-next-step" class="bg-success text-white p-3 text-center" style="display:none;">
						<div id="wpc-modal-next-message" class="mb-2">✓ Order processed successfully!</div>
						<button class="btn btn-light btn-sm fw-bold" id="wpc-modal-btn-next">
							➡️ Next Order: #<span id="wpc-modal-next-id">---</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>

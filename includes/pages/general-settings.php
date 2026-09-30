<?php
/**
 * General Settings admin page template.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $settings is populated below; the form is saved by wpc_general_settings_page().


$settings = get_option(
	'wpc_notification_settings',
	[
		'notification_enabled' => '1',
		'ringtone'             => '1',
		'check_speed'          => 'normal',
	]
);
?>
<div class="right-side-main">
	<div class="card">
		<form method="post" class="" enctype="multipart/form-data">
			<div class="card-header">
				<div class="d-flex align-items-center justify-content-between">
					<h5 class="mb-0"><?php esc_html_e( 'General Settings', 'instant-order-notifier-woc' ); ?></h5>
					<button type="submit" name="wpc_save_settings" class="btn btn-theme">
						<i class="bi bi-save me-1"></i> <?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
					</button>
				</div>
			</div>
			<hr class="my-0">
			<div class="card-body">
				<div class="general-settings-main">
					<?php wp_nonce_field( 'wpc_save_settings_nonce' ); ?>

					<!-- Enable Notifications -->

					<div class="card">
						<div class="card-body d-flex justify-content-between align-items-center">
							<div>
								<h5 class="card-title mb-1"><?php esc_html_e( 'Enable Notifications', 'instant-order-notifier-woc' ); ?></h5>
								<p class="text-muted mb-0"><?php esc_html_e( 'Enable order notification alerts.', 'instant-order-notifier-woc' ); ?></p>
							</div>
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled"
									<?php checked( $settings['notification_enabled'], '1' ); ?>>
							</div>
						</div>
					</div>
					<!-- Enable Desktop Notifications -->
					<div class="card">
						<div class="card-body d-flex justify-content-between align-items-center">
							<div>
								<h5 class="card-title mb-1"><?php esc_html_e( 'Enable Desktop Notifications', 'instant-order-notifier-woc' ); ?></h5>
								<p class="text-muted mb-0">
									<?php esc_html_e( 'Allow browser notifications for new orders.', 'instant-order-notifier-woc' ); ?>
								</p>
							</div>
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" id="desktop_notifications_enabled" name="desktop_notifications_enabled"
									<?php checked( $settings['desktop_notifications_enabled'] ?? '0', '1' ); ?>>
							</div>
						</div>
						<div id="desktop-notification-tip" class="alert alert-info mt-0 m-2 small py-2" style="display:none;">
							<strong><?php esc_html_e( 'Windows:', 'instant-order-notifier-woc' ); ?></strong> <?php esc_html_e( 'Settings → System → Notifications → Allow notifications from browser ON', 'instant-order-notifier-woc' ); ?><br>
							<strong><?php esc_html_e( 'Mac:', 'instant-order-notifier-woc' ); ?></strong> <?php esc_html_e( 'System Preferences → Notifications → Browser → Allow ON', 'instant-order-notifier-woc' ); ?>
						</div>
					</div>

					<!-- Select Ringtone -->
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0"><?php esc_html_e( 'Select Ringtone', 'instant-order-notifier-woc' ); ?></h5>
							<p class="text-muted mb-0"><?php esc_html_e( 'Choose your preferred notification sound or upload your own.', 'instant-order-notifier-woc' ); ?></p>
						</div>
						<hr class="my-0">
						<div class="card-body">
							<div class="list-group">
								<label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo '1' === $settings['ringtone'] ? 'active' : ''; ?>">
									<div class="form-check">
										<input class="form-check-input" type="radio" name="ringtone" value="1" id="ringtone1" <?php checked( $settings['ringtone'], '1' ); ?>>
										<label class="form-check-label ms-3 fw-medium" for="ringtone1"><?php esc_html_e( 'Ringtone 1', 'instant-order-notifier-woc' ); ?></label>
									</div>
									<button type="button" class="btn play-sound" data-sound="1">
										<i class="bi bi-play-fill"></i>
									</button>
								</label>
								<label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo '2' === $settings['ringtone'] ? 'active' : ''; ?>">
									<div class="form-check">
										<input class="form-check-input" type="radio" name="ringtone" value="2" id="ringtone2" <?php checked( $settings['ringtone'], '2' ); ?>>
										<label class="form-check-label ms-3 fw-medium" for="ringtone2"><?php esc_html_e( 'Ringtone 2', 'instant-order-notifier-woc' ); ?></label>
									</div>
									<button type="button" class="btn play-sound" data-sound="2">
										<i class="bi bi-play-fill"></i>
									</button>
								</label>
								<label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo '3' === $settings['ringtone'] ? 'active' : ''; ?>">
									<div class="form-check">
										<input class="form-check-input" type="radio" name="ringtone" value="3" id="ringtone3" <?php checked( $settings['ringtone'], '3' ); ?>>
										<label class="form-check-label ms-3 fw-medium" for="ringtone3"><?php esc_html_e( 'Ringtone 3', 'instant-order-notifier-woc' ); ?></label>
									</div>
									<button type="button" class="btn play-sound" data-sound="3">
										<i class="bi bi-play-fill"></i>
									</button>
								</label>

								<div class="wpc-audio-slot <?php echo 'custom' === $settings['ringtone'] ? 'active-slot' : ''; ?>">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<div class="form-check">
											<input class="form-check-input" type="radio" name="ringtone" value="custom" id="ringtone_custom" <?php checked( $settings['ringtone'], 'custom' ); ?>>
											<label class="form-check-label ms-2 slot-label" for="ringtone_custom">
												<i class="bi bi-music-note-beamed"></i> <?php echo esc_html( sprintf( /* translators: %d: custom sound slot number. */ __( 'Custom Sound %d', 'instant-order-notifier-woc' ), 1 ) ); ?>
											</label>
										</div>
										<div class="action-buttons">
											<?php if ( ! empty( $settings['custom_ringtone_url'] ) ) : ?>
												<button type="button" class="btn btn-action btn-play-preview play-sound" data-sound="custom" title="<?php esc_attr_e( 'Preview', 'instant-order-notifier-woc' ); ?>">
													<i class="bi bi-play-fill"></i>
												</button>
												<button type="button" class="btn btn-action btn-remove-file" id="remove_custom_sound" title="<?php esc_attr_e( 'Delete', 'instant-order-notifier-woc' ); ?>">
													<i class="bi bi-trash"></i>
												</button>
											<?php endif; ?>
										</div>
									</div>
									<div class="file-input-wrapper">
										<input type="hidden" name="remove_custom_sound" id="remove_custom_sound_input" value="0">
										<?php if ( ! empty( $settings['custom_ringtone_url'] ) ) : ?>
											<div class="current-file-info">
												<small class="text-truncate" style="max-width: 200px;">
													<i class="bi bi-file-earmark-music me-1"></i>
													<?php echo esc_html( basename( $settings['custom_ringtone_url'] ) ); ?>
												</small>
												<a href="<?php echo esc_url( $settings['custom_ringtone_url'] ); ?>" target="_blank" rel="noopener" class="text-theme small text-decoration-none">
													<i class="bi bi-download"></i>
												</a>
											</div>
										<?php endif; ?>
									</div>
								</div>

								<div class="wpc-audio-slot <?php echo 'custom2' === $settings['ringtone'] ? 'active-slot' : ''; ?>">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<div class="form-check">
											<input class="form-check-input" type="radio" name="ringtone" value="custom2" id="ringtone_custom2" <?php checked( $settings['ringtone'], 'custom2' ); ?>>
											<label class="form-check-label ms-2 slot-label" for="ringtone_custom2">
												<i class="bi bi-music-note-beamed"></i> <?php echo esc_html( sprintf( /* translators: %d: custom sound slot number. */ __( 'Custom Sound %d', 'instant-order-notifier-woc' ), 2 ) ); ?>
											</label>
										</div>
										<div class="action-buttons">
											<?php if ( ! empty( $settings['custom_ringtone_url_2'] ) ) : ?>
												<button type="button" class="btn btn-action btn-play-preview play-sound" data-sound="custom2" title="<?php esc_attr_e( 'Preview', 'instant-order-notifier-woc' ); ?>">
													<i class="bi bi-play-fill"></i>
												</button>
												<button type="button" class="btn btn-action btn-remove-file" id="remove_custom_sound_2" title="<?php esc_attr_e( 'Delete', 'instant-order-notifier-woc' ); ?>">
													<i class="bi bi-trash"></i>
												</button>
											<?php endif; ?>
										</div>
									</div>
									<div class="file-input-wrapper">
										<input type="hidden" name="remove_custom_sound_2" id="remove_custom_sound_2_input" value="0">
										<?php if ( ! empty( $settings['custom_ringtone_url_2'] ) ) : ?>
											<div class="current-file-info">
												<small class="text-truncate" style="max-width: 200px;">
													<i class="bi bi-file-earmark-music me-1"></i>
													<?php echo esc_html( basename( $settings['custom_ringtone_url_2'] ) ); ?>
												</small>
												<a href="<?php echo esc_url( $settings['custom_ringtone_url_2'] ); ?>" target="_blank" rel="noopener" class="text-theme small text-decoration-none">
													<i class="bi bi-download"></i>
												</a>
											</div>
										<?php endif; ?>
									</div>
								</div>

								<div class="mt-3 p-3 border rounded bg-white">
									<label class="form-label fw-bold mb-1"><i class="bi bi-cloud-arrow-up me-1"></i> <?php esc_html_e( 'Upload New Sounds', 'instant-order-notifier-woc' ); ?></label>
									<input type="file" name="custom_ringtones[]" id="custom_ringtones" accept=".mp3,.wav,.ogg,audio/mpeg,audio/wav,audio/ogg" class="form-control form-control-sm" multiple>
									<div class="mt-2 text-center">
										<small class="text-muted"><i class="bi bi-info-circle me-1"></i> <?php esc_html_e( 'Select up to 2 files (mp3, wav or ogg, 2 MB each). Slot 1 and Slot 2 will be filled in order.', 'instant-order-notifier-woc' ); ?></small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Order Check Speed -->
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0"><?php esc_html_e( 'Order Check Speed', 'instant-order-notifier-woc' ); ?></h5>
							<p class="text-muted mb-0"><?php esc_html_e( 'How quickly should we check for new orders?', 'instant-order-notifier-woc' ); ?></p>
						</div>
						<hr class="my-0">
						<div class="card-body">
							<div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
								<div class="text-center">
									<input type="radio" class="btn-check" name="check_speed" id="speed_fast" value="fast" <?php checked( $settings['check_speed'], 'fast' ); ?>>
									<label class="btn d-block" for="speed_fast" style="width:150px;">
										<div class="fs-4 mb-2">⚡</div>
										<h6 class="fw-bold mb-0"><?php esc_html_e( 'Fast', 'instant-order-notifier-woc' ); ?></h6>
										<small><?php esc_html_e( 'Every 1 second', 'instant-order-notifier-woc' ); ?></small>
									</label>
								</div>
								<div class="text-center">
									<input type="radio" class="btn-check" name="check_speed" id="speed_normal" value="normal" <?php checked( $settings['check_speed'], 'normal' ); ?>>
									<label class="btn d-block" for="speed_normal" style="width:150px;">
										<div class="fs-4 mb-2">🔔</div>
										<h6 class="fw-bold mb-0"><?php esc_html_e( 'Normal', 'instant-order-notifier-woc' ); ?></h6>
										<small><?php esc_html_e( 'Every 2 seconds', 'instant-order-notifier-woc' ); ?></small>
									</label>
								</div>
								<div class="text-center">
									<input type="radio" class="btn-check" name="check_speed" id="speed_slow" value="slow" <?php checked( $settings['check_speed'], 'slow' ); ?>>
									<label class="btn d-block" for="speed_slow" style="width:150px;">
										<div class="fs-4 mb-2">🐢</div>
										<h6 class="fw-bold mb-0"><?php esc_html_e( 'Slow', 'instant-order-notifier-woc' ); ?></h6>
										<small><?php esc_html_e( 'Every 6 seconds', 'instant-order-notifier-woc' ); ?></small>
									</label>
								</div>
							</div>
							<div class="alert alert-info mb-0 mt-3 small py-2">
								<i class="bi bi-info-circle me-2"></i>
								<strong><?php esc_html_e( 'Tip:', 'instant-order-notifier-woc' ); ?></strong> <?php esc_html_e( 'Use "Normal" for most stores. Use "Slow" if your server is slow.', 'instant-order-notifier-woc' ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<hr class="my-0">
			<!-- Save Button -->
			<div class="card-footer text-end">
				<button type="submit" name="wpc_save_settings" class="btn btn-theme">
					<i class="bi bi-save me-2"></i> <?php esc_html_e( 'Save Settings', 'instant-order-notifier-woc' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
</div>
</div>

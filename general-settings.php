<?php
if (!defined('ABSPATH')) exit;

// Save settings


$settings = get_option('wpc_notification_settings', [
    'notification_enabled' => '1',
    'ringtone'             => '1',
    'check_speed'          => 'normal'
]);
?>
<div class="right-side-main">
    <div class="card">
        <form method="post" class="">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">General Settings</h5>
                    <button type="submit" name="wpc_save_settings" class="btn btn-theme">
                        <i class="bi bi-save me-1"></i> Save Settings
                    </button>
                </div>
            </div>
            <hr class="my-0">
            <div class="card-body">
                <div class="general-settings-main">
                    <?php wp_nonce_field('wpc_save_settings_nonce'); ?>

                    <!-- Enable Notifications -->

                    <div class="card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Enable Notifications</h5>
                                <p class="text-muted mb-0">Enable order notification alerts.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled"
                                    <?php checked($settings['notification_enabled'], '1'); ?>>
                            </div>
                        </div>
                    </div>
                    <!-- Enable Desktop Notifications -->
                    <div class="card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Enable Desktop Notifications</h5>
                                <p class="text-muted mb-0">
                                    Allow browser notifications for new orders.
                                </p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="desktop_notifications_enabled" name="desktop_notifications_enabled"
                                    <?php checked($settings['desktop_notifications_enabled'] ?? '0', '1'); ?>>
                            </div>
                        </div>
                        <div id="desktop-notification-tip" class="alert alert-info mt-0 m-2 small py-2" style="display:none;">
                            <strong>Windows:</strong> Settings → System → Notifications → Allow notifications from browser ON<br>
                            <strong>Mac:</strong> System Preferences → Notifications → Browser → Allow ON
                        </div>
                    </div>

                    <!-- Select Ringtone -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Select Ringtone</h5>
                            <p class="text-muted mb-0">Choose your preferred notification sound.</p>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <div class="list-group">
                                <label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo $settings['ringtone'] == '1' ? 'active' : ''; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ringtone" value="1" id="ringtone1" <?php checked($settings['ringtone'], '1'); ?>>
                                        <label class="form-check-label ms-3 fw-medium" for="ringtone1">Ringtone 1</label>
                                    </div>
                                    <button type="button" class="btn play-sound" data-sound="1">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                </label>
                                <label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo $settings['ringtone'] == '2' ? 'active' : ''; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ringtone" value="2" id="ringtone2" <?php checked($settings['ringtone'], '2'); ?>>
                                        <label class="form-check-label ms-3 fw-medium" for="ringtone2">Ringtone 2</label>
                                    </div>
                                    <button type="button" class="btn play-sound" data-sound="2">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                </label>
                                <label class="list-group-item d-flex justify-content-between align-items-center mb-2 <?php echo $settings['ringtone'] == '3' ? 'active' : ''; ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ringtone" value="3" id="ringtone3" <?php checked($settings['ringtone'], '3'); ?>>
                                        <label class="form-check-label ms-3 fw-medium" for="ringtone3">Ringtone 3</label>
                                    </div>
                                    <button type="button" class="btn play-sound" data-sound="3">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Order Check Speed -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Order Check Speed</h5>
                            <p class="text-muted mb-0">How quickly should we check for new orders?</p>
                        </div>
                        <hr class="my-0">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
                                <div class="text-center">
                                    <input type="radio" class="btn-check" name="check_speed" id="speed_fast" value="fast" <?php checked($settings['check_speed'], 'fast'); ?>>
                                    <label class="btn d-block" for="speed_fast" style="width:150px;">
                                        <div class="fs-4 mb-2">⚡</div>
                                        <h6 class="fw-bold mb-0">Fast</h6>
                                        <small>Every 1 second</small>
                                    </label>
                                </div>
                                <div class="text-center">
                                    <input type="radio" class="btn-check" name="check_speed" id="speed_normal" value="normal" <?php checked($settings['check_speed'], 'normal'); ?>>
                                    <label class="btn d-block" for="speed_normal" style="width:150px;">
                                        <div class="fs-4 mb-2">🔔</div>
                                        <h6 class="fw-bold mb-0">Normal</h6>
                                        <small>Every 2 seconds</small>
                                    </label>
                                </div>
                                <div class="text-center">
                                    <input type="radio" class="btn-check" name="check_speed" id="speed_slow" value="slow" <?php checked($settings['check_speed'], 'slow'); ?>>
                                    <label class="btn d-block" for="speed_slow" style="width:150px;">
                                        <div class="fs-4 mb-2">🐢</div>
                                        <h6 class="fw-bold mb-0">Slow</h6>
                                        <small>Every 6 seconds</small>
                                    </label>
                                </div>
                            </div>
                            <div class="alert alert-info mb-0 mt-3 small py-2">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tip:</strong> Use "Normal" for most stores. Use "Slow" if your server is slow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-0">
            <!-- Save Button -->
            <div class="card-footer text-end">
                <button type="submit" name="wpc_save_settings" class="btn btn-theme">
                    <i class="bi bi-save me-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
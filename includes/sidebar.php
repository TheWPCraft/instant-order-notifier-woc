<?php
if (!defined('ABSPATH')) exit;

$current_page = '';

if (function_exists('get_current_screen')) {
    $screen = get_current_screen();
    if (!empty($screen->base)) {
        $current_page = $screen->base;
    }
}

$is_recent   = (strpos($current_page, 'woc-order-notification') !== false);
$is_settings = (strpos($current_page, 'woc-general-settings') !== false);

$recent_orders_url    = esc_url(admin_url('admin.php?page=woc-order-notification'));
$general_settings_url = esc_url(admin_url('admin.php?page=woc-general-settings'));
?>
<!-- Page Content Will Be Included Here -->
<main>
    <nav class="navbar navbar-expand-sm navbar-light mb-3 shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand py-0" href="#">Woo Order Notification</a>
            <!-- <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavId" aria-controls="collapsibleNavId" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavId">
                <ul class="navbar-nav ms-auto mt-2 mt-lg-0 gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#" aria-current="page">Support</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Documentations</a>
                    </li>
                </ul>
            </div> -->
        </div>
    </nav>
    <div class="container-fluid ps-0 py-0">
        <div class="d-flex all-main-part ">
          
            <div class="sidbar-main">
                <div class="p-2">
                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link mb-3 rounded <?php echo $is_recent ? 'active' : ''; ?>"
                            href="<?php echo esc_url($recent_orders_url); ?>">
                            <span>Recent Orders</span>
                        </a>
                        <span class="nav-link-main-text"> settings </span>
                        <a class="nav-link mb-2 rounded <?php echo $is_settings ? 'active' : ''; ?>"
                            href="<?php echo esc_url($general_settings_url); ?>">
                            <span>General Settings</span>
                        </a>
                    </nav>
                </div>
            </div>
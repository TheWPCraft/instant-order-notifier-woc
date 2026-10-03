<?php
/**
 * Shared admin page footer.
 *
 * @package Instant_Order_Notifier_Woc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<footer class="text-center mt-3">
	<p class="">
		<?php
		printf(
			/* translators: %s: "The WP Craft" company name, wrapped in <strong> markup. */
			esc_html__( 'Developed with ❤️ by %s | All Rights Reserved', 'instant-order-notifier-woc' ),
			'<strong>' . esc_html__( 'The WP Craft', 'instant-order-notifier-woc' ) . '</strong>'
		);
		?>
	</p>
</footer>
</main>
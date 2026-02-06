<?php
/**
 * Tabs template.
 *
 * @package Tasty/Framework
 */

use Tasty\Framework\Utils\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $data['tasty_is_savable'] ) ) {
	$tasty_is_savable = $data['tasty_is_savable'];
} else {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tasty_is_savable = ! isset( $_GET['tab'] ) || ! in_array( $_GET['tab'], array( 'about', 'converters' ), true );
}
?>

<div class="tasty-tabs-container <?php echo esc_attr( ! $tasty_is_savable ? 'tasty-tabs-container-not-savable' : '' ); ?>">
	<div class="tasty-tabs tasty-flex">
		<?php foreach ( $data['tabs'] as $tasty_tab ) { ?>
		<p>
			<a
				class="tasty-nav-tab <?php echo esc_attr( $tasty_tab['active'] ? 'tasty-nav-tab-active' : '' ); ?>"
				href="<?php echo esc_url( $tasty_tab['url'] ); ?>"
			>
				<?php
				echo esc_html( $tasty_tab['title'] );

				if ( ! empty( $tasty_tab['count'] ) ) {
					?>
					<span class="tasty-nav-tab-count"><?php echo absint( $tasty_tab['count'] ); ?></span>
					<?php
				}
				?>
			</a>
		</p>
		<?php } ?>
	</div>
	<div class="tasty-tabs-submit">
		<?php
		if ( $tasty_is_savable ) {
			/**
			 * Fires in the tabs submit section, allowing custom buttons.
			 *
			 * @since x.x
			 */
			do_action( 'tasty_before_tabs_submit' );

			/**
			 * Filters the buttons for the tabs.
			 *
			 * @since x.x
			 */
			$tasty_tab_buttons = apply_filters( 'tasty_tabs_buttons', Common::get_tabs_save_button() );
		}

		if ( ! empty( $tasty_tab_buttons ) ) {
			echo wp_kses_post( $tasty_tab_buttons );
		}
		?>
	</div>
</div>

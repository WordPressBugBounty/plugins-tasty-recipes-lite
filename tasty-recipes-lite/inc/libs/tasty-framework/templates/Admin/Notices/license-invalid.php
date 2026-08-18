<?php
/**
 * Invalid license admin notice template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="error inline tasty-license-admin-notice">
	<p>
		<?php
			echo wp_kses_post(
				sprintf(
					// translators: %s is the plugin name.
					__(
						'<strong>To enable updates and support for %s</strong> enter a valid license',
						'tasty'
					),
					$data['plugin_name']
				)
			);
			?>
	</p>
	<p>
		<strong>
			<?php esc_html_e( "Think you've reached this message in error?", 'tasty-recipes-lite' ); ?>
		</strong>
		<?php
			$tasty_support_url = ! empty( $data['support_url'] ) ? $data['support_url'] : 'https://www.wptasty.com/support';
			printf(
				// translators: %1$s Opening anchor tag, %2$s Closing anchor tag.
				esc_html__( '%1$sSubmit a support ticket%2$s, and we\'ll do our best to help out.', 'tasty-recipes-lite' ),
				'<a href="' . esc_url( $tasty_support_url ) . '" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
	</p>
</div>

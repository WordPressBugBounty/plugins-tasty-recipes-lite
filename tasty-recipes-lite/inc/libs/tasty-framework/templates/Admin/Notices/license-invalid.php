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
<div class="error">
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
			printf(
				// translators: %1$s Opening anchor tag, %1$s Closing anchor tag.
				esc_html__( '%1$sSubmit a support ticket%2$s, and we\'ll do our best to help out.', 'tasty-recipes-lite' ),
				'<a href="https://www.wptasty.com/support" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
	</p>
</div>

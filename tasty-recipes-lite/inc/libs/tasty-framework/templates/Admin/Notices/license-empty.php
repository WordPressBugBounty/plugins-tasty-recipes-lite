<?php
/**
 * Empty license admin notice template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="updated">
	<p>
		<strong>
			<?php
			// translators: %1$s Plugin name.
			echo esc_html( sprintf( __( '%1$s is almost ready.', 'tasty-recipes-lite' ), $data['plugin_name'] ) );
			?>
		</strong>
		<a href="<?php echo esc_url( $data['license_url'] ); ?>">
			<?php esc_html_e( 'Enter your license to continue.', 'tasty-recipes-lite' ); ?>
		</a>
	</p>
</div>

<?php
/**
 * Template for pro badge in settings page.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( empty( $template_object ) ) {
	return;
}

/* translators: %s: template name */
$preview_template = sprintf( __( 'Preview %s template', 'tasty-recipes-lite' ), $template_object->get_template_name() );
$template_id      = $template_object->get_id();
$variation_number = isset( $variation ) ? absint( $variation ) : 0;
$image_dir        = trailingslashit( dirname( TASTY_RECIPES_LITE_FILE ) ) . 'assets/images/templates/';
$image_url_base   = trailingslashit( plugins_url( 'assets/images/templates/', TASTY_RECIPES_LITE_FILE ) );
$image            = '';
$extensions       = array( 'png', 'jpg' );
$base_names       = array();

if ( $variation_number > 0 ) {
	$base_names[] = $template_id . '-variation-' . $variation_number;
}

$base_names[] = $template_id;

foreach ( $base_names as $base_name ) {
	foreach ( $extensions as $extension ) {
		$file_name = $base_name . '.' . $extension;
		if ( file_exists( $image_dir . $file_name ) ) {
			$image = $image_url_base . $file_name;
			break 2;
		}
	}
}

if ( empty( $image ) ) {
	$image = $image_url_base . $template_id . '.png';
}
?>
<div class="tasty-recipes-pro-template">
	<img src="<?php echo esc_url( $image ); ?>"
		alt="<?php echo esc_attr( $preview_template ); ?>"
	>
</div>

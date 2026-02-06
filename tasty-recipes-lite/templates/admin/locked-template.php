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
$image            = plugins_url(
	'assets/images/templates/' . $template_object->get_id() . '.png',
	TASTY_RECIPES_LITE_FILE
);
?>
<div class="tasty-recipes-pro-template">
	<img src="<?php echo esc_url( $image ); ?>"
		alt="<?php echo esc_attr( $preview_template ); ?>"
	>
</div>

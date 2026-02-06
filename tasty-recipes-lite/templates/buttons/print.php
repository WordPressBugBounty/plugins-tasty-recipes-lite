<?php
/**
 * Template for the print button.
 *
 * @package Tasty_Recipes
 *
 * @var object $recipe        Recipe object.
 * @var string $customization Customization options.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$current_post = get_post();
$ext          = is_feed() ? '.png' : '.svg';
$print_link   = '#';
if ( $current_post ) {
	$print_link = tasty_recipes_get_print_url( $current_post->ID, $recipe->get_id() );
}

$template_obj = Tasty_Recipes\Designs\Template::get_object_by_name( 'current' );
?>

<a class="button tasty-recipes-print-button tasty-recipes-no-print" <?php echo '#' === $print_link ? '' : 'href="' . esc_url( $print_link ) . '"'; ?> target="_blank" data-tasty-recipes-customization="<?php echo esc_attr( $customization ); ?>">
	<?php if ( '.svg' === $ext ) : ?>
		<svg viewBox="0 0 24 24" class="svg-print" aria-hidden="true"><use xlink:href="#tasty-recipes-icon-print"></use></svg>
	<?php elseif ( file_exists( $template_obj->get_base_templates_path() . 'images/icon-print.png' ) ) : ?>
		<img class="svg-print" data-pin-nopin="true" src="<?php echo esc_url( plugins_url( 'images/icon-print.png', $template_obj->get_base_templates_path() . 'tasty-recipes.php' ) ); ?>">
	<?php else : ?>
		<svg viewBox="0 0 24 24" class="svg-print" aria-hidden="true"><use xlink:href="#tasty-recipes-icon-print"></use></svg>
	<?php endif; ?>
	<?php esc_html_e( 'Print Recipe', 'tasty-recipes-lite' ); ?>
</a>

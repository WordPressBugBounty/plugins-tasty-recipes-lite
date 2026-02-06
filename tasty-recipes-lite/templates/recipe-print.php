<?php
/**
 * Template for the recipe print view.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) & !(IE 8)]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php
	Tasty_Recipes::echo_template_part( 'recipe-print-controls' );
	$recipe_id = get_query_var( Tasty_Recipes\Content_Model::get_print_query_var() );
	/**
	 * Fires before a recipe has been rendered for print.
	 *
	 * @param int $recipe_id ID for the recipe being rendered.
	 */
	do_action( 'tasty_recipes_before_render_print' );

	// The output comes from a template file that has already been escaped.
	echo Tasty_Recipes\Shortcodes::render_tasty_recipe_shortcode( array( 'id' => $recipe_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	/**
	 * Fires after a recipe has been rendered for print.
	 *
	 * @param int $recipe_id ID for the recipe being rendered.
	 */
	do_action( 'tasty_recipes_after_render_print' );
	?>
	<?php wp_footer(); ?>
</body>
</html>

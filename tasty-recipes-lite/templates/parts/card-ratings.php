<?php
/**
 * Display the recipe rating icons and label.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$color = \Tasty_Recipes\Shortcodes::get_template_customization( 'rating_color', $template );
?>

<div class="tasty-recipes-rating" <?php echo $color ? 'data-tasty-recipes-customization="' . esc_attr( $color ) . '"' : ''; ?>>
	<?php if ( ! empty( $recipe_rating_icons ) ) : ?>
		<p><?php \Tasty_Recipes\Utils::kses( $recipe_rating_icons, true ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $recipe_rating_label ) ) : ?>
		<p><?php \Tasty_Recipes\Utils::kses( $recipe_rating_label, true ); ?></p>
	<?php endif; ?>
</div>

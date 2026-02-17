<?php
/**
 * Default recipe template.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Tasty_Recipes\Utils;

$print_view_options = tasty_recipes_get_print_view_options();
?>

<h2 class="tasty-recipes-title" data-tasty-recipes-customization="h2-color.color h2-transform.text-transform"><?php Utils::kses( $recipe_title, true ); ?></h2>
<div class="tasty-recipes-image-button-container">
	<?php if ( ! empty( $recipe_image ) && isset( $print_view_options['images'] ) ) : ?>
		<div class="tasty-recipes-image">
			<?php Utils::kses( $recipe_image, true ); ?>
		</div>
	<?php endif; ?>
	<div class="tasty-recipes-buttons">
		<?php if ( ! empty( $first_button ) ) : ?>
		<div class="tasty-recipes-button-wrap">
			<?php Utils::kses( $first_button, true ); ?>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $second_button ) ) : ?>
		<div class="tasty-recipes-button-wrap">
			<?php Utils::kses( $second_button, true ); ?>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php
/**
 * Add more information after the title.
 *
 * @since 1.0
 *
 * @var array $vars Array of variables to pass to the action.
 */
do_action( 'tasty_recipes_card_after_title', $vars );
?>

<?php if ( ! empty( $recipe_print_button ) && ! tasty_recipes_is_print() ) : ?>
	<?php Utils::kses( $recipe_print_button, true ); ?>
<?php endif; ?>

<?php if ( ! empty( $recipe_description ) && isset( $print_view_options['description'] ) ) : ?>
	<div class="tasty-recipes-description" data-tasty-recipes-customization="body-color.color">
		<?php Utils::kses( $recipe_description, true ); ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_details ) ) : ?>
	<div class="tasty-recipes-details" data-tasty-recipes-customization="body-color.color">
		<ul>
			<?php foreach ( $recipe_details as $detail ) : ?>
				<li class="<?php echo esc_attr( $detail['class'] ); ?>"><strong data-tasty-recipes-customization="detail-label-color.color" class="tasty-recipes-label"><?php echo esc_html( $detail['label'] ); ?>:</strong> <?php Utils::kses( $detail['value'], true ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_ingredients ) ) : ?>
	<div class="tasty-recipes-ingredients">
		<div class="tasty-recipes-ingredients-header">
			<div class="tasty-recipes-ingredients-clipboard-container">
				<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php echo esc_html( Tasty_Recipes\Designs\Template::get_heading_name( 'ingredients', $recipe ) ); ?></h3>
				<?php if ( $copy_ingredients ) : ?>
					<?php Utils::kses( $copy_ingredients, true ); ?>
				<?php endif; ?>
			</div>
			<?php do_action( 'tasty_recipes_card_before_ingredients', $vars ); ?>
		</div>
		<div class="tasty-recipes-ingredients-body" data-tasty-recipes-customization="body-color.color">
			<?php Utils::kses( $recipe_ingredients, true ); ?>
		</div>
		<?php
		/**
		 * Add more information after the ingredient list.
		 *
		 * @var object $recipe The recipe to display.
		 */
		do_action( 'tasty_recipes_card_after_ingredients', $recipe );
		?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_instructions ) ) : ?>
	<div class="tasty-recipe-instructions">
		<div class="tasty-recipes-instructions-header">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php echo esc_html( Tasty_Recipes\Designs\Template::get_heading_name( 'instructions', $recipe ) ); ?></h3>
			<?php if ( ! empty( $recipe_instructions_has_video ) ) : ?>
			<div class="tasty-recipes-video-toggle-container">
				<label for="tasty-recipes-video-toggle"><?php esc_html_e( 'Video', 'tasty-recipes-lite' ); ?></label>
				<button type="button" role="switch" aria-checked="true" name="tasty-recipes-video-toggle">
					<span><?php esc_html_e( 'On', 'tasty-recipes-lite' ); ?></span>
					<span><?php esc_html_e( 'Off', 'tasty-recipes-lite' ); ?></span>
				</button>
			</div>
		<?php endif; ?>
		</div>
		<div class="tasty-recipes-instructions-body" data-tasty-recipes-customization="body-color.color">
			<?php Utils::kses( $recipe_instructions, true ); ?>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_video_embed ) ) : ?>
	<div class="tasty-recipe-video-embed" id="<?php echo esc_attr( 'tasty-recipe-video-embed-' . $recipe->get_id() ); ?>">
		<?php Utils::kses( $recipe_video_embed, true ); ?>
	</div>
<?php endif; ?>

<?php
if ( ! empty( $recipe_equipment ) ) :
	?>
	<div class="tasty-recipes-equipment">
		<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php echo esc_html( Tasty_Recipes\Designs\Template::get_heading_name( 'equipment', $recipe ) ); ?></h3>
		<?php Utils::kses( $recipe_equipment, true ); ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_notes ) && isset( $print_view_options['notes'] ) ) : ?>
	<div class="tasty-recipes-notes">
		<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php echo esc_html( Tasty_Recipes\Designs\Template::get_heading_name( 'notes', $recipe ) ); ?></h3>
		<div class="tasty-recipes-notes-body" data-tasty-recipes-customization="body-color.color">
			<?php Utils::kses( $recipe_notes, true ); ?>
		</div>
	</div>
<?php endif; ?>

<?php do_action( 'tasty_recipes_card_before_nutrition', $vars ); ?>

<?php if ( ! empty( $recipe_nutrifox_embed ) && isset( $print_view_options['nutrition'] ) ) : ?>
	<div class="tasty-recipes-nutrifox">
		<?php Utils::kses( $recipe_nutrifox_embed, true ); ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_nutrition ) && isset( $print_view_options['nutrition'] ) ) : ?>
	<div class="tasty-recipes-nutrition">
		<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php echo esc_html( Tasty_Recipes\Designs\Template::get_heading_name( 'nutrition', $recipe ) ); ?></h3>
		<ul>
			<?php foreach ( $recipe_nutrition as $nutrition ) : ?>
				<li><strong class="tasty-recipes-label" data-tasty-recipes-customization="body-color.color"><?php echo esc_html( $nutrition['label'] ); ?>:</strong> <?php Utils::kses( $nutrition['value'], true ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<?php if ( ! empty( $recipe_keywords ) ) : ?>
	<div class="tasty-recipes-keywords" data-tasty-recipes-customization="detail-value-color.color">
		<p><em><strong data-tasty-recipes-customization="detail-label-color.color"><?php esc_html_e( 'Keywords', 'tasty-recipes-lite' ); ?>:</strong> <?php Utils::kses( $recipe_keywords, true ); ?></em></p>
	</div>
<?php endif; ?>

<footer class="tasty-recipes-entry-footer">
	<?php
	/**
	 * Add content inside the footer of the recipe card.
	 *
	 * @since 1.0
	 *
	 * @var array $vars Array of variables to pass to the action.
	 */
	do_action( 'tasty_recipes_card_footer', $vars );
	?>
</footer>

<?php if ( tasty_recipes_is_print() && get_post() ) : ?>
	<div class="tasty-recipes-source-link">
		<p><strong class="tasty-recipes-label"><?php esc_html_e( 'Find it online', 'tasty-recipes-lite' ); ?></strong>: <a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_url( get_permalink( get_the_ID() ) ); ?></a></p>
	</div>
<?php endif; ?>

<?php
/**
 * Template for No Rating icons.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$no_ratings   = isset( $no_ratings ) ? $no_ratings : false;
$average      = isset( $average ) ? $average : 0;
$rating_style = isset( $rating_style ) ? $rating_style : 'outline';
?>
<span class="tasty-recipes-ratings-buttons <?php echo $no_ratings ? 'tasty-recipes-no-ratings-buttons' : ''; ?>"
	data-tr-default-rating="<?php echo esc_attr( $average ); ?>"
	>
<?php
for ( $i = 5; $i >= 1; $i-- ) {
	$is_selected = $average ? (int) ceil( $average ) === $i : false;
	$percentage  = \Tasty_Recipes\Ratings::get_fill_percent( compact( 'i', 'average' ) );

	$aria_label = sprintf(
		// translators: The aria label for a specific star rating.
		_n( 'Rate this recipe %d star', 'Rate this recipe %d stars', $i, 'tasty-recipes-lite' ),
		$i
	);
	?>
	<?php if ( ! $no_ratings ) { ?>
	<input
		aria-label="<?php echo esc_attr( $aria_label ); ?>"
		type="radio"
		name="tasty-recipes-rating"
		class="tasty-recipes-rating"
		id="<?php echo esc_attr( uniqid( 'tasty_recipes_rating_input_' ) ); ?>"
		value="<?php echo absint( $i ); ?>">
	<?php } ?>
	<span class="tasty-recipes-rating" <?php echo $is_selected ? 'data-tr-checked="1"' : ''; ?>>
		<i class="checked" data-rating="<?php echo esc_attr( $i ); ?>">
			<span class="tasty-recipes-rating-<?php echo esc_attr( $rating_style ); ?>" data-tr-clip="<?php echo esc_attr( $percentage ); ?>">
				<?php
				if ( $percentage === 100 || $percentage === 0 ) {
					Tasty_Recipes\Utils::kses( $icons['full'], true );
				} else {
					Tasty_Recipes\Utils::kses( $icons['checked'], true );
				}
				?>
			</span>
			<span class="tasty-recipes-screen-reader">
				<?php
				printf(
					// translators: The number of stars.
					esc_html( _n( '%d Star', '%d Stars', $i, 'tasty-recipes-lite' ) ),
					(int) $i
				);
				?>
			</span>
		</i>
	</span>
<?php } ?>
</span>

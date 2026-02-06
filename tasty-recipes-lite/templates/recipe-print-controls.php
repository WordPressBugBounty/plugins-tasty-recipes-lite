<?php
/**
 * Template for the recipe print controls.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$show_options = apply_filters( 'tasty_recipes_show_print_options', true );
?>

<script type="text/html" id="tmpl-tasty-recipes-print-controls">
	<?php
	wp_enqueue_style( 'tasty-recipes-print-controls' );
	wp_maybe_inline_styles();
	wp_print_styles( 'tasty-recipes-print-controls' );
	?>
	<form id="tr-control-form" method="POST" class="<?php echo esc_attr( $show_options ? 'tasty-recipes-show-controls' : '' ); ?>">
		<fieldset class="tasty-recipes-print-buttons">
			<button id="tasty-recipes-print" type="button"><svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10.6665 5V2.32812C10.6665 2.11719 10.5728 1.92969 10.4321 1.78906L9.37744 0.734375C9.23682 0.59375 9.04932 0.5 8.83838 0.5H2.4165C1.99463 0.5 1.6665 0.851562 1.6665 1.25V5C0.822754 5 0.166504 5.67969 0.166504 6.5V9.125C0.166504 9.33594 0.330566 9.5 0.541504 9.5H1.6665V11.75C1.6665 12.1719 1.99463 12.5 2.4165 12.5H9.9165C10.3149 12.5 10.6665 12.1719 10.6665 11.75V9.5H11.7915C11.979 9.5 12.1665 9.33594 12.1665 9.125V6.5C12.1665 5.67969 11.4868 5 10.6665 5ZM9.1665 11H3.1665V8.75H9.1665V11ZM9.1665 5.75H3.1665V2H7.6665V3.125C7.6665 3.33594 7.83057 3.5 8.0415 3.5H9.1665V5.75ZM10.2915 7.4375C9.96338 7.4375 9.729 7.20312 9.729 6.875C9.729 6.57031 9.96338 6.3125 10.2915 6.3125C10.5962 6.3125 10.854 6.57031 10.854 6.875C10.854 7.20312 10.5962 7.4375 10.2915 7.4375Z" fill="currentColor"/>
</svg> <?php esc_html_e( 'Print', 'tasty-recipes-lite' ); ?></button>
			<button id="tasty-recipes-options" class= "<?php echo esc_attr( $show_options ? 'tasty-hidden' : '' ); ?>" type="button"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.5 0H1.5C0.671875 0 0 0.671875 0 1.5V12.5C0 13.3281 0.671875 14 1.5 14H12.5C13.3281 14 14 13.3281 14 12.5V1.5C14 0.671875 13.3281 0 12.5 0ZM12.3125 12.5H1.6875C1.58438 12.5 1.5 12.4156 1.5 12.3125V1.6875C1.5 1.58438 1.58438 1.5 1.6875 1.5H12.3125C12.4156 1.5 12.5 1.58438 12.5 1.6875V12.3125C12.5 12.4156 12.4156 12.5 12.3125 12.5ZM11 4.875V5.125C11 5.33125 10.8313 5.5 10.625 5.5H6V6.25C6 6.66563 5.66563 7 5.25 7H4.75C4.33437 7 4 6.66563 4 6.25V5.5H3.375C3.16875 5.5 3 5.33125 3 5.125V4.875C3 4.66875 3.16875 4.5 3.375 4.5H4V3.75C4 3.33437 4.33437 3 4.75 3H5.25C5.66563 3 6 3.33437 6 3.75V4.5H10.625C10.8313 4.5 11 4.66875 11 4.875ZM11 8.875V9.125C11 9.33125 10.8313 9.5 10.625 9.5H10V10.25C10 10.6656 9.66562 11 9.25 11H8.75C8.33438 11 8 10.6656 8 10.25V9.5H3.375C3.16875 9.5 3 9.33125 3 9.125V8.875C3 8.66875 3.16875 8.5 3.375 8.5H8V7.75C8 7.33437 8.33438 7 8.75 7H9.25C9.66562 7 10 7.33437 10 7.75V8.5H10.625C10.8313 8.5 11 8.66875 11 8.875Z" fill="currentColor"/>
</svg> <?php esc_html_e( 'Options', 'tasty-recipes-lite' ); ?></button>
		</fieldset>
		<?php
		$defaults = array(
			'display'   => array(
				'images',
				'description',
				'notes',
				'nutrition',
			),
			'text_size' => 'medium',
		);

		/**
		 * Allows the print view default values to be modified.
		 *
		 * @var array $defaults Existing default values.
		 */
		$defaults = apply_filters( 'tasty_recipes_print_view_defaults', $defaults );

		$print_view_options = tasty_recipes_get_print_view_options();
		?>
		<fieldset class="tasty-recipes-print-display-controls">
			<legend><?php esc_html_e( 'Include in print view:', 'tasty-recipes-lite' ); ?></legend>
			<?php
			if ( empty( $recipe_nutrifox_embed ) && empty( $recipe_nutrition ) ) {
				unset( $print_view_options['nutrition'] );
			}
			foreach ( $print_view_options as $key => $label ) {
				$print_view_options_id = 'tasty-recipes-print-display-' . $key;
				?>
				<input class="tasty-recipes-print-display" type="checkbox" value="<?php echo esc_attr( $key ); ?>" name="display" id="<?php echo esc_attr( $print_view_options_id ); ?>" <?php checked( in_array( $key, $defaults['display'], true ) ); ?>>
				<label for="<?php echo esc_attr( $print_view_options_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php
			}
			?>
		</fieldset>
		<fieldset class="tasty-recipes-print-text-size-controls">
			<legend><?php esc_html_e( 'Text size:', 'tasty-recipes-lite' ); ?></legend>
			<div>
			<?php
			$options = array(
				'small'  => esc_html__( 'Small', 'tasty-recipes-lite' ),
				'medium' => esc_html__( 'Medium', 'tasty-recipes-lite' ),
				'large'  => esc_html__( 'Large', 'tasty-recipes-lite' ),
			);
			foreach ( $options as $key => $label ) {
				$text_size_options_id = 'tasty-recipes-print-text-size-' . $key;
				?>
				<input class="tasty-recipes-print-text-size" type="radio" value="<?php echo esc_attr( $key ); ?>" name="text_size" id="<?php echo esc_attr( $text_size_options_id ); ?>" <?php checked( $key, $defaults['text_size'] ); ?>>
				<label for="<?php echo esc_attr( $text_size_options_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php
			}
			?>
			</div>
		</fieldset>
	</form>
</script>

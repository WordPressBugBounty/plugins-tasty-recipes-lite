<?php
/**
 * Tasty Recipes control for Elementor.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations\Elementor;

/**
 * Tasty Recipes control for Elementor.
 */
class Recipe_Control extends \Elementor\Base_Data_Control {

	/**
	 * Gets the control name.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'tasty-recipe-control';
	}

	/**
	 * Enqueues the necessary scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue() {
		global $post;

		wp_enqueue_editor();
		$time = filemtime( dirname( TASTY_RECIPES_LITE_FILE ) . '/assets/js/elementor.js' );
		wp_enqueue_script(
			'tasty-recipe-elementor-control',
			plugins_url( 'assets/js/elementor.js?r=' . (int) $time, TASTY_RECIPES_LITE_FILE ),
			array( 'elementor-editor' ),
			'1.0.0',
			true
		);

		$data = array(
			'recipeDataStore' => array(),
		);
		if ( $post ) {
			foreach ( \Tasty_Recipes::get_recipes_for_post( $post->ID ) as $recipe ) {
				/**
				 * Permit modification of the recipe JSON before it's returned.
				 *
				 * @param array $recipe_json Existing recipe JSON blob.
				 */
				$recipe_json = apply_filters( 'tasty_recipes_shortcode_response_recipe_json', $recipe->to_json() );
				// These fields are stored without paragraph tags,
				// so they need to be added for TinyMCE compat.
				foreach ( array(
					'description',
					'ingredients',
					'instructions',
					'notes',
				) as $field ) {
					$recipe_json[ $field ] = wpautop( $recipe_json[ $field ] );
				}
				$data['recipeDataStore'][ $recipe->get_id() ] = $recipe_json;
			}
		}
		wp_localize_script(
			'tasty-recipe-elementor-control',
			'tastyRecipesElementorControl',
			$data
		);
	}

	/**
	 * Renders the content of the control.
	 *
	 * @return void
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<div class="elementor-control-input-wrapper">
				<button type="button" class="elementor-button elementor-button-default" data-event="tasty-recipes:edit-recipe">
					<?php esc_html_e( 'Edit Recipe', 'tasty-recipes-lite' ); ?>
				</button>
				<input id="<?php echo esc_attr( $control_uid ); ?>" data-setting="{{ data.name }}" type="hidden">
			</div>
		</div>
		<?php
	}
}

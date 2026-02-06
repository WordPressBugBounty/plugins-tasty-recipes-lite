<?php
/**
 * Manages the recipe templates.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Designs;

use Tasty_Recipes;
use Tasty_Recipes\Ratings;
use Tasty_Recipes\Utils;
use Tasty_Recipes\Assets;
use Tasty_Recipes\Admin;
use Tasty_Recipes\Shortcodes;

/**
 * Manages the recipe templates.
 */
class Template {

	/**
	 * Array of template objects.
	 *
	 * @var array
	 */
	protected static $template_objects = array();

	/**
	 * Get filtered design template objects.
	 *
	 * @return void
	 */
	private static function set_template_objects() {
		if ( ! empty( self::$template_objects ) ) {
			return;
		}

		$template_objects = array(
			'default'        => Default_Template::instance(),
			'simple'         => Simple::instance(),
			'snap'           => Snap::instance(),
			'bold'           => Bold::instance(),
			'fresh'          => Fresh::instance(),
			'elegant'        => Elegant::instance(),
			'modern-compact' => Modern_Compact::instance(),
		);

		self::$template_objects = (array) apply_filters( 'tasty_recipes_template_objects', $template_objects );
	}

	/**
	 * Get templates as an array, key is template ID and value is the template name.
	 *
	 * @param array $parts Array of parts to include in the return.
	 *
	 * @return array
	 */
	public static function get_all_templates( $parts = array() ) {
		self::set_template_objects();

		$templates = [];
		if ( empty( self::$template_objects ) ) {
			return $templates;
		}

		if ( empty( $parts ) ) {
			$parts = array( 'name' );
		}
		foreach ( self::$template_objects as $template ) {
			if ( count( $parts ) === 1 ) {
				$templates[ $template->get_id() ] = $template->{ 'get_template_' . $parts[0] }();
				continue;
			}

			$templates[ $template->get_id() ] = array();
			foreach ( $parts as $part ) {
				$templates[ $template->get_id() ][ $part ] = $template->{ 'get_template_' . $part }();
			}
		}

		return $templates;
	}

	/**
	 * Get template object with its ID.
	 *
	 * @param string $template Template ID or 'current'.
	 *
	 * @return Abstract_Template
	 */
	public static function get_object_by_name( $template ) {
		$custom_template = self::has_custom_template();
		if ( $custom_template ) {
			return $custom_template;
		}

		if ( $template === 'current' ) {
			$template = get_option( Tasty_Recipes::TEMPLATE_OPTION, '' );
			if ( $template && self::is_locked( $template ) ) {
				// If the selected template is locked, use the default.
				$template = '';
			}
		}

		$templates = self::get_all_templates();

		if ( ! $template || empty( $templates ) || ! isset( $templates[ $template ] ) ) {
			return Default_Template::instance();
		}

		return self::$template_objects[ $template ];
	}

	/**
	 * Check if a custom template exists in the theme.
	 *
	 * @return Abstract_Template|false
	 */
	public static function has_custom_template() {
		$custom = Custom::instance();
		$file   = $custom->get_template_path();
		return file_exists( $file ) ? $custom : false;
	}

	/**
	 * Check if a template is locked.
	 *
	 * @since 1.0
	 *
	 * @param string $template Template ID.
	 *
	 * @return bool
	 */
	private static function is_locked( $template ) {
		if ( ! $template ) {
			return false;
		}

		$locked_templates = self::get_locked_templates();
		return in_array( $template, $locked_templates, true );
	}

	/**
	 * Get ids of locked templates.
	 *
	 * @return array
	 */
	public static function get_locked_templates() {
		self::set_template_objects();

		$locked_templates = array();
		foreach ( self::$template_objects as $template ) {
			if ( $template->is_pro() ) {
				$locked_templates[] = $template->get_id();
			}
		}

		return $locked_templates;
	}

	/**
	 * Gets any customizations for the recipe template.
	 *
	 * @since 1.0
	 *
	 * @param string $name          Name of the customization.
	 * @param string $custom_design Design being rendered.
	 *
	 * @return string
	 */
	public static function get_template_customization( $name, $custom_design = '' ) {
		$template_obj = self::get_object_by_name( $custom_design );
		return $template_obj->get_customized( $name );
	}

	/**
	 * Handles an AJAX request to preview a recipe template.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_preview_recipe_card() { // phpcs:ignore SlevomatCodingStandard
		$nonce = Utils::request_param( 'nonce', 'sanitize_key' );
		if ( empty( $nonce ) || ! current_user_can( Admin::CAPABILITY ) || ! wp_verify_nonce( $nonce, Admin::NONCE_KEY ) ) {
			wp_die( esc_html__( "Sorry, you don't have permission to do this.", 'tasty-recipes-lite' ) );
		}
		header( 'Content-Type: text/html' );
		$wrapper_classes = array(
			'tasty-recipes',
			'tasty-recipes-display',
			'tasty-recipes-has-image',
		);

		Assets::dequeue_non_tasty_assets();

		$show_plug = get_option( Tasty_Recipes::SHAREASALE_OPTION ) || get_option( Tasty_Recipes::POWEREDBY_OPTION );
		if ( $show_plug ) {
			$wrapper_classes[] = 'tasty-recipes-has-plug';
		}

		$get_template               = Utils::get_param( 'template', 'sanitize_key' );
		$get_star_ratings_style     = Utils::get_param( 'star_ratings_style', 'sanitize_key' );
		$get_nutrifox_display_style = Utils::get_param( 'nutrifox_display_style', 'sanitize_key' );

		$custom_design          = ! empty( $get_template ) && array_key_exists( $get_template, self::get_all_templates() ) ? $get_template : '';
		$star_ratings_style     = ! empty( $get_star_ratings_style ) && in_array( $get_star_ratings_style, array( 'solid', 'outline' ), true ) ? $get_star_ratings_style : null;
		$show_nutrifox          = ! empty( $get_nutrifox_display_style ) && in_array( $get_nutrifox_display_style, array( 'label', 'card' ), true );
		$nutrifox_display_style = $show_nutrifox ? $get_nutrifox_display_style : null;

		$styles  = ! empty( $custom_design ) ? Shortcodes::get_styles_as_string( $custom_design ) : '';
		$styles .= Assets::get_css_vars();
		$styles .= Utils::get_contents( 'assets/dist/recipe-card-preview.css', 'path' );

		$template        = 'recipe/tasty-recipes';
		$template_object = self::get_object_by_name( $custom_design );
		$custom_path     = $template_object->get_template_path();
		if ( file_exists( $custom_path ) ) {
			$template = $custom_path;
		}

		$stylesheet = get_option( 'stylesheet' );
		// Clean up Feast directory names.
		$stylesheet  = preg_replace( '#-v[\d]+$#', '', $stylesheet );
		$compat_file = dirname( TASTY_RECIPES_LITE_FILE ) . '/assets/css/theme-compat-card-previews/' . $stylesheet . '.css';
		if ( file_exists( $compat_file ) ) {
			$styles .= Utils::get_contents( $compat_file );
		}

		$recipe      = new \stdClass();
		$recipe_json = array();

		if ( 'card' === $nutrifox_display_style ) {
			$nutrifox_id           = '';
			$recipe_nutrition      = array(
				'serving_size' =>
				array(
					'label' => 'Serving Size',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-serving-size">12</span>',
				),
				'calories'     =>
				array(
					'label' => 'Calories',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-calories">238</span>',
				),
				'sugar'        =>
				array(
					'label' => 'Sugar',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-sugar">3.8g</span>',
				),
				'sodium'       =>
				array(
					'label' => 'Sodium',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-sodium">99.3mg</span>',
				),
				'fat'          =>
				array(
					'label' => 'Fat',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-fat">22.6g</span>',
				),
				'fiber'        =>
				array(
					'label' => 'Fiber',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-fiber">2.7g</span>',
				),
				'protein'      =>
				array(
					'label' => 'Protein',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-protein">3.9g</span>',
				),
			);
			$recipe_nutrifox_embed = '';
		} else {
			$recipe_nutrition      = array();
			$nutrifox_id           = 26460;
			$recipe_nutrifox_embed = Shortcodes::nutrifox_iframe( $nutrifox_id );
		}

		$template_vars = array(
			'recipe'                        => $recipe,
			'recipe_styles'                 => '',
			'recipe_scripts'                => '',
			'recipe_json'                   => $recipe_json,
			'recipe_title'                  => 'Almond Butter Cups',
			'recipe_image'                  => '<img width="183" height="183" ' .
				'src="' . esc_url( plugins_url( 'assets/images/Almond-Butter-Cups-Recipe-370x370.jpg', TASTY_RECIPES_LITE_FILE ) ) . '" ' .
				'class="attachment-thumbnail size-thumbnail" alt="Chocolate Chip Cookies on parchment paper." ' .
				'loading="lazy" data-pin-nopin="true">',
			'recipe_rating_icons'           => Ratings::get_rendered_rating(
				4.6,
				'star-color.color',
				$star_ratings_style
			),
			'recipe_rating_label'           => '<span data-tasty-recipes-customization="detail-label-color.color" class="rating-label">' .
				'<span class="average">4.6</span> from <span class="count">1376</span> reviews</span>',
			'recipe_author_name'            => '',
			'recipe_details'                => array(
				'author'     =>
				array(
					'label' => 'Author',
					'value' => '<a data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-author-name" ' .
						'href="https://pinchofyum.com/about">Pinch of Yum</a>',
					'class' => 'author',
				),
				'prep_time'  =>
				array(
					'label' => 'Prep Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-prep-time">10 mins</span>',
					'class' => 'prep-time',
				),
				'cook_time'  =>
				array(
					'label' => 'Cook Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-cook-time">1 hour (for freezing)</span>',
					'class' => 'cook-time',
				),
				'total_time' =>
				array(
					'label' => 'Total Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-total-time">1 hour, 10 minutes</span>',
					'class' => 'total-time',
				),
				'yield'      => array(
					'label' => 'Yield',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-yield">' .
						'12 almond butter cups</span>',
					'class' => 'yield',
				),
				'category'   =>
				array(
					'label' => 'Category',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-category">Dessert</span>',
					'class' => 'category',
				),
				'method'     =>
				array(
					'label' => 'Method',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-method">No bake</span>',
					'class' => 'method',
				),
				'cuisine'    =>
				array(
					'label' => 'Cuisine',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-cuisine">American</span>',
					'class' => 'cuisine',
				),
			),
			'recipe_description'            => '<p>Almond Butter Cups: made with five ingredients and no refined sugar. So creamy, rich, and yummy!</p>',
			'recipe_ingredients'            => self::ingredients_example(),
			'recipe_instructions_has_video' => false,
			'recipe_instructions'           => self::instructions_example(),
			'recipe_keywords'               => '',
			'recipe_notes'                  => self::notes_example(),
			'recipe_nutrifox_id'            => $nutrifox_id,
			'recipe_nutrifox_embed'         => $recipe_nutrifox_embed,
			'recipe_video_embed'            => '',
			'recipe_nutrition'              => $recipe_nutrition,
			'recipe_hidden_nutrition'       => array(),
			'copy_ingredients'              => false,
			'first_button'                  => Shortcodes::get_card_button( $recipe, 'first', $custom_design ),
			'second_button'                 => Shortcodes::get_card_button( $recipe, 'second', $custom_design ),
			'template'                      => $custom_design,
			'template_object'               => ! empty( $template_object ) ? $template_object : false,
		);
		/**
		 * Filter the template vars for the recipe card preview.
		 *
		 * @since 1.0
		 *
		 * @param array $template_vars Array of template vars.
		 */
		$template_vars = apply_filters( 'tasty_recipes_preview_vars', $template_vars );

		self::enqueue_editor_styles();

		wp_enqueue_style( 'tasty-recipes-main' );
		wp_add_inline_style( 'tasty-recipes-main', $styles );

		$ret            = '<!DOCTYPE html><html><head>' .
			self::load_enqueued_assets() .
			'</head><body>';
		$ret           .= '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		$customizations = Shortcodes::get_card_container_customizations( $custom_design );
		if ( $customizations && ! $template_object->is_pro() ) {
			$ret .= ' data-tasty-recipes-customization="' . esc_attr( $customizations ) . '"';
		}
		$ret .= '>' . PHP_EOL;
		$ret .= Tasty_Recipes::get_template_part( $template, $template_vars );
		$ret .= '</div>';

		wp_enqueue_script(
			'tasty-recipe-card-preview',
			plugins_url( 'assets/dist/recipe-card-preview-js.build.js', TASTY_RECIPES_LITE_FILE ),
			array(),
			TASTY_RECIPES_LITE_VERSION,
			true
		);
		$ret .= self::load_enqueued_assets();
		$ret .= '</body></html>';

		// Don't escape this since it includes js, css, and unusual html from above.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters( 'tasty_recipes_recipe_card_output', $ret, 'admin', compact( 'recipe' ) );

		exit;
	}

	/**
	 * Get the html for the ingredients list.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function ingredients_example() {
		$ingredients = '<ul>' .
			'<li><span data-amount="3/4" data-unit="cup">3/4 cup</span> melted coconut oil</li>' .
			'<li><span data-amount="1/2" data-unit="cup">1/2 cup</span> cocoa powder</li>' .
			'<li><span data-amount="2" data-unit="tablespoon">2 tbsp.</span> natural sweetener (agave or maple syrup)</li>' .
			'<li><span data-amount="3/4" data-unit="cup">3/4 cup</span> almond butter</li>' .
			'<li><span data-amount="" data-unit=""></span>pinch of sea salt, plus more for topping</li>' .
			'</ul>';
		$ingredients = apply_filters( 'tasty_recipes_the_content', $ingredients, 'ingredients' );
		return $ingredients;
	}

	/**
	 * Get HTMl for the instructions box.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function instructions_example() {
		return '<ol>' .
			'<li id="instruction-step-1">Whisk the coconut oil, cocoa powder, sweetener, and a pinch of salt.</li>' .
			'<li id="instruction-step-2">Fill a regular size muffin tin with paper liners. ' .
			'Pour a small amount of the cocoa mixture (1-2 tablespoons) into the paper cups. ' .
			'Drop a small spoonful of the almond butter (2-3 teaspoons) into the center of each cup. ' .
			'Divide remaining chocolate amongst the cups.</li>' .
			'<li id="instruction-step-3">If almond butter is sticking up, just gently press it down so each cup has a smooth top layer. ' .
			'Sprinkle each almond butter cup with a pinch of coarse sea salt. Freeze for one hour or until solid. YUM TOWN.</li>' .
			'</ol>';
	}

	/**
	 * Get HTML for the notes box.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function notes_example() {
		return '<p>These are very adaptable – I’ve used as much as 3/4 cup cocoa, and as little as 2 tablespoons agave. ' .
			'It just depends on how sweet / dark you want them to be. I’ve also used peanut butter which is (obviously) delicious!</p>';
	}

	/**
	 * Enqueue editor styles for the recipe card preview.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private static function enqueue_editor_styles() {
		global $editor_styles;

		if ( ! $editor_styles || ! current_theme_supports( 'editor-styles' ) ) {
			return;
		}

		foreach ( $editor_styles as $style ) {
			if ( ! preg_match( '~^(https?:)?//~', $style ) ) {
				$style = get_theme_file_uri( $style );
			}
			wp_enqueue_style( 'tasty-recipe-preview', $style, array(), TASTY_RECIPES_LITE_VERSION );
		}
	}

	/**
	 * Get the html for enqueued assets for the recipe card preview when doing ajax.
	 *
	 * @since 3.12.3
	 *
	 * @return string
	 */
	private static function load_enqueued_assets() {
		ob_start();
		wp_print_styles();
		wp_print_scripts();
		$assets = ob_get_clean();
		if ( ! $assets ) {
			$assets = '';
		}

		return $assets;
	}

	/**
	 * Get default colors for a specific template.
	 * 
	 * @since 1.0.2
	 *
	 * @param string $template Template name.
	 * 
	 * @return array
	 */
	public static function get_default_colors_for_template( $template ) {
		$is_snap = $template === 'snap';
		
		/**
		 * Filter the default colors for a specific template.
		 *
		 * @since 1.0.2
		 *
		 * @param array $colors Array of default colors.
		 * @param string $template Template name.
		 */
		return apply_filters(
			'tasty_recipes_default_colors_for_template',
			array(
				'primary_color'      => $is_snap ? '#000000' : '#FFFFFF',
				'secondary_color'    => '#FFFFFF',
				'icon_color'         => '#000000',
				'button_color'       => $is_snap ? '#000000' : '#F9F9F9',
				'button_text_color'  => $is_snap ? '#FFFFFF' : '#AAAAAA',
				'detail_label_color' => '#000000',
				'detail_value_color' => '#000000',
				'h2_color'           => '#000000',
				'h3_color'           => '#000000',
				'body_color'         => '#000000',
				'star_color'         => '#F2B955',
			),
			$template
		);
	}
}

<?php
// phpcs:ignoreFile SlevomatCodingStandard.Files.FileLength.FileTooLong
// TODO: Separate code in multiple files.
/**
 * Model for the Recipe object.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Objects;

use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Utils;

/**
 * Model for the Recipe object.
 */
class Recipe {

	/**
	 * Post object associated with the Recipe.
	 *
	 * @var object
	 */
	protected $post;

	/**
	 * Name of the post type.
	 *
	 * @var string
	 */
	protected static $post_type = 'tasty_recipe';

	/**
	 * Get all of the recipe attributes as a merged array.
	 *
	 * @return array
	 */
	public static function get_attributes() {
		return array_merge(
			self::get_general_attributes(),
			self::get_cooking_attributes(),
			self::get_nutrition_attributes()
		);
	}

	/**
	 * Get all of the attribute keys.
	 *
	 * @return array
	 */
	public static function get_attribute_keys() {
		$attributes = self::get_attributes();
		return array_keys( $attributes );
	}

	/**
	 * Get general recipe attributes.
	 *
	 * @return array
	 */
	public static function get_general_attributes() {
		return array(
			'id'                          => array(
				'label' => __( 'ID', 'tasty-recipes-lite' ),
			),
			'title'                       => array(
				'label'             => __( 'Title', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'author_name'                 => array(
				'label'             => __( 'Author Name', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'image_id'                    => array(
				'label' => __( 'Image ID', 'tasty-recipes-lite' ),
			),
			'description'                 => array(
				'label'             => __( 'Description', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'description_video_settings'  => array(
				'label'             => __( 'Description Video Settings', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'ingredients'                 => array(
				'label'             => __( 'Ingredients', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'ingredients_video_settings'  => array(
				'label'             => __( 'Ingredients Video Settings', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'instructions'                => array(
				'label'             => __( 'Instructions', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'instructions_video_settings' => array(
				'label'             => __( 'Instructions Video Settings', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'notes'                       => array(
				'label'             => __( 'Notes', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'wp_filter_post_kses',
			),
			'notes_video_settings'        => array(
				'label'             => __( 'Notes Video Settings', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'keywords'                    => array(
				'label'             => __( 'Keywords', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
				'tooltip'           => __(
					'Keywords help Google find your recipes. Include keywords that are not also a category or cuisine ie summer, Halloween, quick, nutmeg crust.',
					'tasty-recipes-lite'
				),
			),
			'nutrifox_id'                 => array(
				'label'             => __( 'Nutrifox ID', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'intval',
			),
			'video_url'                   => array(
				'label'             => __( 'Video URL or Shortcode', 'tasty-recipes-lite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'average_rating'              => array(
				'label' => __( 'Average Rating', 'tasty-recipes-lite' ),
			),
			'total_reviews'               => array(
				'label' => __( 'Total Reviews', 'tasty-recipes-lite' ),
			),
		);
	}

	/**
	 * Get recipe cooking attributes.
	 *
	 * @return array
	 */
	public static function get_cooking_attributes() {
		return array(
			'prep_time'             => array(
				'label'    => __( 'Prep Time', 'tasty-recipes-lite' ),
				'property' => 'prepTime',
			),
			'additional_time_label' => array(),
			'additional_time_value' => array(),
			'cook_time'             => array(
				'label'    => __( 'Cook Time', 'tasty-recipes-lite' ),
				'property' => 'cookTime',
			),
			'total_time'            => array(
				'label'       => __( 'Total Time', 'tasty-recipes-lite' ),
				'property'    => 'totalTime',
				'object_key'  => 'total_time_raw',
				'placeholder' => __( 'Default to prep + cook.', 'tasty-recipes-lite' ),
			),
			'yield'                 => array(
				'label'    => __( 'Yield', 'tasty-recipes-lite' ),
				'property' => 'recipeYield',
			),
			'category'              => array(
				'label'    => __( 'Category', 'tasty-recipes-lite' ),
				'property' => 'recipeCategory',
				'taxonomy' => 'tasty_recipe_category',
			),
			'method'                => array(
				'label'    => __( 'Method', 'tasty-recipes-lite' ),
				'property' => 'cookingMethod',
				'taxonomy' => 'tasty_recipe_method',
			),
			'cuisine'               => array(
				'label'    => __( 'Cuisine', 'tasty-recipes-lite' ),
				'property' => 'recipeCuisine',
				'taxonomy' => 'tasty_recipe_cuisine',
			),
			'diet'                  => array(
				'label'    => __( 'Diet', 'tasty-recipes-lite' ),
				'property' => 'suitableForDiet',
				'taxonomy' => 'tasty_recipe_diet',
			),
		);
	}

	/**
	 * Get recipe nutrition attributes.
	 *
	 * @return array
	 */
	public static function get_nutrition_attributes() {
		$attributes = array(
			'serving_size'    => array(
				'label'        => __( 'Serving Size', 'tasty-recipes-lite' ),
				'property'     => 'servingSize',
				'nutrifox_key' => 'serving_size',
			),
			'calories'        => array(
				'label'        => __( 'Calories', 'tasty-recipes-lite' ),
				'property'     => 'calories',
				'nutrifox_key' => 'ENERC_KCAL',
			),
			'sugar'           => array(
				'label'        => __( 'Sugar', 'tasty-recipes-lite' ),
				'property'     => 'sugarContent',
				'nutrifox_key' => 'SUGAR',
			),
			'sodium'          => array(
				'label'        => __( 'Sodium', 'tasty-recipes-lite' ),
				'property'     => 'sodiumContent',
				'nutrifox_key' => 'NA',
			),
			'fat'             => array(
				'label'        => __( 'Fat', 'tasty-recipes-lite' ),
				'property'     => 'fatContent',
				'nutrifox_key' => 'FAT',
			),
			'saturated_fat'   => array(
				'label'        => __( 'Saturated Fat', 'tasty-recipes-lite' ),
				'property'     => 'saturatedFatContent',
				'nutrifox_key' => 'FASAT',
			),
			'unsaturated_fat' => array(
				'label'    => __( 'Unsaturated Fat', 'tasty-recipes-lite' ),
				'property' => 'unsaturatedFatContent',
			),
			'trans_fat'       => array(
				'label'        => __( 'Trans Fat', 'tasty-recipes-lite' ),
				'property'     => 'transFatContent',
				'nutrifox_key' => 'FATRN',
			),
			'carbohydrates'   => array(
				'label'        => __( 'Carbohydrates', 'tasty-recipes-lite' ),
				'property'     => 'carbohydrateContent',
				'nutrifox_key' => 'CHOCDF',
			),
			'fiber'           => array(
				'label'        => __( 'Fiber', 'tasty-recipes-lite' ),
				'property'     => 'fiberContent',
				'nutrifox_key' => 'FIBTG',
			),
			'protein'         => array(
				'label'        => __( 'Protein', 'tasty-recipes-lite' ),
				'property'     => 'proteinContent',
				'nutrifox_key' => 'PROCNT',
			),
			'cholesterol'     => array(
				'label'        => __( 'Cholesterol', 'tasty-recipes-lite' ),
				'property'     => 'cholesterolContent',
				'nutrifox_key' => 'CHOLE',
			),
		);
		foreach ( $attributes as $key => $attribute ) {
			$attributes[ $key ]['is_pro'] = true;
		}

		/**
		 * Allows the nutrition attributes to be modified.
		 *
		 * @param array $attributes Attributes to be filtered.
		 */
		return apply_filters( 'tasty_recipes_nutrition_attributes', $attributes );
	}

	/**
	 * Get keys for the nutrition attributes.
	 *
	 * @return array
	 */
	public static function get_nutrition_attribute_keys() {
		$attributes = self::get_nutrition_attributes();
		return array_keys( $attributes );
	}

	/**
	 * Instantiate a recipe object.
	 *
	 * @param object $post Post object associated with the recipe.
	 *
	 * @return void
	 */
	protected function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * Get a recipe by its id.
	 *
	 * @param int $id Recipe id.
	 *
	 * @return false|Recipe
	 */
	public static function get_by_id( $id ) {
		if ( empty( $id ) ) {
			return false;
		}
		$post = get_post( $id );
		if ( $post && self::$post_type === $post->post_type ) {
			return new Recipe( $post );
		}
		return false;
	}

	/**
	 * Remove extra tags from the start and end of a setting.
	 *
	 * @since 3.12.3
	 *
	 * @param string $name The name of the meta field.
	 *
	 * @return string
	 */
	protected function get_clean_meta( $name ) {
		return $this->remove_empty_tags_from_start_end( $this->get_meta( $name ) );
	}

	/**
	 * Remove empty tags from the start and end of a string and save.
	 *
	 * @since 3.12.3
	 *
	 * @param string $name The name of the meta field.
	 * @param string $value The value to save.
	 *
	 * @return void
	 */
	protected function set_clean_meta( $name, $value ) {
		$this->set_meta( $name, $this->remove_empty_tags_from_start_end( $value ) );
	}

	/**
	 * Get the ID for the post.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->get_field( 'ID' );
	}

	/**
	 * Get the title for the recipe.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->get_field( 'post_title' );
	}

	/**
	 * Print the name for the recipe.
	 *
	 * @return void
	 */
	public function the_title() {
		Utils::kses( apply_filters( 'tasty_recipes_the_title', $this->get_title() ), true );
	}

	/**
	 * Set the title of the recipe.
	 *
	 * @param string $title Recipe title.
	 *
	 * @return void
	 */
	public function set_title( $title ) {
		$this->set_field( 'post_title', $title );
	}

	/**
	 * Get the author name for the recipe.
	 *
	 * @return string
	 */
	public function get_author_name() {
		return $this->get_meta( 'author_name' );
	}

	/**
	 * Set the author name for the recipe.
	 *
	 * @param string $author_name Recipe author name.
	 *
	 * @return void
	 */
	public function set_author_name( $author_name ) {
		$this->set_meta( 'author_name', $author_name );
	}

	/**
	 * Get the description for the recipe.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->get_clean_meta( 'description' );
	}

	/**
	 * Print the description for the recipe.
	 *
	 * @return void
	 */
	public function the_description() {
		Utils::kses( apply_filters( 'tasty_recipes_the_content', $this->get_description(), 'description' ), true );
	}

	/**
	 * Set the description for the recipe.
	 *
	 * @param string $description Description value.
	 *
	 * @return void
	 */
	public function set_description( $description ) {
		$this->set_clean_meta( 'description', $description );
	}

	/**
	 * Get the description video settings for the recipe.
	 *
	 * @return string
	 */
	public function get_description_video_settings() {
		return $this->get_meta( 'description_video_settings' );
	}

	/**
	 * Set the description video settings for the recipe.
	 *
	 * @param string $video_settings Video settings value.
	 *
	 * @return void
	 */
	public function set_description_video_settings( $video_settings ) {
		$this->set_meta( 'description_video_settings', $video_settings );
	}

	/**
	 * Get the ingredients for the recipe.
	 *
	 * @return string
	 */
	public function get_ingredients() {
		return $this->get_clean_meta( 'ingredients' );
	}

	/**
	 * Print the ingredients for the recipe.
	 *
	 * @return void
	 */
	public function the_ingredients() {
		Utils::kses( apply_filters( 'tasty_recipes_the_content', $this->get_ingredients(), 'ingredients' ), true );
	}

	/**
	 * Set the ingredients for the recipe.
	 *
	 * @param string $ingredients Ingredients value.
	 *
	 * @return void
	 */
	public function set_ingredients( $ingredients ) {
		$this->set_clean_meta( 'ingredients', $ingredients );
	}

	/**
	 * Get the ingredients video settings for the recipe.
	 *
	 * @return string
	 */
	public function get_ingredients_video_settings() {
		return $this->get_meta( 'ingredients_video_settings' );
	}

	/**
	 * Set the ingredients video settings for the recipe.
	 *
	 * @param string $video_settings Video settings value.
	 *
	 * @return void
	 */
	public function set_ingredients_video_settings( $video_settings ) {
		$this->set_meta( 'ingredients_video_settings', $video_settings );
	}

	/**
	 * Get the instructions for the recipe.
	 *
	 * @return string
	 */
	public function get_instructions() {
		return $this->get_clean_meta( 'instructions' );
	}

	/**
	 * Print the instructions for the recipe.
	 *
	 * @return void
	 */
	public function the_instructions() {
		Utils::kses( apply_filters( 'tasty_recipes_the_content', $this->get_instructions(), 'instructions' ), true );
	}

	/**
	 * Set the instructions for the recipe.
	 *
	 * @param string $instructions Instructions value.
	 *
	 * @return void
	 */
	public function set_instructions( $instructions ) {
		$this->set_clean_meta( 'instructions', $instructions );
	}

	/**
	 * Get the instructions video settings for the recipe.
	 *
	 * @return string
	 */
	public function get_instructions_video_settings() {
		return $this->get_meta( 'instructions_video_settings' );
	}

	/**
	 * Set the instructions video settings for the recipe.
	 *
	 * @param string $video_settings Video settings value.
	 *
	 * @return void
	 */
	public function set_instructions_video_settings( $video_settings ) {
		$this->set_meta( 'instructions_video_settings', $video_settings );
	}

	/**
	 * Get the notes for the recipe.
	 *
	 * @return string
	 */
	public function get_notes() {
		return $this->get_clean_meta( 'notes' );
	}

	/**
	 * Print the notes for the recipe.
	 *
	 * @return void
	 */
	public function the_notes() {
		Utils::kses( apply_filters( 'tasty_recipes_the_content', $this->get_notes(), 'notes' ), true );
	}

	/**
	 * Set the notes for the recipe.
	 *
	 * @param string $notes Notes value.
	 *
	 * @return void
	 */
	public function set_notes( $notes ) {
		$this->set_clean_meta( 'notes', $notes );
	}

	/**
	 * Get the notes video settings for the recipe.
	 *
	 * @return string
	 */
	public function get_notes_video_settings() {
		return $this->get_meta( 'notes_video_settings' );
	}

	/**
	 * Set the notes video settings for the recipe.
	 *
	 * @param string $video_settings Video settings value.
	 *
	 * @return void
	 */
	public function set_notes_video_settings( $video_settings ) {
		$this->set_meta( 'notes_video_settings', $video_settings );
	}

	/**
	 * Get the keywords for the recipe.
	 *
	 * @return string
	 */
	public function get_keywords() {
		return $this->get_meta( 'keywords' );
	}

	/**
	 * Set the keywords for the recipe.
	 *
	 * @param string $keywords Keywords value.
	 *
	 * @return void
	 */
	public function set_keywords( $keywords ) {
		$this->set_meta( 'keywords', $keywords );
	}

	/**
	 * Get the Nutrifox id for the recipe.
	 *
	 * @return int
	 */
	public function get_nutrifox_id() {
		return $this->get_meta( 'nutrifox_id' );
	}

	/**
	 * Set the Nutrifox id for the recipe.
	 *
	 * @param int $nutrifox_id Nutrifox ID value.
	 *
	 * @return void
	 */
	public function set_nutrifox_id( $nutrifox_id ) {
		$this->set_meta( 'nutrifox_id', $nutrifox_id );
	}

	/**
	 * Get the Nutrifox response for the recipe.
	 *
	 * @return array
	 */
	public function get_nutrifox_response() {
		return $this->get_meta( 'nutrifox_response' );
	}

	/**
	 * Set the Nutrifox response for the recipe.
	 *
	 * @param array $nutrifox_response Nutrifox response value.
	 *
	 * @return void
	 */
	public function set_nutrifox_response( $nutrifox_response ) {
		$this->set_meta( 'nutrifox_response', $nutrifox_response );
	}

	/**
	 * Get formatted value for Nutrifox data.
	 *
	 * @param string $name Attribute name.
	 *
	 * @return false|mixed
	 */
	public function get_formatted_nutrifox_value( $name ) {
		$nutrifox = $this->get_nutrifox_response();
		if ( ! $nutrifox ) {
			return false;
		}
		$attributes = self::get_nutrition_attributes();
		$meta       = isset( $attributes[ $name ] ) ? $attributes[ $name ] : array();
		if ( 'serving_size' === $name ) {
			$value = $nutrifox['serving_size'];
		} elseif ( isset( $meta['nutrifox_key'] ) && isset( $nutrifox['nutrients'][ $meta['nutrifox_key'] ] ) ) {
			$value = $nutrifox['nutrients'][ $meta['nutrifox_key'] ]['value'];
			if ( $value ) {
				$value = round( $value / $nutrifox['servings'], 1 );
			}
			if ( 'calories' === $name ) {
				$value = (string) round( $value );
			} elseif ( in_array( $name, array( 'cholesterol', 'sodium' ), true ) ) {
				$value .= ' mg';
			} else {
				$value .= ' g';
			}
		} else {
			return false;
		}
		return $value;
	}

	/**
	 * Get the video URL for the recipe.
	 *
	 * @return string
	 */
	public function get_video_url() {
		return $this->get_meta( 'video_url' );
	}

	/**
	 * Set the video url for the recipe.
	 *
	 * @param string $video_url Video URL value.
	 *
	 * @return void
	 */
	public function set_video_url( $video_url ) {
		$this->set_meta( 'video_url', $video_url );
	}

	/**
	 * Get the video URL response for the recipe.
	 *
	 * @return false|object
	 */
	public function get_video_url_response() {
		$video = $this->get_meta( 'video_url_response' );
		return $video ? (object) $video : false;
	}

	/**
	 * Set the video URL response for the recipe.
	 *
	 * @param array $video_url_response Video URL response value.
	 *
	 * @return void
	 */
	public function set_video_url_response( $video_url_response ) {
		$this->set_meta( 'video_url_response', $video_url_response );
	}

	/**
	 * Get the average rating for the recipe.
	 *
	 * @return float|string
	 */
	public function get_average_rating() {
		$rating = $this->get_meta( 'average_rating' );
		if ( $rating ) {
			$rating = round( (float) $rating, 1 );
		}
		return $rating;
	}

	/**
	 * Set the average rating for the recipe.
	 *
	 * @param string $average_rating Average rating value.
	 *
	 * @return void
	 */
	public function set_average_rating( $average_rating ) {
		$this->set_meta( 'average_rating', $average_rating );
	}

	/**
	 * Get the total reviews for the recipe.
	 *
	 * @return int
	 */
	public function get_total_reviews() {
		return (int) $this->get_meta( 'total_reviews' );
	}

	/**
	 * Set the total reviews for the recipe.
	 *
	 * @param float|int $total_reviews Total reviews value.
	 *
	 * @return void
	 */
	public function set_total_reviews( $total_reviews ) {
		$this->set_meta( 'total_reviews', $total_reviews );
	}

	/**
	 * Get the prep time for the recipe.
	 *
	 * @return string
	 */
	public function get_prep_time() {
		return $this->get_meta( 'prep_time' );
	}

	/**
	 * Set the prep time for the recipe.
	 *
	 * @param string $prep_time Prep time value.
	 *
	 * @return void
	 */
	public function set_prep_time( $prep_time ) {
		$this->set_meta( 'prep_time', $prep_time );
	}

	/**
	 * Get the extra time label for the recipe.
	 *
	 * @return string
	 */
	public function get_additional_time_label() {
		return $this->get_meta( 'additional_time_label' );
	}

	/**
	 * Set the extra time label for the recipe.
	 *
	 * @param string $additional_time_label Extra time label value.
	 *
	 * @return void
	 */
	public function set_additional_time_label( $additional_time_label ) {
		$this->set_meta( 'additional_time_label', $additional_time_label );
	}

	/**
	 * Get the extra time value for the recipe.
	 *
	 * @return string
	 */
	public function get_additional_time_value() {
		$value = $this->get_meta( 'additional_time_value' );

		if ( is_array( $value ) ) {
			return reset( $value );
		}

		return $value;
	}

	/**
	 * Set the extra time value for the recipe.
	 *
	 * @param string $additional_time_value Extra time value.
	 *
	 * @return void
	 */
	public function set_additional_time_value( $additional_time_value ) {
		$this->set_meta( 'additional_time_value', $additional_time_value );
	}

	/**
	 * Get the cook time for the recipe.
	 *
	 * @return string
	 */
	public function get_cook_time() {
		return $this->get_meta( 'cook_time' );
	}

	/**
	 * Set the cook time for the recipe.
	 *
	 * @param string $cook_time Cook time value.
	 *
	 * @return void
	 */
	public function set_cook_time( $cook_time ) {
		$this->set_meta( 'cook_time', $cook_time );
	}

	/**
	 * Get the total time (as a combination of prep and cook).
	 *
	 * @return string
	 */
	public function get_total_time() {

		$total = $this->get_meta( 'total_time' );
		if ( '' !== $total ) {
			return $total;
		}

		$prep = $this->get_prep_time();
		$addl = $this->get_additional_time_value();
		$cook = $this->get_cook_time();
		if ( ! $prep && ! $addl && ! $cook ) {
			return '';
		}

		// Assume minutes if just an integer was provided.
		if ( is_numeric( $prep ) ) {
			$prep = "{$prep} minutes";
		}
		if ( is_numeric( $addl ) ) {
			$addl = "{$addl} minutes";
		}
		if ( is_numeric( $cook ) ) {
			$cook = "{$cook} minutes";
		}

		$time  = time();
		$total = 0;
		if ( '' !== $prep ) {
			$total += Distribution_Metadata::strtotime( $prep, $time ) - $time;
		}
		if ( '' !== $addl ) {
			$total += Distribution_Metadata::strtotime( $addl, $time ) - $time;
		}
		if ( '' !== $cook ) {
			$total += Distribution_Metadata::strtotime( $cook, $time ) - $time;
		}
		// If strtotime failed, return 0.
		$total = max( 0, $total );

		return Distribution_Metadata::format_time_for_human( $total );
	}

	/**
	 * Set the total time for the recipe.
	 *
	 * @param string $total_time Total time value.
	 *
	 * @return void
	 */
	public function set_total_time( $total_time ) {
		$this->set_meta( 'total_time', $total_time );
	}

	/**
	 * Get the yield for the recipe.
	 *
	 * @return string
	 */
	public function get_yield() {
		return $this->get_meta( 'yield' );
	}

	/**
	 * Set the yield for the recipe.
	 *
	 * @param string $value Yield value.
	 *
	 * @return void
	 */
	public function set_yield( $value ) {
		$this->set_meta( 'yield', $value );
	}

	/**
	 * Get the category for the recipe.
	 *
	 * @return string
	 */
	public function get_category() {
		return $this->get_taxonomy_terms( 'tasty_recipe_category', 'category' );
	}

	/**
	 * Set the category for the recipe.
	 *
	 * @param string $category Category value.
	 *
	 * @return void
	 */
	public function set_category( $category ) {
		$this->set_taxonomy_terms( 'tasty_recipe_category', $category, 'category' );
	}

	/**
	 * Get the cuisine for the recipe.
	 *
	 * @return string
	 */
	public function get_cuisine() {
		return $this->get_taxonomy_terms( 'tasty_recipe_cuisine', 'cuisine' );
	}

	/**
	 * Set the cuisine for the recipe.
	 *
	 * @param string $cuisine Cuisine value.
	 *
	 * @return void
	 */
	public function set_cuisine( $cuisine ) {
		$this->set_taxonomy_terms( 'tasty_recipe_cuisine', $cuisine, 'cuisine' );
	}

	/**
	 * Get the diet for the recipe.
	 *
	 * @return string
	 */
	public function get_diet() {
		return $this->get_taxonomy_terms( 'tasty_recipe_diet', 'diet' );
	}

	/**
	 * Set the diet for the recipe.
	 *
	 * @param string $diet Diet value.
	 *
	 * @return void
	 */
	public function set_diet( $diet ) {
		$this->set_taxonomy_terms( 'tasty_recipe_diet', $diet, 'diet' );
	}

	/**
	 * Get the method for the recipe.
	 *
	 * @return string
	 */
	public function get_method() {
		return $this->get_taxonomy_terms( 'tasty_recipe_method', 'method' );
	}

	/**
	 * Set the method for the recipe.
	 *
	 * @param string $method Method value.
	 *
	 * @return void
	 */
	public function set_method( $method ) {
		$this->set_taxonomy_terms( 'tasty_recipe_method', $method, 'method' );
	}

	/**
	 * Get the serving size for the recipe.
	 *
	 * @return string
	 */
	public function get_serving_size() {
		return $this->get_meta( 'serving_size' );
	}

	/**
	 * Set the serving size for the recipe.
	 *
	 * @param string $serving_size Serving size value.
	 *
	 * @return void
	 */
	public function set_serving_size( $serving_size ) {
		$this->set_meta( 'serving_size', $serving_size );
	}

	/**
	 * Get the calories for the recipe.
	 *
	 * @return string
	 */
	public function get_calories() {
		return $this->get_meta( 'calories' );
	}

	/**
	 * Set the calories for the recipe.
	 *
	 * @param string $calories Calories value.
	 *
	 * @return void
	 */
	public function set_calories( $calories ) {
		$this->set_meta( 'calories', $calories );
	}

	/**
	 * Get the sugar for the recipe.
	 *
	 * @return string
	 */
	public function get_sugar() {
		return $this->get_meta( 'sugar' );
	}

	/**
	 * Set the sugar for the recipe.
	 *
	 * @param string $sugar Sugar value.
	 *
	 * @return void
	 */
	public function set_sugar( $sugar ) {
		$this->set_meta( 'sugar', $sugar );
	}

	/**
	 * Get the sodium for the recipe.
	 *
	 * @return string
	 */
	public function get_sodium() {
		return $this->get_meta( 'sodium' );
	}

	/**
	 * Set the sodium for the recipe.
	 *
	 * @param string $sodium Sodium value.
	 *
	 * @return void
	 */
	public function set_sodium( $sodium ) {
		$this->set_meta( 'sodium', $sodium );
	}

	/**
	 * Get the fat for the recipe.
	 *
	 * @return string
	 */
	public function get_fat() {
		return $this->get_meta( 'fat' );
	}

	/**
	 * Set the fat for the recipe.
	 *
	 * @param string $fat Fat value.
	 *
	 * @return void
	 */
	public function set_fat( $fat ) {
		$this->set_meta( 'fat', $fat );
	}

	/**
	 * Get the saturated fat for the recipe.
	 *
	 * @return string
	 */
	public function get_saturated_fat() {
		return $this->get_meta( 'saturated_fat' );
	}

	/**
	 * Set the saturated fat for the recipe.
	 *
	 * @param string $saturated_fat Saturated fat value.
	 *
	 * @return void
	 */
	public function set_saturated_fat( $saturated_fat ) {
		$this->set_meta( 'saturated_fat', $saturated_fat );
	}

	/**
	 * Get the unsaturated fat for the recipe.
	 *
	 * @return string
	 */
	public function get_unsaturated_fat() {
		return $this->get_meta( 'unsaturated_fat' );
	}

	/**
	 * Set the unsaturated fat for the recipe.
	 *
	 * @param string $unsaturated_fat Unsaturated fat value.
	 *
	 * @return void
	 */
	public function set_unsaturated_fat( $unsaturated_fat ) {
		$this->set_meta( 'unsaturated_fat', $unsaturated_fat );
	}

	/**
	 * Get the trans fat for the recipe.
	 *
	 * @return string
	 */
	public function get_trans_fat() {
		return $this->get_meta( 'trans_fat' );
	}

	/**
	 * Set the trans fat for the recipe.
	 *
	 * @param string $trans_fat Trans fat value.
	 *
	 * @return void
	 */
	public function set_trans_fat( $trans_fat ) {
		$this->set_meta( 'trans_fat', $trans_fat );
	}

	/**
	 * Get the carbohydrates for the recipe.
	 *
	 * @return string
	 */
	public function get_carbohydrates() {
		return $this->get_meta( 'carbohydrates' );
	}

	/**
	 * Set the carbohydrates for the recipe.
	 *
	 * @param string $carbohydrates Carbohydrates value.
	 *
	 * @return void
	 */
	public function set_carbohydrates( $carbohydrates ) {
		$this->set_meta( 'carbohydrates', $carbohydrates );
	}

	/**
	 * Get the fiber for the recipe.
	 *
	 * @return string
	 */
	public function get_fiber() {
		return $this->get_meta( 'fiber' );
	}

	/**
	 * Set the fiber for the recipe.
	 *
	 * @param string $fiber Fiber value.
	 *
	 * @return void
	 */
	public function set_fiber( $fiber ) {
		$this->set_meta( 'fiber', $fiber );
	}

	/**
	 * Get the protein for the recipe.
	 *
	 * @return string
	 */
	public function get_protein() {
		return $this->get_meta( 'protein' );
	}

	/**
	 * Set the protein for the recipe.
	 *
	 * @param string $protein Protein value.
	 *
	 * @return void
	 */
	public function set_protein( $protein ) {
		$this->set_meta( 'protein', $protein );
	}

	/**
	 * Get the cholesterol for the recipe.
	 *
	 * @return string
	 */
	public function get_cholesterol() {
		return $this->get_meta( 'cholesterol' );
	}

	/**
	 * Set the cholesterol for the recipe.
	 *
	 * @param string $cholesterol Cholesterol value.
	 *
	 * @return void
	 */
	public function set_cholesterol( $cholesterol ) {
		$this->set_meta( 'cholesterol', $cholesterol );
	}

	/**
	 * Get the image ID for the post.
	 *
	 * @return false|int
	 */
	public function get_image_id() {
		return (int) $this->get_meta( '_thumbnail_id' );
	}

	/**
	 * Set the image id for the post.
	 *
	 * @param int $image_id Image id.
	 *
	 * @return void
	 */
	public function set_image_id( $image_id ) {
		$this->set_meta( '_thumbnail_id', (int) $image_id );
	}

	/**
	 * Get the featured image url for the given featured image id.
	 *
	 * @return false
	 */
	public function get_featured_image_url() {
		_deprecated_function( __METHOD__, 'unknown' );
		return false;
	}

	/**
	 * Get a specified image size.
	 *
	 * @param string $size Image size.
	 *
	 * @return array|false
	 */
	public function get_image_size( $size = 'full' ) {
		$attachment_id = $this->get_image_id();
		if ( ! $attachment_id ) {
			return false;
		}
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( ! $src ) {
			return false;
		}
		return array(
			'url'    => $src[0],
			'width'  => $src[1],
			'height' => $src[2],
			'html'   => wp_get_attachment_image( $attachment_id, $size, false, array( 'data-pin-nopin' => 'true' ) ),
		);
	}

	/**
	 * Get the JSON representation of the recipe.
	 *
	 * @return array
	 */
	public function to_json() {
		global $_wp_additional_image_sizes;
		$recipe_json = array();
		$rendered    = array( 'title', 'description', 'ingredients', 'instructions', 'notes' );
		foreach ( self::get_attribute_keys() as $attribute ) {
			$getter = "get_{$attribute}";
			// Accommodate extra nutrition attributes that might've been added.
			if ( ! method_exists( $this, $getter )
				&& in_array( $attribute, self::get_nutrition_attribute_keys(), true ) ) {
				$recipe_json[ $attribute ] = $this->get_meta( $attribute );
			} else {
				$recipe_json[ $attribute ] = $this->$getter();
			}
			if ( in_array( $attribute, $rendered, true ) ) {
				if ( 'title' === $attribute ) {
					$recipe_json[ "{$attribute}_rendered" ] = apply_filters( 'tasty_recipes_the_title', $recipe_json[ $attribute ] );
				} else {
					$recipe_json[ "{$attribute}_rendered" ] = apply_filters( 'tasty_recipes_the_content', $recipe_json[ $attribute ], $attribute );
				}
			}
		}

		$recipe_json['total_time_raw'] = $this->get_meta( 'total_time' );

		$image_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' );
		if ( isset( $_wp_additional_image_sizes ) ) {
			$image_sizes = array_merge( $image_sizes, array_keys( $_wp_additional_image_sizes ) );
		}
		$key = array_search( 'post-thumbnail', $image_sizes, true );
		if ( false !== $key ) {
			unset( $image_sizes[ $key ] );
		}

		$recipe_json['image_sizes'] = array();
		foreach ( $image_sizes as $image_size ) {
			$image_data = $this->get_image_size( $image_size );
			if ( $image_data ) {
				$recipe_json['image_sizes'][ $image_size ] = $image_data;
			}
		}

		return $recipe_json;
	}

	/**
	 * Create a new instance.
	 *
	 * @param array $args Arguments to use when creating post instance.
	 *
	 * @return false|Recipe
	 */
	public static function create( $args = array() ) {

		$defaults = array(
			'post_type'   => static::$post_type,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		);
		$args     = array_merge( $defaults, $args );
		add_filter( 'wp_insert_post_empty_content', '__return_false' );
		$post_id = wp_insert_post( $args );
		remove_filter( 'wp_insert_post_empty_content', '__return_false' );
		if ( ! $post_id ) {
			return false;
		}

		$class = get_called_class();
		$post  = get_post( $post_id );
		return new $class( $post );
	}

	/**
	 * Get a field from the post object.
	 *
	 * @param string $key Field key.
	 *
	 * @return mixed
	 */
	protected function get_field( $key ) {
		return $this->post->$key;
	}

	/**
	 * Set a field for the post object.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Value for the field.
	 *
	 * @return void
	 */
	protected function set_field( $key, $value ) {
		global $wpdb;

		$wpdb->update( $wpdb->posts, array( $key => $value ), array( 'ID' => $this->get_id() ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		clean_post_cache( $this->get_id() );
		$this->post = get_post( $this->get_id() );
	}

	/**
	 * Get a meta value for a post.
	 *
	 * @param string $key    Meta key.
	 * @param bool   $single Return single value or multiple, default is true.
	 *
	 * @return mixed
	 */
	protected function get_meta( $key, $single = true ) {
		return get_post_meta( $this->get_id(), $key, $single );
	}

	/**
	 * Insert a meta value for a post.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 *
	 * @return void
	 */
	protected function add_meta( $key, $value ) {
		add_post_meta( $this->get_id(), $key, $value );
	}

	/**
	 * Set a meta value for a post.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 *
	 * @return void
	 */
	protected function set_meta( $key, $value ) {
		update_post_meta( $this->get_id(), $key, $value );
	}

	/**
	 * Delete meta from recipe.
	 *
	 * @param string     $meta_key   Meta Key to be deleted.
	 * @param int|string $meta_value Meta value if you want to target with it.
	 *
	 * @return void
	 */
	private function delete_meta( $meta_key, $meta_value = '' ) {
		delete_post_meta( $this->get_id(), $meta_key, $meta_value );
	}

	/**
	 * Get taxonomy terms for the recipe as a comma-separated string.
	 * Falls back to legacy post meta if no taxonomy terms exist.
	 *
	 * @since 1.2
	 *
	 * @param string $taxonomy     Taxonomy name.
	 * @param string $legacy_meta  Legacy meta key for backwards compatibility.
	 *
	 * @return string
	 */
	protected function get_taxonomy_terms( $taxonomy, $legacy_meta = '' ) {
		$terms = get_the_terms( $this->get_id(), $taxonomy );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term_names = wp_list_pluck( $terms, 'name' );
			return implode( ', ', $term_names );
		}

		// Backwards compatibility: check legacy post meta.
		if ( ! empty( $legacy_meta ) ) {
			$legacy_value = $this->get_meta( $legacy_meta );
			if ( ! empty( $legacy_value ) ) {
				return $legacy_value;
			}
		}

		return '';
	}

	/**
	 * Set taxonomy terms for the recipe from a comma-separated string.
	 * Also cleans up legacy post meta after successful taxonomy assignment.
	 *
	 * @since 1.2
	 *
	 * @param string $taxonomy     Taxonomy name.
	 * @param string $value        Comma-separated string of term names.
	 * @param string $legacy_meta  Legacy meta key for backwards compatibility cleanup.
	 *
	 * @return void
	 */
	protected function set_taxonomy_terms( $taxonomy, $value, $legacy_meta = '' ) {
		if ( empty( $value ) ) {
			wp_set_object_terms( $this->get_id(), array(), $taxonomy );

			// Clean up legacy meta if it exists.
			if ( ! empty( $legacy_meta ) ) {
				$this->delete_meta( $legacy_meta );
			}

			return;
		}

		$term_names = array_map( 'trim', explode( ',', $value ) );

		// Remove empty strings from the array.
		$term_names = array_filter( $term_names );

		$term_ids = array();

		foreach ( $term_names as $term_name ) {
			$existing_term = get_term_by( 'name', $term_name, $taxonomy );
			if ( $existing_term ) {
				$term_ids[] = $existing_term->term_id;

				continue;
			}

			$new_term = wp_insert_term( $term_name, $taxonomy );
			if ( ! is_wp_error( $new_term ) ) {
				$term_ids[] = $new_term['term_id'];
			}
		}

		wp_set_object_terms( $this->get_id(), $term_ids, $taxonomy );

		// Clean up legacy meta if it exists.
		if ( ! empty( $legacy_meta ) ) {
			$this->delete_meta( $legacy_meta );
		}
	}

	/**
	 * Remove empty elements from the start and end of HTML content based on the provided tag.
	 * We use a regex to detect and replace empty tags (spaces and &nbsp;).
	 * This regex consists of two parts, one for the start and the other for the end.
	 * If the provided tag is paragraph, so it can detect the following examples
	 * from the start and end of the string and keep the in-between as is:
	 * <p></p> AND <p> </p> AND <p>     </p> AND <p>&nbsp;</p> AND <p> &nbsp; </p>.
	 *
	 * @param string $content The content to search in.
	 * @param string $tag     The tag that has empty content.
	 *
	 * @return string
	 */
	private function remove_empty_tags_from_start_end( $content, $tag = 'p' ) {
		$regex = sprintf(
			'#^(<%1$s[^>]*>(\s*[&nbsp;]*\n*\s*)</%1$s>\n*)*|(\s*\n*<%1$s[^>]*>(\s*[&nbsp;]*\n*\s*)</%1$s>\n*)*$#i',
			$tag
		);
		return preg_replace( $regex, '', $content );
	}

	/**
	 * Add parent post to this recipe.
	 *
	 * @param int $parent_id Parent post ID.
	 *
	 * @return void
	 */
	public function add_parent_post( $parent_id ) {
		$recipe_parents = $this->get_meta( '_tasty_recipe_parents', false );
		if ( in_array( (string) $parent_id, $recipe_parents, true ) ) {
			return;
		}
		$this->add_meta( '_tasty_recipe_parents', $parent_id );
	}

	/**
	 * Remove parent post from thie recipe.
	 *
	 * @param int $parent_id Parent post ID.
	 *
	 * @return void
	 */
	public function remove_parent_post( $parent_id ) {
		$this->delete_meta( '_tasty_recipe_parents', $parent_id );
	}

	/**
	 * Get array of parent posts for this recipe.
	 *
	 * @return array
	 */
	public function get_parent_posts() {
		return (array) $this->get_meta( '_tasty_recipe_parents', false );
	}
}

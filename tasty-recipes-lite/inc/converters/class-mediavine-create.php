<?php
/**
 * Converter class for Mediavine Create.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Block_Editor;
use Tasty_Recipes\Distribution_Metadata;
use Tasty_Recipes\Meta_Keys;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Ratings;
use Tasty_Recipes\Shortcodes;

/**
 * Converter class for Mediavine Create.
 */
class Mediavine_Create extends Converter {

	/**
	 * Meta key linking a Tasty recipe back to a Create recipe ID.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	const CREATE_KEY_META = Meta_Keys::CREATE_KEY;

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = array(
		'wp:mv/recipe',
		'[mv_create',
	);

	/**
	 * Name of the block.
	 *
	 * @var string
	 */
	protected static $block_name = 'mv/recipe';

	/**
	 * Name of the shortcode tag.
	 *
	 * @var string
	 */
	protected static $shortcode_tag = 'mv_create';

	/**
	 * Get the total number of posts with Create recipes in content or Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @return int
	 */
	public static function get_count() {
		global $wpdb;

		$likes = self::get_discovery_like_patterns();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} AS p
				LEFT JOIN {$wpdb->postmeta} AS pm
					ON p.ID = pm.post_id
					AND pm.meta_key = %s
				WHERE p.post_type NOT IN (%s, %s)
					AND p.post_status NOT IN (%s, %s)
					AND (
						p.post_content LIKE %s
						OR (
							p.post_content LIKE %s
							AND ( p.post_content LIKE %s OR p.post_content LIKE %s )
						)
						OR (
							pm.meta_value LIKE %s
							AND ( pm.meta_value LIKE %s OR pm.meta_value LIKE %s )
						)
					)",
				'_elementor_data',
				'revision',
				'tasty_recipe',
				'trash',
				'auto-draft',
				$likes['block'],
				$likes['mv_create'],
				$likes['type_recipe'],
				$likes['type_recipe_escaped'],
				$likes['mv_create'],
				$likes['type_recipe'],
				$likes['type_recipe_escaped']
			)
		);
	}

	/**
	 * Get post ids for posts with Create recipes in content or Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @param int $per_page Number of posts to fetch. Defaults to 10.
	 *
	 * @return array
	 */
	public static function get_post_ids( $per_page = 10 ) {
		global $wpdb;

		$likes = self::get_discovery_like_patterns();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} AS p
				LEFT JOIN {$wpdb->postmeta} AS pm
					ON p.ID = pm.post_id
					AND pm.meta_key = %s
				WHERE p.post_type NOT IN (%s, %s)
					AND p.post_status NOT IN (%s, %s)
					AND (
						p.post_content LIKE %s
						OR (
							p.post_content LIKE %s
							AND ( p.post_content LIKE %s OR p.post_content LIKE %s )
						)
						OR (
							pm.meta_value LIKE %s
							AND ( pm.meta_value LIKE %s OR pm.meta_value LIKE %s )
						)
					)
				ORDER BY p.ID ASC
				LIMIT %d",
				'_elementor_data',
				'revision',
				'tasty_recipe',
				'trash',
				'auto-draft',
				$likes['block'],
				$likes['mv_create'],
				$likes['type_recipe'],
				$likes['type_recipe_escaped'],
				$likes['mv_create'],
				$likes['type_recipe'],
				$likes['type_recipe_escaped'],
				(int) $per_page
			)
		);

		return array_map( 'intval', $post_ids );
	}

	/**
	 * Whether a post has a Create recipe shortcode stored in Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function has_elementor_create_shortcode( $post_id ) {
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! is_string( $elementor_data ) || false === stripos( $elementor_data, '[mv_create' ) ) {
			return false;
		}

		return false !== stripos( $elementor_data, 'type="recipe"' )
			|| false !== stripos( $elementor_data, 'type=\"recipe\"' );
	}

	/**
	 * Convert the recipe content within a given post, including Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @param int    $post_id ID for the post with the recipe.
	 * @param string $type    Whether to create a shortcode or a block.
	 *
	 * @return false|Recipe
	 */
	public static function convert_post( $post_id, $type = 'shortcode' ) {
		global $wpdb;

		if ( ! in_array( $type, array( 'shortcode', 'block' ), true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$content = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE ID=%d", $post_id ) );
		// Correct Windows-style line endings.
		$content = str_replace( "\r\n", "\n", $content );
		$content = str_replace( "\r", "\n", $content );

		$elementor_raw = get_post_meta( $post_id, '_elementor_data', true );
		$document      = null;
		if ( is_string( $elementor_raw ) && false !== stripos( $elementor_raw, '[mv_create' ) ) {
			$decoded = json_decode( $elementor_raw, true );
			if ( is_array( $decoded ) ) {
				$document = $decoded;
			}
		}

		$content_match       = self::get_first_create_recipe_match_from_content( $content );
		$elementor_shortcode = self::get_first_create_recipe_shortcode( $document );
		$from_content        = (bool) $content_match;
		$existing            = $content_match ? $content_match : $elementor_shortcode;

		if ( ! $existing ) {
			return false;
		}

		$data = self::get_existing_block_or_shortcode( $existing, 'data' );
		if ( empty( $data['type'] ) || 'recipe' !== $data['type'] ) {
			return false;
		}

		$create_key = self::extract_create_id_from_data( $data );
		if ( ! $create_key ) {
			return false;
		}

		$recipe = self::resolve_recipe_for_create_key( $create_key, $existing, $post_id );
		if ( ! $recipe ) {
			return false;
		}

		Ratings::update_recipe_rating( $recipe, $post_id );

		if ( $from_content ) {
			if ( 'shortcode' === $type ) {
				$replacement = PHP_EOL . Shortcodes::get_shortcode_for_recipe( $recipe ) . PHP_EOL;
			} else {
				$replacement = PHP_EOL . Block_Editor::get_block_for_recipe( $recipe ) . PHP_EOL;
			}
			$content = str_replace( $existing, $replacement, $content );
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
		}

		if ( null !== $document ) {
			$recipe_map        = array( $create_key => $recipe->get_id() );
			$replacement_count = 0;
			self::replace_create_shortcodes( $document, $recipe_map, $replacement_count );

			if ( $replacement_count > 0 ) {
				$updated_data = wp_json_encode( $document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( false === $updated_data ) {
					return false;
				}

				update_post_meta( $post_id, '_elementor_data', wp_slash( $updated_data ) );
				delete_post_meta( $post_id, '_elementor_css' );
			}
		}

		clean_post_cache( $post_id );

		return $recipe;
	}

	/**
	 * Delete unused empty-title Tasty recipes left by prior incomplete converts.
	 *
	 * @since 1.2.7
	 *
	 * @return int Number of recipes deleted.
	 */
	public static function cleanup_empty_title_orphans() {
		global $wpdb;

		$tasty_like = '%' . $wpdb->esc_like( 'tasty-recipe' ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$candidate_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status != %s
					AND (post_title = '' OR post_title = %s OR post_title = %s)",
				'tasty_recipe',
				'trash',
				'Auto Draft',
				'(no title)'
			)
		);
		$candidate_ids = array_map( 'absint', $candidate_ids );

		if ( empty( $candidate_ids ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$content_hits = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_content FROM {$wpdb->posts}
				WHERE post_type != %s
					AND post_status != %s
					AND post_content LIKE %s",
				'revision',
				'trash',
				$tasty_like
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$elementor_hits = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s
					AND meta_value LIKE %s",
				'_elementor_data',
				$tasty_like
			)
		);

		$referenced_lookup = array();
		foreach ( array_merge( $content_hits, $elementor_hits ) as $haystack ) {
			if ( preg_match_all( '/(?:\[tasty-recipe[^\]]*id=["\']?(\d+)|"id":(\d+))/', (string) $haystack, $matches ) ) {
				foreach ( array_filter( array_merge( $matches[1], $matches[2] ) ) as $found_id ) {
					$referenced_lookup[ (int) $found_id ] = true;
				}
			}
		}

		$delete_ids = array();
		foreach ( $candidate_ids as $candidate_id ) {
			if ( ! isset( $referenced_lookup[ $candidate_id ] ) ) {
				$delete_ids[] = $candidate_id;
			}
		}

		if ( empty( $delete_ids ) ) {
			return 0;
		}

		$deleted = 0;
		foreach ( $delete_ids as $delete_id ) {
			$result = wp_delete_post( $delete_id, true );
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that might contain a recipe.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore SlevomatCodingStandard
		global $wpdb;

		$post_id = (int) $post_id; // Avoid UnusedVariable.

		$data = self::get_existing_block_or_shortcode( $existing, 'data' );
		// Only converting recipe cards.
		if ( empty( $data['type'] ) || 'recipe' !== $data['type'] ) {
			return false;
		}
		// Block uses 'id' while shortcode uses 'key'.
		$create_id = self::extract_create_id_from_data( $data );
		if ( ! $create_id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_creations WHERE id=%d", $create_id ) );
		if ( ! $existing_row ) {
			return false;
		}

		$recipe = Recipe::create();
		if ( ! $recipe ) {
			return false;
		}

		$converted_data = array();
		$existing       = (object) $existing_row;
		$existing_post  = get_post( $existing->object_id );

		$mapping_fields = array(
			// MV Create -> Tasty Recipes.
			'title'             => 'title',
			'author'            => 'author_name',
			'thumbnail_id'      => 'image_id',
			'yield'             => 'yield',
			'suitable_for_diet' => 'diet',
			'description'       => 'description',
			'instructions'      => 'instructions',
			'notes'             => 'notes',
			'keywords'          => 'keywords',
			'prep_time'         => 'prep_time',
			'active_time'       => 'cook_time',
			'additional_time'   => 'additional_time_value',
			'total_time'        => 'total_time',
			'external_video'    => 'video_url',
		);

		foreach ( $mapping_fields as $mc => $tr ) {

			$value = $existing->$mc;

			if ( is_null( $value ) ) {
				continue;
			}

			if ( 'instructions' === $mc ) {
				// Handle '[mv_schema_meta name=&quot;Pour the milk&quot;]' schema details.
				$value = str_replace( '&quot;', '"', $value );
				$value = preg_replace_callback(
					'#\[mv_schema_meta([^\]]+)\]#',
					function ( $matches ) {
						$atts = shortcode_parse_atts( $matches[1] );
						if ( empty( $atts['name'] ) ) {
							return $matches[0];
						}
						return '<strong>' . $atts['name'] . '</strong>';
					},
					$value
				);
			}

			if ( in_array( $mc, array( 'prep_time', 'active_time', 'total_time' ), true ) ) {
				$value = Distribution_Metadata::format_time_for_human( $value );
			}

			if ( 'additional_time' === $mc ) {
				$converted_data['additional_time_label'] = $existing->additional_time_label ? $existing->additional_time_label : 'Additional Time';

				$value = Distribution_Metadata::format_time_for_human( $value );
			}

			if ( 'external_video' === $mc ) {
				$video_data = json_decode( $value, true );
				if ( ! empty( $video_data['contentUrl'] ) ) {
					$value = $video_data['contentUrl'];
				}
			}

			$converted_data[ $tr ] = $value;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_supplies = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_supplies WHERE creation=%d ORDER BY position ASC", $create_id ) );
		$ingredients       = array();
		foreach ( $existing_supplies as $ingredient ) {
			$ingredient = (array) $ingredient;
			if ( ! isset( $ingredients[ $ingredient['group'] ] ) ) {
				$ingredients[ $ingredient['group'] ] = array();
			}
			$ingredients[ $ingredient['group'] ][] = self::format_create_ingredient_text(
				(string) $ingredient['original_text'],
				isset( $ingredient['link'] ) ? (string) $ingredient['link'] : '',
				! empty( $ingredient['nofollow'] )
			);
		}
		$output = '';
		foreach ( $ingredients as $group => $lines ) {
			if ( ! in_array( $group, array( 'mv-has-no-group', '_empty_' ), true ) ) {
				$output .= '<h3>' . wp_filter_post_kses( (string) $group ) . '</h3>' . PHP_EOL;
			}
			$output .= '<ul>' . PHP_EOL;
			foreach ( $lines as $line ) {
				$output .= '<li>' . $line . '</li>' . PHP_EOL;
			}
			$output .= '</ul>' . PHP_EOL;
		}
		$converted_data['ingredients'] = trim( $output );

		$mapping_fields = array(
			// MV Create -> Tasty Recipes.
			'serving_size'    => 'serving_size',
			'calories'        => 'calories',
			'total_fat'       => 'fat',
			'saturated_fat'   => 'saturated_fat',
			'unsaturated_fat' => 'unsaturated_fat',
			'trans_fat'       => 'trans_fat',
			'cholesterol'     => 'cholesterol',
			'carbohydrates'   => 'carbohydrates',
			'sodium'          => 'sodium',
			'fiber'           => 'fiber',
			'sugar'           => 'sugar',
			'protein'         => 'protein',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_nutrition = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mv_nutrition WHERE creation=%d", $create_id ) );
		if ( is_object( $existing_nutrition ) ) {
			foreach ( $mapping_fields as $mc => $tr ) {

				$value = $existing_nutrition->$mc;

				if ( is_null( $value ) ) {
					continue;
				}

				$converted_data[ $tr ] = $value;
			}
		}

		if ( ! empty( $existing_post ) ) {
			// Back up registered taxonomies so we can restore them after we've
			// fetched the data.
			$backup_taxonomies        = $GLOBALS['wp_taxonomies'];
			$GLOBALS['wp_taxonomies'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$tax_fields               = array(
				'category'   => 'category',
				'mv_cuisine' => 'cuisine',
			);
			foreach ( $tax_fields as $tax => $tr ) {
				register_taxonomy( $tax, $existing_post->post_type );
				$terms = get_the_terms( $existing_post->ID, $tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$term_names            = wp_list_pluck( $terms, 'name' );
					$converted_data[ $tr ] = implode( ', ', $term_names );
				}
			}
			// Restore registered taxonomies.
			$GLOBALS['wp_taxonomies'] = $backup_taxonomies; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! empty( $existing->rating ) ) {
			update_post_meta(
				$recipe->get_id(),
				'create_ratings',
				array(
					'rating'       => $existing->rating,
					'rating_count' => $existing->rating_count,
				)
			);
		}

		$recipe = self::save_converted_data_to_recipe( $converted_data, $recipe );
		update_post_meta( $recipe->get_id(), self::CREATE_KEY_META, $create_id );

		return $recipe;
	}

	/**
	 * Format a Create supply row into ingredient HTML.
	 *
	 * Create marks a partial link target with brackets in original_text and stores
	 * the URL in the link column. Orphan brackets (link removed) are stripped.
	 *
	 * @since 1.2.7
	 *
	 * @param string $original_text Ingredient text, possibly with [linked span].
	 * @param string $link          Ingredient link URL, if any.
	 * @param bool   $nofollow      Whether the link should be nofollow.
	 *
	 * @return string
	 */
	private static function format_create_ingredient_text( $original_text, $link = '', $nofollow = false ) {
		$original_text = (string) $original_text;
		$link          = trim( (string) $link );

		if ( '' === $link ) {
			$text = preg_replace( '/\[([^\]]*)\]/', '$1', $original_text, 1 );
			return wp_filter_post_kses( null === $text ? $original_text : $text );
		}

		preg_match( '/([^[]*?)\[([^\]]*)\](.*)/s', $original_text, $matches );
		if ( empty( $matches ) ) {
			$before    = '';
			$after     = '';
			$link_text = $original_text;
		} else {
			$before    = $matches[1];
			$link_text = $matches[2];
			$after     = $matches[3];
		}

		$output  = wp_filter_post_kses( $before );
		$output .= '<a href="' . esc_url( $link ) . '"';
		if ( $nofollow ) {
			$output .= ' rel="nofollow"';
		}
		// Check for internal links.
		if ( ! str_starts_with( $link, get_site_url() ) ) {
			$output .= ' target="_blank"';
		}
		$output .= '>';
		$output .= wp_filter_post_kses( $link_text );
		$output .= '</a>';
		$output .= wp_filter_post_kses( $after );

		return $output;
	}

	/**
	 * Resolve a Create key to a Recipe, reusing an existing Tasty recipe when possible.
	 *
	 * @since 1.2.7
	 *
	 * @param int    $create_key     Create recipe key.
	 * @param string $existing_match Existing block or shortcode match.
	 * @param int    $post_id        Parent post ID.
	 *
	 * @return false|Recipe
	 */
	private static function resolve_recipe_for_create_key( $create_key, $existing_match, $post_id ) {
		$existing_id = self::find_existing_tasty_recipe_for_create_key( $create_key );
		if ( $existing_id ) {
			$recipe = Recipe::get_by_id( $existing_id );
			if ( $recipe ) {
				update_post_meta( $existing_id, self::CREATE_KEY_META, $create_key );
				return $recipe;
			}
		}

		return self::create_recipe_from_existing( $existing_match, $post_id );
	}

	/**
	 * Find an existing Tasty recipe for a Create key.
	 *
	 * Prefer Create-key meta, then a unique title match that is not mapped to another Create key.
	 *
	 * @since 1.2.7
	 *
	 * @param int $create_key Create recipe key.
	 *
	 * @return int
	 */
	private static function find_existing_tasty_recipe_for_create_key( $create_key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_meta = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
					AND p.post_status != %s
					AND pm.meta_key = %s
					AND pm.meta_value = %s
				ORDER BY p.ID ASC
				LIMIT 1",
				'tasty_recipe',
				'trash',
				self::CREATE_KEY_META,
				(string) $create_key
			)
		);

		if ( $by_meta ) {
			return $by_meta;
		}

		$table_name = $wpdb->prefix . 'mv_creations';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$create_title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$table_name} WHERE id = %d", $create_key ) );
		if ( ! is_string( $create_title ) || '' === trim( $create_title ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$matching_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status != %s
					AND post_title = %s
				ORDER BY ID ASC",
				'tasty_recipe',
				'trash',
				$create_title
			)
		);

		// Only reuse a unique title match that is not already mapped to a different Create key.
		if ( 1 !== count( $matching_ids ) ) {
			return 0;
		}

		$match_id     = (int) $matching_ids[0];
		$existing_key = get_post_meta( $match_id, self::CREATE_KEY_META, true );
		if ( $existing_key && (int) $existing_key !== (int) $create_key ) {
			return 0;
		}

		return $match_id;
	}

	/**
	 * Extract Create recipe ID from parsed shortcode/block attributes.
	 *
	 * @since 1.2.7
	 *
	 * @param array $data Parsed attributes.
	 *
	 * @return int
	 */
	private static function extract_create_id_from_data( $data ) {
		if ( ! empty( $data['id'] ) ) {
			return (int) $data['id'];
		}
		if ( ! empty( $data['key'] ) ) {
			return (int) $data['key'];
		}
		return 0;
	}

	/**
	 * LIKE patterns used when discovering Create recipe posts.
	 *
	 * @since 1.2.7
	 *
	 * @return array{block: string, mv_create: string, type_recipe: string, type_recipe_escaped: string}
	 */
	private static function get_discovery_like_patterns() {
		global $wpdb;

		return array(
			'block'               => '%' . $wpdb->esc_like( 'wp:mv/recipe' ) . '%',
			'mv_create'           => '%' . $wpdb->esc_like( '[mv_create' ) . '%',
			'type_recipe'         => '%' . $wpdb->esc_like( 'type="recipe"' ) . '%',
			'type_recipe_escaped' => '%' . $wpdb->esc_like( 'type=\"recipe\"' ) . '%',
		);
	}

	/**
	 * Get the first Create recipe block/shortcode match from post content.
	 *
	 * @since 1.2.7
	 *
	 * @param string $content Post content.
	 *
	 * @return false|string
	 */
	private static function get_first_create_recipe_match_from_content( $content ) {
		$match = static::get_existing_to_convert( $content );
		if ( $match ) {
			$data = self::get_existing_block_or_shortcode( $match, 'data' );
			if ( is_array( $data ) && ! empty( $data['type'] ) && 'recipe' === $data['type'] ) {
				return $match;
			}
		}

		return self::get_first_create_recipe_shortcode( $content );
	}

	/**
	 * Get the first Create recipe shortcode from nested Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @param mixed $value Nested value.
	 *
	 * @return false|string
	 */
	private static function get_first_create_recipe_shortcode( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				$found = self::get_first_create_recipe_shortcode( $child );
				if ( $found ) {
					return $found;
				}
			}
			return false;
		}

		if ( ! is_string( $value ) || false === stripos( $value, '[mv_create' ) ) {
			return false;
		}

		if ( ! preg_match_all( '/\[mv_create\b[^\]]*\]/i', $value, $matches ) ) {
			return false;
		}

		foreach ( $matches[0] as $shortcode ) {
			$attributes = shortcode_parse_atts( substr( $shortcode, 1, -1 ) );
			if ( empty( $attributes['type'] ) || 'recipe' !== $attributes['type'] ) {
				continue;
			}
			if ( empty( $attributes['key'] ) ) {
				continue;
			}
			return $shortcode;
		}

		return false;
	}

	/**
	 * Replace Create recipe shortcodes in nested content/Elementor data.
	 *
	 * @since 1.2.7
	 *
	 * @param mixed $value             Nested value.
	 * @param int[] $recipe_map        Create key to Tasty Recipe ID map.
	 * @param int   $replacement_count Replacement count.
	 *
	 * @return void
	 */
	private static function replace_create_shortcodes( &$value, $recipe_map, &$replacement_count ) {
		if ( is_array( $value ) ) {
			foreach ( $value as &$child ) {
				self::replace_create_shortcodes( $child, $recipe_map, $replacement_count );
			}
			unset( $child );
			return;
		}

		if ( ! is_string( $value ) || false === stripos( $value, '[mv_create' ) ) {
			return;
		}

		$value = preg_replace_callback(
			'/\[mv_create\b[^\]]*\]/i',
			static function ( $matches ) use ( $recipe_map, &$replacement_count ) {
				$attributes = shortcode_parse_atts( substr( $matches[0], 1, -1 ) );
				if ( empty( $attributes['type'] ) || 'recipe' !== $attributes['type'] ) {
					return $matches[0];
				}

				$create_key = ! empty( $attributes['key'] ) ? absint( $attributes['key'] ) : 0;
				if ( ! isset( $recipe_map[ $create_key ] ) ) {
					return $matches[0];
				}

				++$replacement_count;
				return '[tasty-recipe id="' . absint( $recipe_map[ $create_key ] ) . '"]';
			},
			$value
		);
	}
}

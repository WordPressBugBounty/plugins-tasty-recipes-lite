<?php
/**
 * Defines our custom post type and everything related to its behavior.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Utils;

/**
 * Defines our custom post type and everything related to its behavior.
 */
class Content_Model {

	/**
	 * Query variable used for print pages.
	 *
	 * @var string
	 */
	const PRINT_QUERY_VAR = 'print';

	/**
	 * Load everything triggered on the init hook.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function init_post_type() {
		self::action_init_register_cron_events();
		self::action_init_register_post_types();
		self::action_init_register_taxonomies();
		self::insert_default_taxonomy_terms();
		self::action_init_register_rewrite_rules();
		self::action_init_register_oembed_providers();

		// Prevent deletion of default taxonomy terms.
		add_action( 'pre_delete_term', array( __CLASS__, 'filter_pre_delete_term' ), 10, 2 );

		// Filter taxonomy archive queries to only show recipes with parent posts.
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_taxonomy_archive_query' ) );
	}

	/**
	 * Registers our cron events.
	 *
	 * @return void
	 */
	public static function action_init_register_cron_events() {
		if ( ! wp_next_scheduled( 'tasty_recipes_process_thumbnails' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'tasty_recipes_process_thumbnails' );
		}
	}

	/**
	 * Registers our post types.
	 *
	 * @return void
	 */
	public static function action_init_register_post_types() {

		$args           = array(
			'hierarchical' => false,
			'public'       => false,
			'show_ui'      => true,
			'rewrite'      => false,
			'supports'     => array(),
			'show_in_menu' => false,
		);
		$args['labels'] = array(
			'name'               => __( 'Recipes', 'tasty-recipes-lite' ),
			'singular_name'      => __( 'Recipe', 'tasty-recipes-lite' ),
			'all_items'          => __( 'All Recipes', 'tasty-recipes-lite' ),
			'new_item'           => __( 'New Recipe', 'tasty-recipes-lite' ),
			'add_new'            => __( 'Add New', 'tasty-recipes-lite' ),
			'add_new_item'       => __( 'Add New Recipe', 'tasty-recipes-lite' ),
			'edit_item'          => __( 'Edit Recipe', 'tasty-recipes-lite' ),
			'view_item'          => __( 'View Recipes', 'tasty-recipes-lite' ),
			'search_items'       => __( 'Search Recipes', 'tasty-recipes-lite' ),
			'not_found'          => __( 'No recipes found', 'tasty-recipes-lite' ),
			'not_found_in_trash' => __( 'No recipes found in trash', 'tasty-recipes-lite' ),
			'parent_item_colon'  => __( 'Parent recipe', 'tasty-recipes-lite' ),
			'menu_name'          => __( 'Recipes', 'tasty-recipes-lite' ),
		);
		register_post_type( 'tasty_recipe', $args );
	}

	/**
	 * Registers our taxonomies.
	 * 
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function action_init_register_taxonomies() {
		$taxonomies = self::get_taxonomy_definitions();

		foreach ( $taxonomies as $taxonomy => $config ) {
			register_taxonomy( $taxonomy, 'tasty_recipe', $config['args'] );
		}
	}

	/**
	 * Get taxonomy definitions for recipe attributes.
	 * 
	 * @since 1.2
	 *
	 * @return array
	 */
	public static function get_taxonomy_definitions() { // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
		$definitions = array(
			'tasty_recipe_category' => self::get_taxonomy_definition( __( 'category', 'tasty-recipes-lite' ), __( 'Categories', 'tasty-recipes-lite' ) ),
			'tasty_recipe_method'   => self::get_taxonomy_definition( __( 'method', 'tasty-recipes-lite' ), __( 'Methods', 'tasty-recipes-lite' ) ),
			'tasty_recipe_cuisine'  => self::get_taxonomy_definition( __( 'cuisine', 'tasty-recipes-lite' ), __( 'Cuisines', 'tasty-recipes-lite' ) ),
			'tasty_recipe_diet'     => self::get_taxonomy_definition( __( 'diet', 'tasty-recipes-lite' ), __( 'Diets', 'tasty-recipes-lite' ) ),
		);

		/**
		 * Filters the taxonomy definitions for the recipe post type.
		 *
		 * Allows adding or modifying taxonomy definitions that will be registered
		 * on the tasty_recipe post type. Each definition should include an 'args'
		 * key compatible with register_taxonomy().
		 *
		 * @since 1.2.2
		 *
		 * @param array $definitions Taxonomy definitions keyed by taxonomy name.
		 */
		return apply_filters( 'tasty_recipes_taxonomy_definitions', $definitions );
	}

	/**
	 * Get the taxonomy definition for a given taxonomy.
	 *
	 * @since 1.2
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @param string $plural The plural of the taxonomy.
	 *
	 * @return array
	 */
	private static function get_taxonomy_definition( $taxonomy, $plural ) {
		$singular      = ucfirst( $taxonomy );
		$slug          = 'recipe-' . sanitize_title( $taxonomy );
		$taxonomy_name = 'tasty_recipe_' . sanitize_title( $taxonomy );

		/**
		 * Filters whether recipe taxonomy archive pages are enabled.
		 *
		 * When set to false, the taxonomy archive pages will return 404.
		 * This does not affect the admin UI or REST API access.
		 *
		 * @since 1.2.1
		 *
		 * @param bool   $has_archive   Whether archive pages are enabled. Default true.
		 * @param string $taxonomy_name The full taxonomy name (e.g., 'tasty_recipe_category').
		 */
		$has_archive = apply_filters( 'tasty_recipes_taxonomy_has_archive', true, $taxonomy_name );

		return array(
			'legacy_meta' => $taxonomy,
			'args'        => array(
				'labels'             => array(
					// translators: %s is the plural of the taxonomy.
					'name'                       => sprintf( __( 'Recipe %s', 'tasty-recipes-lite' ), $plural ),
					'singular_name'              => $singular,
					// translators: %s is the plural of the taxonomy.
					'search_items'               => sprintf( __( 'Search %s', 'tasty-recipes-lite' ), $plural ),
					// translators: %s is the plural of the taxonomy.
					'all_items'                  => sprintf( __( 'All %s', 'tasty-recipes-lite' ), $plural ),
					// translators: %s is the singular of the taxonomy.
					'edit_item'                  => sprintf( __( 'Edit %s', 'tasty-recipes-lite' ), $singular ),
					// translators: %s is the singular of the taxonomy.
					'update_item'                => sprintf( __( 'Update %s', 'tasty-recipes-lite' ), $singular ),
					// translators: %s is the singular of the taxonomy.
					'add_new_item'               => sprintf( __( 'Add New %s', 'tasty-recipes-lite' ), $singular ),
					// translators: %s is the singular of the taxonomy.
					'new_item_name'              => sprintf( __( 'New %s Name', 'tasty-recipes-lite' ), $singular ),
					// translators: %s is the plural of the taxonomy.
					'menu_name'                  => $plural,
					// translators: %s is the plural of the taxonomy.
					'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'tasty-recipes-lite' ), $plural ),
					// translators: %s is the plural of the taxonomy.
					'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'tasty-recipes-lite' ), $plural ),
					// translators: %s is the plural of the taxonomy.
					'not_found'                  => sprintf( __( 'No %s found', 'tasty-recipes-lite' ), $plural ),
				),
				'hierarchical'       => false,
				'public'             => $has_archive,
				'publicly_queryable' => $has_archive,
				'show_ui'            => true,
				'show_admin_column'  => false,
				'show_in_nav_menus'  => true,
				'show_tagcloud'      => false,
				'show_in_rest'       => true,
				'rewrite'            => $has_archive ? array(
					'slug'       => $slug,
					'with_front' => false,
				) : false,
			),
		);
	}

	/**
	 * Get the taxonomy name for a given legacy meta key.
	 *
	 * @since 1.2
	 *
	 * @param string $legacy_meta The legacy meta key to look up.
	 *
	 * @return false|string The taxonomy name or false if not found.
	 */
	public static function get_taxonomy_for_legacy_meta( $legacy_meta ) {
		$taxonomies = self::get_taxonomy_definitions();

		foreach ( $taxonomies as $taxonomy => $config ) {
			if ( $config['legacy_meta'] === $legacy_meta ) {
				return $taxonomy;
			}
		}

		return false;
	}

	/**
	 * Get default terms for each taxonomy.
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public static function get_default_taxonomy_terms() {
		$defaults = array(
			'tasty_recipe_category' => array(
				'Breakfast',
				'Lunch',
				'Dinner',
				'Dessert',
				'Snack',
				'Appetizer',
				'Beverage',
				'Side Dish',
			),
			'tasty_recipe_method'   => array(
				'Baking',
				'Boiling',
				'Frying',
				'Grilling',
				'Pressure Cooking',
				'Roasting',
				'Sautéing',
				'Slow Cooking',
				'Steaming',
			),
			'tasty_recipe_cuisine'  => array(
				'American',
				'Chinese',
				'French',
				'Greek',
				'Indian',
				'Italian',
				'Japanese',
				'Mediterranean',
				'Mexican',
				'Thai',
			),
			'tasty_recipe_diet'     => array(
				'Vegetarian',
				'Vegan',
				'Gluten-Free',
				'Dairy-Free',
				'Keto',
				'Paleo',
				'Low-Carb',
				'Pescatarian',
			),
		);

		/**
		 * Filters the default taxonomy terms.
		 *
		 * Allows adding default terms for additional taxonomies.
		 * Terms are inserted once and protected from deletion.
		 *
		 * @since 1.2.2
		 *
		 * @param array $defaults Default terms keyed by taxonomy name.
		 */
		return apply_filters( 'tasty_recipes_default_taxonomy_terms', $defaults );
	}

	/**
	 * Insert default terms for all recipe taxonomies.
	 * Should be called after taxonomies are registered.
	 * Uses an option to ensure this only runs once.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function insert_default_taxonomy_terms() {
		$option_key = 'tasty_recipes_default_terms_inserted_v2';

		if ( get_option( $option_key ) ) {
			return;
		}

		$defaults = self::get_default_taxonomy_terms();

		foreach ( $defaults as $taxonomy => $terms ) {
			foreach ( $terms as $term_name ) {
				if ( ! term_exists( $term_name, $taxonomy ) ) {
					wp_insert_term( $term_name, $taxonomy );
				}
			}
		}

		update_option( $option_key, true );
	}

	/**
	 * Check if a term is a default term that should not be deleted.
	 *
	 * @since 1.2
	 *
	 * @param int|string $term_id  The term ID.
	 * @param string     $taxonomy The taxonomy name.
	 *
	 * @return bool
	 */
	public static function is_default_term( $term_id, $taxonomy ) {
		$defaults = self::get_default_taxonomy_terms();

		if ( ! isset( $defaults[ $taxonomy ] ) ) {
			return false;
		}

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		return in_array( $term->name, $defaults[ $taxonomy ], true );
	}

	/**
	 * Prevent deletion of default taxonomy terms.
	 *
	 * @since 1.2
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return void
	 */
	public static function filter_pre_delete_term( $term_id, $taxonomy ) {
		if ( self::is_default_term( $term_id, $taxonomy ) ) {
			wp_die(
				esc_html__( 'This is a default term and cannot be deleted.', 'tasty-recipes-lite' ),
				esc_html__( 'Cannot Delete Term', 'tasty-recipes-lite' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Registers our rewrite rules for print pages.
	 *
	 * @return void
	 */
	public static function action_init_register_rewrite_rules() {
		add_rewrite_endpoint( self::get_print_query_var(), EP_PERMALINK | EP_PAGES );
	}

	/**
	 * Registers our custom oEmbed providers.
	 *
	 * @return void
	 */
	public static function action_init_register_oembed_providers() {
		wp_oembed_add_provider( '#video\.mediavine\.com/videos/.*\.js#', 'https://embed.mediavine.com/oembed/', true );
		wp_oembed_add_provider( '#https://dashboard\.mediavine\.com/videos/.*/edit#', 'https://embed.mediavine.com/oembed/', true );
		wp_oembed_add_provider( '#https://reporting\.mediavine\.com/sites/[\d]+/videos/edit/.+#', 'https://embed.mediavine.com/oembed/', true );
		wp_oembed_add_provider( '#https?://((m|www)\.)?youtube\.com/shorts/*#i', 'https://www.youtube.com/oembed', true );
	}

	/**
	 * Filters rewrite rules to avoid loading post content at /print/ URL.
	 *
	 * @param array $rewrite_rules Existing rewrite rules.
	 *
	 * @return array
	 */
	public static function filter_rewrite_rules_array( $rewrite_rules ) {
		$new_rewrite_rules = array();
		foreach ( $rewrite_rules as $match => $rule ) {
			$match                       = str_replace(
				'/' . self::get_print_query_var() . '(/(.*))?/?$',
				'/' . self::get_print_query_var() . '(/(.*))/?$',
				$match
			);
			$new_rewrite_rules[ $match ] = $rule;
		}
		return $new_rewrite_rules;
	}

	/**
	 * Fetches Nutrifox API data when the Nutrifox ID is updated.
	 *
	 * @param mixed  $check      Existing check value.
	 * @param int    $object_id  ID for the post being updated.
	 * @param string $meta_key   Post meta key.
	 * @param string $meta_value New meta value.
	 *
	 * @return mixed
	 */
	public static function filter_update_post_metadata_nutrifox_id( $check, $object_id, $meta_key, $meta_value ) {
		if ( 'tasty_recipe' !== get_post_type( $object_id ) || 'nutrifox_id' !== $meta_key ) {
			return $check;
		}
		if ( empty( $meta_value ) ) {
			delete_post_meta( $object_id, 'nutrifox_response' );
			delete_post_meta( $object_id, 'nutrifox_error' );
			return $check;
		}
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response      = wp_remote_get( sprintf( '%s/api/recipes/%d', Tasty_Recipes::nutrifox_url(), $meta_value ) );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( ! is_wp_error( $response ) && 200 === $response_code ) {
			$body          = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $body, true );
			update_post_meta( $object_id, 'nutrifox_response', $response_data );
			delete_post_meta( $object_id, 'nutrifox_error' );
		} else {
			if ( ! is_wp_error( $response ) ) {
				// translators: Nutrifox HTTP error code.
				$response = new \WP_Error( 'nutrifox-api', sprintf( __( 'Nutrifox API request failed (HTTP code %d)', 'tasty-recipes-lite' ), $response_code ) );
			}
			update_post_meta( $object_id, 'nutrifox_error', $response );
			delete_post_meta( $object_id, 'nutrifox_response' );
		}
		return $check;
	}

	/**
	 * Fetches and stores oEmbed data when the recipe video URL is updated.
	 *
	 * @param mixed  $check      Existing check value.
	 * @param int    $object_id  ID for the post being updated.
	 * @param string $meta_key   Post meta key.
	 * @param string $meta_value New meta value.
	 *
	 * @return mixed
	 */
	public static function filter_update_post_metadata_video_url( $check, $object_id, $meta_key, $meta_value ) {
		if ( 'tasty_recipe' !== get_post_type( $object_id ) || 'video_url' !== $meta_key ) {
			return $check;
		}
		if ( empty( $meta_value ) ) {
			delete_post_meta( $object_id, 'video_url_response' );
			delete_post_meta( $object_id, 'video_url_error' );
			return $check;
		}

		// Looks like a shortcode.
		if ( str_starts_with( trim( $meta_value ), '[' ) ) {
			$shortcode = trim( $meta_value, '[] ' ); // Deliberate empty space.
			// Only AdThrive shortcodes are supported.
			$adthrive_beginning = 'adthrive-in-post-video-player ';
			if ( 0 !== stripos( $shortcode, $adthrive_beginning ) ) {
				delete_post_meta( $object_id, 'video_url_response' );
				update_post_meta( $object_id, 'video_url_error', new \WP_Error( 'video-url', __( 'Unknown shortcode in video URL.', 'tasty-recipes-lite' ) ) );
				return $check;
			}
			$shortcode_inner = substr( $shortcode, strlen( $adthrive_beginning ) );
			// WP 4.0 doesn't correctly handle dashes in shortcode attributes.
			$shortcode_inner = str_replace(
				array(
					'video-id',
					'upload-date',
				),
				array(
					'video_id',
					'upload_date',
				),
				$shortcode_inner
			);
			$atts            = shortcode_parse_atts( $shortcode_inner );
			if ( empty( $atts['video_id'] ) ) {
				delete_post_meta( $object_id, 'video_url_response' );
				update_post_meta( $object_id, 'video_url_error', new \WP_Error( 'video-url', __( 'Shortcode is missing video id.', 'tasty-recipes-lite' ) ) );
				return $check;
			}
			$response_data = Tasty_Recipes::get_template_part(
				'video/adthrive-oembed-response',
				array(
					'video_id'    => $atts['video_id'],
					'title'       => isset( $atts['name'] ) ? $atts['name'] : '',
					'description' => isset( $atts['description'] ) ? $atts['description'] : '',
					'upload_date' => isset( $atts['upload_date'] ) ? $atts['upload_date'] : '',
				)
			);
			update_post_meta( $object_id, 'video_url_response', json_decode( $response_data ) );
			delete_post_meta( $object_id, 'video_url_error' );
			return $check;
		}

		$existing_value = get_post_meta( $object_id, $meta_key, true );
		if ( $existing_value !== $meta_value || ! get_post_meta( $object_id, 'video_url_response', true ) ) {
			if ( ! function_exists( '_wp_oembed_get_object' ) ) {
				require_once ABSPATH . WPINC . '/class-oembed.php';
			}
			$wp_oembed = _wp_oembed_get_object();
			$provider  = $wp_oembed->get_provider( $meta_value );
			if ( ! $provider ) {
				delete_post_meta( $object_id, 'video_url_response' );
				update_post_meta( $object_id, 'video_url_error', new \WP_Error( 'video-url', __( 'Unknown provider for URL.', 'tasty-recipes-lite' ) ) );
			}

			$response_data = $wp_oembed->fetch( $provider, $meta_value );
			if ( false !== $response_data ) {
				update_post_meta( $object_id, 'video_url_response', $response_data );
				delete_post_meta( $object_id, 'video_url_error' );

				/**
				 * Enrich the video data with additional metadata.
				 *
				 * @since 1.0
				 *
				 * @param int    $object_id  ID for the post being updated.
				 * @param object $meta_value Existing oEmbed response data.
				 */
				do_action( 'tasty_recipes_enrich_video', $object_id, $meta_value );
			} else {
				delete_post_meta( $object_id, 'video_url_response' );
				update_post_meta( $object_id, 'video_url_error', new \WP_Error( 'video-url', __( 'Invalid response from provider.', 'tasty-recipes-lite' ) ) );
			}
		}
		return $check;
	}

	/**
	 * Generates our custom JSON+LD image sizes when a thumbnail is assigned to a recipe.
	 *
	 * @param mixed  $check      Existing check value.
	 * @param int    $object_id  ID for the post being updated.
	 * @param string $meta_key   Post meta key.
	 * @param string $meta_value New meta value.
	 *
	 * @return mixed
	 */
	public static function filter_update_post_metadata_thumbnail_id( $check, $object_id, $meta_key, $meta_value ) {

		if ( 'tasty_recipe' !== get_post_type( $object_id ) || '_thumbnail_id' !== $meta_key ) {
			return $check;
		}
		if ( empty( $meta_value ) ) {
			return $check;
		}
		self::generate_attachment_image_sizes( (int) $meta_value );
		return $check;
	}

	/**
	 * Cron callback to generate image sizes for attachments associated with Tasty Recipes.
	 *
	 * @return void
	 */
	public static function action_tasty_recipes_process_thumbnails() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$attach_ids = $wpdb->get_col(
			"SELECT pm.meta_value FROM {$wpdb->postmeta} as pm
			LEFT JOIN {$wpdb->posts} as p ON p.ID = pm.post_id
			WHERE pm.meta_key='_thumbnail_id' AND p.post_type='tasty_recipe'"
		);
		foreach ( $attach_ids as $attach_id ) {
			if ( empty( $attach_id ) ) {
				continue;
			}
			self::generate_attachment_image_sizes( $attach_id );
		}
	}

	/**
	 * Generates our extra image sizes without registering the image sizes.
	 *
	 * @param int $attachment_id Id for the attachment.
	 *
	 * @return bool Success state.
	 */
	public static function generate_attachment_image_sizes( $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return false;
		}
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return false;
		}
		$existing = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		// No image sizes so something must've gone wrong.
		if ( empty( $existing['sizes'] ) ) {
			return false;
		}
		$new_sizes = array();
		foreach ( Distribution_Metadata::get_json_ld_image_sizes( $attachment_id ) as $name => $image_size ) {
			$key = 'tasty-recipes-' . $name;
			if ( ! isset( $existing['sizes'][ $key ] ) ) {
				$new_sizes[ $key ] = array(
					'width'  => $image_size[0],
					'height' => $image_size[1],
					'crop'   => true,
				);
			}
		}
		if ( ! empty( $new_sizes ) ) {
			$new_sizes         = $editor->multi_resize( $new_sizes );
			$existing['sizes'] = array_merge( $existing['sizes'], $new_sizes );
		}
		update_post_meta( $attachment_id, '_wp_attachment_metadata', $existing );
		return true;
	}

	/**
	 * Emulates the WordPress auto-embed behavior for content immediately after
	 * line breaks and list items.
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string
	 */
	public static function autoembed_advanced( $content ) {
		// Find URLs immediately after list items '<li>' and line breaks '<br />'.
		$content = preg_replace_callback(
			'#(^|<li(?: [^>]*)?>\s*|<p(?: [^>]*)?>\s*|\n\s*|<br(?: [^>]*)?>\s*)(https?://[^\s<>"]+)(\s*<\/li>|\s*<\/p>|\s*<br(?: [^>]*)?>|\s*\n|$)#Ui',
			function ( $matches ) {
				global $wp_embed;
				if ( ! $wp_embed ) {
					return $matches[0];
				}
				return $matches[1] . $wp_embed->shortcode( array(), $matches[2] ) . $matches[3];
			},
			$content
		);
		return $content;
	}

	/**
	 * Filters template loading to load our print template when
	 * print view is request.
	 *
	 * @param string $template Existing template being loaded.
	 *
	 * @return string
	 */
	public static function filter_template_include( $template ) {

		$recipe_id = (int) get_query_var( self::get_print_query_var() );
		if ( is_singular() && $recipe_id ) {
			$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( get_queried_object_id() );
			if ( in_array( $recipe_id, $recipe_ids, true ) ) {
				$template = Tasty_Recipes::get_template_path( 'recipe-print' );
			}
		}

		return $template;
	}

	/**
	 * Modify the taxonomy archive query to only include recipes with parent posts.
	 *
	 * @since 1.2
	 *
	 * @param \WP_Query $query The main query.
	 *
	 * @return void
	 */
	public static function filter_taxonomy_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! self::is_recipe_taxonomy( $query ) ) {
			return;
		}

		// Force the post type to tasty_recipe since it's not public.
		$query->set( 'post_type', 'tasty_recipe' );

		// Only include recipes that have a parent post.
		$existing_meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $existing_meta_query ) ) {
			$existing_meta_query = array();
		}

		$existing_meta_query[] = array(
			'key'     => '_tasty_recipe_parents',
			'compare' => 'EXISTS',
		);

		$query->set( 'meta_query', $existing_meta_query );
	}

	/**
	 * Check if the query is for a recipe taxonomy.
	 * 
	 * @since 1.2
	 *
	 * @param \WP_Query $query The query object.
	 *
	 * @return bool
	 */
	private static function is_recipe_taxonomy( $query ) {
		foreach ( array_keys( self::get_taxonomy_definitions() ) as $taxonomy ) {
			if ( $query->get( $taxonomy ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get archive display data for a recipe.
	 * Returns image, title, description from recipe but link to parent post.
	 *
	 * @since 1.2
	 *
	 * @param int $recipe_id The recipe post ID.
	 *
	 * @return array|false Array with display data or false if no parent exists.
	 */
	public static function get_archive_item_data( $recipe_id ) {
		$parent_post_id = get_post_meta( $recipe_id, '_tasty_recipe_parents', true );
		if ( empty( $parent_post_id ) ) {
			return false;
		}

		$parent_post = get_post( $parent_post_id );
		if ( ! $parent_post || 'publish' !== $parent_post->post_status ) {
			return false;
		}

		$recipe = Objects\Recipe::get_by_id( $recipe_id );
		if ( ! $recipe ) {
			return false;
		}

		$image_data = $recipe->get_image_size( 'medium' );

		return array(
			'recipe_id'      => $recipe_id,
			'parent_post_id' => $parent_post_id,
			'title'          => $recipe->get_title(),
			'description'    => $recipe->get_description(),
			'image_id'       => $recipe->get_image_id(),
			'image_html'     => $image_data ? $image_data['html'] : '',
			'image_url'      => $image_data ? $image_data['url'] : '',
			'permalink'      => get_permalink( $parent_post_id ),
			'parent_title'   => $parent_post->post_title,
		);
	}

	/**
	 * Filter the permalink for recipe posts on taxonomy archives.
	 * Returns the parent post's permalink instead of the recipe's (non-existent) permalink.
	 *
	 * @since 1.2
	 *
	 * @param string   $permalink The post's permalink.
	 * @param \WP_Post $post      The post object.
	 *
	 * @return string
	 */
	public static function filter_recipe_permalink( $permalink, $post ) {
		if ( 'tasty_recipe' !== $post->post_type || ! self::is_recipe_taxonomy_archive() ) {
			return $permalink;
		}

		$parent_post_id = get_post_meta( $post->ID, '_tasty_recipe_parents', true );
		if ( empty( $parent_post_id ) ) {
			return $permalink;
		}

		$parent_post = get_post( $parent_post_id );
		if ( ! $parent_post || 'publish' !== $parent_post->post_status ) {
			return $permalink;
		}

		return get_permalink( $parent_post_id );
	}

	/**
	 * Filter the excerpt for recipe posts on taxonomy archives.
	 * Returns the recipe description since recipes don't have a traditional excerpt.
	 *
	 * @since 1.2
	 *
	 * @param string $excerpt The post excerpt.
	 *
	 * @return string
	 */
	public static function filter_recipe_excerpt( $excerpt ) {
		if ( 'tasty_recipe' !== get_post_type() || ! self::is_recipe_taxonomy_archive() ) {
			return $excerpt;
		}

		$recipe = Objects\Recipe::get_by_id( get_the_ID() );
		if ( ! $recipe ) {
			return $excerpt;
		}

		$description = $recipe->get_description();
		if ( ! $description ) {
			return $excerpt;
		}

		$description    = wp_strip_all_tags( $description );
		$excerpt_length = 55;
		$excerpt_more   = ' [&hellip;]';

		return wp_trim_words( $description, $excerpt_length, $excerpt_more );
	}

	/**
	 * Check if we're currently on a recipe taxonomy archive page.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	public static function is_recipe_taxonomy_archive() {
		$recipe_taxonomies = array_keys( self::get_taxonomy_definitions() );

		foreach ( $recipe_taxonomies as $taxonomy ) {
			if ( is_tax( $taxonomy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter cooking attribute values to add links to taxonomy archives.
	 *
	 * @since 1.2
	 *
	 * @param string $value     The attribute value.
	 * @param string $attribute The attribute name (category, method, cuisine, diet).
	 *
	 * @return string
	 */
	public static function filter_cooking_attribute_links( $value, $attribute ) {
		$taxonomy_map = array(
			'category' => 'tasty_recipe_category',
			'method'   => 'tasty_recipe_method',
			'cuisine'  => 'tasty_recipe_cuisine',
			'diet'     => 'tasty_recipe_diet',
		);

		if ( ! isset( $taxonomy_map[ $attribute ] ) ) {
			return $value;
		}

		$taxonomy = $taxonomy_map[ $attribute ];

		/**
		 * Filters whether taxonomy terms should be rendered as links on the recipe card.
		 *
		 * When set to false, taxonomy terms will be displayed as plain text
		 * instead of links to their archive pages.
		 *
		 * @since 1.2.1
		 *
		 * @param bool   $enable_links Whether to render terms as links. Default true.
		 * @param string $taxonomy     The taxonomy name (e.g., 'tasty_recipe_category').
		 * @param string $attribute    The attribute name (e.g., 'category').
		 */
		$enable_links = apply_filters( 'tasty_recipes_enable_taxonomy_links', true, $taxonomy, $attribute );

		if ( ! $enable_links ) {
			return esc_html( $value );
		}

		$terms        = array_map( 'trim', explode( ',', $value ) );
		$linked_terms = array();

		foreach ( $terms as $term_name ) {
			$term = get_term_by( 'name', $term_name, $taxonomy );

			if ( $term && ! is_wp_error( $term ) ) {
				$term_link = get_term_link( $term, $taxonomy );

				if ( ! is_wp_error( $term_link ) ) {
					$linked_terms[] = '<a data-tasty-recipes-customization="detail-value-color.color" href="' . esc_url( $term_link ) . '">' . esc_html( $term_name ) . '</a>';
					continue;
				}
			}

			$linked_terms[] = esc_html( $term_name );
		}

		return implode( ', ', $linked_terms );
	}

	/**
	 * Filters body classes to add a special class when view is loaded.
	 *
	 * @param array $classes Existing body classes.
	 *
	 * @return array
	 */
	public static function filter_body_class( $classes ) {
		if ( get_query_var( self::get_print_query_var() ) ) {
			$classes[] = 'tasty-recipes-print-view';
		}
		return $classes;
	}

	/**
	 * Wrapper method for getting the print query variable.
	 *
	 * @return string
	 */
	public static function get_print_query_var() {
		/**
		 * Get the 'keyword' used in the print URL.
		 *
		 * @param string $query_var
		 */
		return apply_filters( 'tasty_recipes_print_query_var', self::PRINT_QUERY_VAR );
	}

	/**
	 * Fetches Nutrifox conversion data when the ingredients are updated.
	 *
	 * @deprecated 1.0
	 *
	 * @param mixed $check Existing check value.
	 *
	 * @return mixed
	 */
	public static function filter_update_post_metadata_ingredients( $check ) {
		_deprecated_function( __METHOD__, '1.0', 'Tasty_Recipes_Pro\Content_Model::filter_update_post_metadata_ingredients' );
		return $check;
	}

	/**
	 * Generates the Nutrifox conversion data.
	 *
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function generate_nutrifox_conversion() {
		_deprecated_function( __METHOD__, '1.0', 'Tasty_Recipes_Pro\Content_Model::generate_nutrifox_conversion' );
		return false;
	}

	/**
	 * Cron callback to apply unit conversion to recipes without it.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function action_tasty_recipes_apply_unit_conversion() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Cron callback to enrich YouTube responses with additional metadata.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function action_tasty_recipes_enrich_youtube_embeds() {
		_deprecated_function( __METHOD__, '1.0', 'Tasty_Recipes_Pro\Content_Model::action_tasty_recipes_enrich_youtube_embeds' );
	}

	/**
	 * Enriches the YouTube oEmbed response with additional metadata.
	 *
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function enrich_youtube_oembed() {
		_deprecated_function( __METHOD__, '1.0', 'Tasty_Recipes_Pro\Content_Model::enrich_youtube_oembed' );
		return false;
	}

	/**
	 * Applies the enrichment to the response data.
	 *
	 * @deprecated 1.0
	 *
	 * @param string $video_url     URL assigned to the recipe.
	 * @param object $response_data Existing oEmbed response data.
	 *
	 * @return mixed
	 */
	public static function apply_youtube_enrichment_to_response_data( $video_url, $response_data ) {
		_deprecated_function( __METHOD__, '1.0', 'Tasty_Recipes_Pro\Content_Model::apply_youtube_enrichment_to_response_data' );
		return $response_data;
	}
}

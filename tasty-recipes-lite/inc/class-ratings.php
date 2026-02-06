<?php
/**
 * Manages ratings integration with comments.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty\Framework\Utils\Vars;
use Tasty_Recipes;
use WP_Comment_Query;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Integrations\Bigscoots;

/**
 * Manages ratings integration with comments.
 */
class Ratings {

	/**
	 * Meta key where ratings are stored.
	 *
	 * Defaults to 'ERRating' for historical compatibility with EasyRecipe.
	 *
	 * @var string
	 */
	const COMMENT_META_KEY = 'ERRating';

	/**
	 * Meta key where Cookbook ratings are stored.
	 *
	 * @var string
	 */
	const CB_COMMENT_META_KEY = 'cookbook_comment_rating';

	/**
	 * Meta key where Simple Recipe Pro ratings are stored.
	 *
	 * @var string
	 */
	const SRP_COMMENT_META_KEY = 'recipe_rating';

	/**
	 * Meta key where WP Recipe Maker ratings are stored.
	 *
	 * @var string
	 */
	const WPRM_COMMENT_META_KEY = 'wprm-comment-rating';

	/**
	 * Meta key where ZipList ratings are stored.
	 *
	 * @var string
	 */
	const ZRP_COMMENT_META_KEY = 'zrdn_post_recipe_rating';

	/**
	 * Card rating hash meta key.
	 *
	 * @var string
	 */
	const RATING_COMMENT_HASH_META_KEY = 'tasty-recipes-comment-hash';

	/**
	 * Whether the SVG has been loaded.
	 *
	 * @var bool
	 */
	private static $svg_loaded = false;

	/**
	 * Whether or not ratings are enabled. Optionally pass a comment status to
	 * check if comments are open for the current post.
	 *
	 * @param string $comment_status Comment status to check. 'open' or 'any'.
	 *
	 * @return bool
	 */
	public static function is_enabled( $comment_status = 'open' ) {
		global $post;

		$enabled = true;
		if ( 'open' === $comment_status && $post && ! comments_open( $post ) ) {
			$enabled = false;
		}

		/**
		 * Permit ratings to be disabled by the end user.
		 *
		 * @param bool
		 */
		return apply_filters( 'tasty_recipes_enable_ratings', $enabled );
	}

	/**
	 * Renders ratings CSS in <head> when enabled.
	 *
	 * @return void
	 */
	public static function action_admin_head() {
		$current_screen = get_current_screen()->id;
		if ( ! in_array( $current_screen, array( 'edit-comments', 'comment' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'tasty-recipes-main' );
	}

	/**
	 * Recalculate total reviews and average rating for the embedded recipe
	 * when a comment changes in some way.
	 *
	 * @param int $comment_id ID of the changed comment.
	 *
	 * @return void
	 */
	public static function action_modify_comment_update_recipe_ratings( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! self::is_enabled( 'any' ) || ! $comment || ! $comment->comment_post_ID ) {
			return;
		}

		// Only process when there is one embedded recipe in a post.
		$recipes = Tasty_Recipes::get_recipes_for_post( (int) $comment->comment_post_ID );
		if ( 1 !== count( $recipes ) ) {
			return;
		}

		$recipe = reset( $recipes );
		self::update_recipe_rating( $recipe, (int) $comment->comment_post_ID );
	}

	/**
	 * Update rating for a recipe embedded within a given post.
	 *
	 * @param Recipe $recipe  Existing recipe object.
	 * @param int    $post_id ID for the post with the recipe.
	 *
	 * @return void
	 */
	public static function update_recipe_rating( $recipe, $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ratings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT $wpdb->commentmeta.comment_id, $wpdb->commentmeta.meta_value
				FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE $wpdb->comments.comment_post_ID=%d
				AND $wpdb->comments.comment_approved=1
				AND (
					$wpdb->commentmeta.meta_key=%s OR
					$wpdb->commentmeta.meta_key=%s OR
					$wpdb->commentmeta.meta_key=%s OR
					$wpdb->commentmeta.meta_key=%s OR
					$wpdb->commentmeta.meta_key=%s
				)",
				$post_id,
				self::COMMENT_META_KEY,
				self::CB_COMMENT_META_KEY,
				self::SRP_COMMENT_META_KEY,
				self::WPRM_COMMENT_META_KEY,
				self::ZRP_COMMENT_META_KEY
			)
		);
		// Some comments may have ER, Cookbook, and Simple Recipe Pro.
		$comment_ratings = array();
		foreach ( $ratings as $rating ) {
			if ( $rating->meta_value >= 1 ) {
				$comment_ratings[ $rating->comment_id ] = $rating->meta_value;
			}
		}
		$ratings        = array_values( $comment_ratings );
		$total_reviews  = count( $ratings );
		$create_ratings = get_post_meta( $recipe->get_id(), 'create_ratings', true );
		if ( ! empty( $create_ratings ) ) {
			$ratings        = array_merge( $ratings, array_fill( 0, $create_ratings['rating_count'], $create_ratings['rating'] ) );
			$total_reviews += $create_ratings['rating_count'];
		}
		$srp_ratings = get_post_meta( $recipe->get_id(), 'srp_ratings', true );
		if ( ! empty( $srp_ratings ) ) {
			$srp_ratings    = json_decode( $srp_ratings, true );
			$ratings        = array_merge( $ratings, array_values( $srp_ratings ) );
			$total_reviews += count( $srp_ratings );
		}
		$wprm_ratings = get_post_meta( $recipe->get_id(), 'wprm_ratings', true );
		if ( ! empty( $wprm_ratings ) ) {
			$ratings        = array_merge( $ratings, array( $wprm_ratings['total'] ) );
			$total_reviews += $wprm_ratings['count'];
		}
		$zrp_ratings = get_post_meta( $recipe->get_id(), 'zrp_ratings', true );
		if ( ! empty( $zrp_ratings ) ) {
			$zrp_ratings_data = wp_list_pluck( $zrp_ratings, 'rating' );
			$ratings          = array_merge( $ratings, array_values( $zrp_ratings_data ) );
			$total_reviews   += count( $zrp_ratings_data );
		}
		$average_rating = '';
		if ( $total_reviews ) {
			$average_rating = round( array_sum( $ratings ) / $total_reviews, 4 );
		}
		$recipe->set_total_reviews( $total_reviews );
		$recipe->set_average_rating( $average_rating );
		/**
		 * Fires when a recipe's rating has been updated.
		 *
		 * @param object $recipe  Recipe object.
		 * @param int    $post_id ID for the post.
		 */
		do_action( 'tasty_recipes_updated_recipe_rating', $recipe, $post_id );
	}

	/**
	 * Processes comment submission for its rating (if set).
	 *
	 * @param array $commentdata Comment data to be saved.
	 *
	 * @return array
	 */
	public static function filter_preprocess_comment( $commentdata ) {
		// We need to remove the old quick rating that was added before from this user if found.
		list( $comment_hash, $comment_id ) = self::get_previous_comment( $commentdata['comment_post_ID'] );
		if ( $comment_id ) {
			wp_delete_comment( $comment_id );
		}

		$rating = Utils::post_param( 'tasty-recipes-rating' );
		if ( empty( $rating ) ) {
			return $commentdata;
		}

		if ( ! isset( $commentdata['comment_meta'] ) ) {
			$commentdata['comment_meta'] = array();
		}
		$commentdata['comment_meta'][ self::COMMENT_META_KEY ]             = (int) $rating;
		$commentdata['comment_meta'][ self::RATING_COMMENT_HASH_META_KEY ] = $comment_hash;

		return $commentdata;
	}

	/**
	 * Handles a REST API request to create a new comment.
	 *
	 * @param object $comment New comment object.
	 * @param object $request Request object.
	 *
	 * @return void
	 */
	public static function action_rest_insert_comment( $comment, $request ) {
		if ( ! empty( $request['meta']['tasty-recipes-rating'] ) ) {
			update_comment_meta(
				$comment->comment_ID,
				self::COMMENT_META_KEY,
				(int) $request['meta']['tasty-recipes-rating']
			);
		}
	}

	/**
	 * Filters the comment form HTML to include a ratings input.
	 *
	 * @param string $comment_form Existing comment form HTML.
	 *
	 * @return string
	 */
	public static function filter_comment_form_field_comment( $comment_form ) {
		$post_id = get_queried_object_id();
		if ( ! self::is_enabled( 'any' ) || ! $post_id ) {
			return $comment_form;
		}
		$recipes = Tasty_Recipes::get_recipes_for_post( $post_id );
		if ( 1 !== count( $recipes ) ) {
			return $comment_form;
		}
		ob_start();
		?>
		<fieldset class="tasty-recipes-ratings tasty-recipes-comment-form">
			<legend><?php esc_html_e( 'Recipe rating', 'tasty-recipes-lite' ); ?></legend>
			<?php self::show_rating_stars_html(); ?>
		</fieldset>
		<?php
		$rating_html = ob_get_clean();
		/**
		 * Control whether the rating HTML appears before or after the comment form.
		 *
		 * @param string $position Can be one of 'before' or 'after'
		 */
		$position = apply_filters( 'tasty_recipes_comment_form_rating_position', 'before' );
		if ( 'before' === $position ) {
			return $rating_html . $comment_form;
		}
		if ( 'after' === $position ) {
			return $comment_form . $rating_html;
		}
		// Invalid $position, so just return the comment form.
		return $comment_form;
	}

	/**
	 * Filters the rendered comment text to include a rating when it exists.
	 *
	 * @param string $comment_text Existing comment text.
	 * @param object $comment      Comment object, if included.
	 *
	 * @return string
	 */
	public static function filter_comment_text( $comment_text, $comment = null ) {

		if ( ! self::is_enabled( 'any' )
			|| ! $comment
			|| ! Tasty_Recipes::has_recipe( $comment->comment_post_ID ) ) {
			return $comment_text;
		}

		$rating_keys = array(
			self::COMMENT_META_KEY,
			self::CB_COMMENT_META_KEY,
			self::SRP_COMMENT_META_KEY,
			self::WPRM_COMMENT_META_KEY,
			self::ZRP_COMMENT_META_KEY,
		);
		$rating      = '';
		foreach ( $rating_keys as $rating_key ) {
			$rating = get_comment_meta( $comment->comment_ID, $rating_key, true );
			if ( $rating ) {
				break;
			}
		}

		if ( ! $rating ) {
			return $comment_text;
		}

		$rating = PHP_EOL . '<p class="tasty-recipes-ratings">' . self::get_rendered_rating( $rating ) . '</p>';
		return $comment_text . $rating;
	}

	/**
	 * Get the HTML for a rendered rating.
	 *
	 * @param float|int $rating        Rating value.
	 * @param string    $customization Integration with card customization.
	 * @param string    $style         Style to use.
	 *
	 * @return string
	 */
	public static function get_rendered_rating( $rating, $customization = '', $style = null ) {
		if ( ! $rating ) {
			return '';
		}

		if ( is_null( $style ) ) {
			$settings = Tasty_Recipes::get_filtered_customization_settings();
			$style    = $settings['star_ratings_style'];
		}

		$icons = self::get_icons_by_type( $style );
		if ( empty( $icons ) ) {
			return '';
		}

		$ret  = '';
		$ret .= self::maybe_load_sprite( $icons );

		for ( $i = 1; $i <= 5; $i++ ) {
			$show_star = ceil( $rating ) >= $i || in_array( $style, array( 'outline', 'solid' ), true );
			if ( ! $show_star ) {
				continue;
			}

			$percentage = self::get_fill_percent(
				array(
					'i'       => $i,
					'average' => $rating,
				)
			);

			$ret .= '<span';
			if ( ! empty( $customization ) ) {
				$ret .= ' data-tasty-recipes-customization="' . esc_attr( $customization ) . '"';
			}
			$ret .= ' class="' . esc_attr( 'tasty-recipes-rating tasty-recipes-rating-' . $style ) . '"';
			$ret .= ' data-tr-clip="' . esc_attr( (string) $percentage ) . '"';
			$ret .= ' data-rating="' . intval( $i ) . '"';
			$ret .= '>';
			if ( $percentage === 100 || $percentage === 0 ) {
				$ret .= $icons['full'];
			} else {
				$ret .= $icons['checked'];
			}
			$ret .= '</span>';
		}
		return $ret;
	}

	/**
	 * Get the rating icon HTML.
	 *
	 * @since 3.12
	 *
	 * @param string $style Type of the icon. ie. solid or outline.
	 *
	 * @return array
	 */
	private static function get_icons_by_type( $style ) {
		if ( 'solid' === $style ) {
			$svg_name = 'star-rating-solid.svg';
		} elseif ( 'outline' === $style ) {
			$svg_name = 'star-rating-clip.svg';
		}

		if ( empty( $svg_name ) ) {
			return array();
		}

		$star_svg = Utils::get_contents( 'assets/images/' . $svg_name, 'path' );

		$sprite_name = str_replace( '.svg', '-sprite.svg', $svg_name );
		$star_sprite = Utils::get_contents( 'assets/images/' . $sprite_name, 'path' );

		return array(
			'checked'   => Utils::minify_js( $star_svg ),
			'unchecked' => $star_svg,
			'sprite'    => Utils::minify_js( $star_sprite ),
			'full'      => Utils::minify_js( self::filled_star_from_sprite() ),
		);
	}

	/**
	 * Load the SVG sprite if it hasn't been loaded yet.
	 *
	 * @since 3.14
	 *
	 * @param array $icons Icons to load.
	 *
	 * @return string
	 */
	private static function maybe_load_sprite( $icons ) {
		if ( self::$svg_loaded ) {
			return '';
		}

		self::$svg_loaded = true;
		return Utils::kses( $icons['sprite'] );
	}

	/**
	 * Show the filled star from the sprite.
	 *
	 * @since 3.14
	 *
	 * @return string
	 */
	private static function filled_star_from_sprite() {
		return '<svg class="tasty-recipes-svg" width="18" height="17">' .
			'<use href="#wpt-star-full" />' .
			'</svg>';
	}

	/**
	 * Filter customization settings to set the default value of stars color.
	 *
	 * @since 3.9
	 *
	 * @param array $settings Array of customization settings.
	 *
	 * @return array
	 */
	public static function apply_default_rating_stars_color( $settings = array() ) {
		if ( ! empty( $settings['star_color'] ) ) {
			return $settings;
		}

		$settings['star_color'] = ! empty( $settings['detail_value_color'] ) ? $settings['detail_value_color'] : '#F2B955';
		return $settings;
	}

	/**
	 * Get rating stars HTML.
	 *
	 * @param bool $no_ratings Show no rating template without review yet.
	 * @param int  $average    The rating valie to show.
	 *
	 * @return string
	 */
	public static function get_rating_stars_html( $no_ratings = false, $average = 0 ) {
		if ( ! comments_open() && ! is_admin() ) {
			return '';
		}

		$settings     = Tasty_Recipes::get_filtered_customization_settings();
		$rating_style = $settings['star_ratings_style'];

		$icons  = self::get_icons_by_type( $rating_style );
		$sprite = self::maybe_load_sprite( $icons );

		return $sprite .
			Tasty_Recipes::get_template_part(
				'parts/ratings',
				array(
					'no_ratings'       => $no_ratings,
					'icons'            => $icons,
					'remove_new_lines' => true,
					'average'          => $average,
					'rating_style'     => $rating_style,
				)
			);
	}

	/**
	 * Show rating stars HTML that has already been escaped.
	 *
	 * @since 1.0
	 *
	 * @param bool $no_ratings Show no rating template without review yet.
	 * @param int  $average    The rating valie to show.
	 *
	 * @return void
	 */
	public static function show_rating_stars_html( $no_ratings = false, $average = 0 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_rating_stars_html( $no_ratings, $average );
	}

	/**
	 * Get the filled percentage for the stars.
	 *
	 * @since 3.12
	 *
	 * @param array $atts {
	 *     Attributes to be used.
	 *
	 *     @type int $i       Rating value.
	 *     @type int $average Default rating value.
	 * }
	 *
	 * @return int
	 */
	public static function get_fill_percent( $atts ) {
		$diff = round( $atts['average'], 1 ) - $atts['i'] + 1;
		if ( $diff < 1 && $diff > 0 ) {
			$percentage = $diff * 100;
		} elseif ( $diff >= 1 ) {
			$percentage = 100;
		} else {
			$percentage = 0;
		}

		return $percentage;
	}

	/**
	 * Allow empty comment when rating is more than 3.
	 *
	 * @param bool  $allow        Whether to allow empty comments. Default false.
	 * @param array $comment_data Array of comment data to be sent to wp_insert_comment().
	 *
	 * @return bool
	 */
	public static function filter_allow_rating_empty_comment( $allow, $comment_data ) {
		$recipes = Tasty_Recipes::get_recipes_for_post( (int) $comment_data['comment_post_ID'] );
		if ( 1 !== count( $recipes ) ) {
			return $allow;
		}

		$minimum_rating_without_comment = self::get_minimum_rating_without_comment( $comment_data );
		$post_rating                    = (int) Utils::get_post_value( 'tasty-recipes-rating' );
		return ! empty( $post_rating ) && $post_rating >= $minimum_rating_without_comment;
	}

	/**
	 * Get minimum rating value without requiring comment (filtered).
	 *
	 * @param array $comment_data Comment data if found.
	 *
	 * @return int
	 */
	public static function get_minimum_rating_without_comment( $comment_data = array() ) {
		if ( is_callable( 'Tasty_Recipes_Pro\Ratings::get_minimum_rating_without_comment' ) ) {
			return \Tasty_Recipes_Pro\Ratings::get_minimum_rating_without_comment( $comment_data );
		}

		return 6;
	}

	/**
	 * Add the rating stars sprite to the recipe card.
	 * 
	 * @since 1.0
	 * 
	 * @param string $output - The output of the recipe card.
	 *
	 * @return string
	 */
	public static function add_star_sprite_to_recipe_card( $output ) {
		$settings     = Tasty_Recipes::get_customization_settings();
		$rating_style = $settings['star_ratings_style'];
		$icons        = self::get_icons_by_type( $rating_style );

		return Utils::kses( $icons['sprite'] ) . $output;
	}

	/**
	 * Save the quick rating.
	 *
	 * @since 3.12
	 *
	 * @return void
	 */
	public static function save_rating() {
		if ( is_user_logged_in() ) {
			check_ajax_referer( 'tasty-recipes-save-rating', 'nonce' );
		}

		$rating    = Vars::post_param( 'rating', 'intval' );
		$post_id   = Vars::post_param( 'post_id', 'intval' );
		$recipe_id = Vars::post_param( 'recipe_id', 'intval' );
		$recipe    = ! empty( $recipe_id ) ? Recipe::get_by_id( $recipe_id ) : null;

		if ( ! $rating || ! $post_id || ! $recipe ) {
			Utils::send_json_error( __( 'Invalid request', 'tasty-recipes-lite' ) );
		}

		list( $comment_hash, $comment_id ) = self::get_previous_comment( $post_id );

		$has_comment = self::has_comment( $comment_id );
		$creating    = empty( $comment_id ); // No previous rating found.
		$low_rating  = $rating < self::get_minimum_rating_without_comment();
		$message_id  = self::generate_message_id( $creating, $low_rating, $has_comment );

		if ( $low_rating ) {
			wp_send_json_success(
				array(
					'message' => self::rating_message( $message_id, $rating ),
					'average' => $recipe->get_average_rating(),
					'comment' => self::prepare_comment_response( $has_comment ),
				)
			);
		}

		if ( $creating ) {
			self::create_rating( $comment_hash );
		} else {
			// Update the rating on existing comment.
			update_comment_meta( $comment_id, self::COMMENT_META_KEY, $rating );
		}

		self::update_recipe_rating( $recipe, $post_id );

		$response = array(
			'hash'    => $comment_hash,
			'message' => self::rating_message( $message_id, $rating ),
			'count'   => (int) $recipe->get_total_reviews(),
			'average' => $recipe->get_average_rating(),
		);

		if ( 2 >= $response['count'] ) {
			$response['label'] = self::get_rating_label( $response['count'], $response['average'] );
		}

		Bigscoots::init();

		do_action( 'tasty_recipes_after_saving_rating', $post_id, $recipe_id, $rating );

		wp_send_json_success( $response );
	}

	/**
	 * Check if the rating has a comment or not.
	 *
	 * @since 3.12
	 *
	 * @param int $comment_id Comment ID.
	 *
	 * @return bool|object
	 */
	private static function has_comment( $comment_id ) {
		$has_comment = false;
		if ( $comment_id ) {
			$comment     = get_comment( $comment_id );
			$has_comment = $comment && ! empty( $comment->comment_content );
		}
		return $has_comment ? $comment : false;
	}

	/**
	 * Save new rating.
	 *
	 * @since 3.12
	 *
	 * @param string $comment_hash Comment hash.
	 *
	 * @return void
	 */
	private static function create_rating( $comment_hash ) {
		$rating  = Vars::post_param( 'rating', 'intval' );
		$post_id = Vars::post_param( 'post_id', 'intval' );

		$comment_args = array(
			'comment_post_ID'   => $post_id,
			'comment_author_IP' => Vars::server_param( 'REMOTE_ADDR' ),
			'comment_meta'      => array(
				self::RATING_COMMENT_HASH_META_KEY => $comment_hash,
				self::COMMENT_META_KEY             => $rating,
			),
		);
		if ( is_user_logged_in() ) {
			$comment_args['user_id'] = get_current_user_id();
		}
		$created = wp_insert_comment( $comment_args );

		if ( ! $created ) {
			Utils::send_json_error( __( 'Something went wrong. Your rating was not saved.', 'tasty-recipes-lite' ) );
		}
	}

	/**
	 * Check if comment is already added and get its hash and ID.
	 *
	 * @since 3.12
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array Comment hash and comment ID
	 */
	private static function get_previous_comment( $post_id ) {
		$comment_hash = self::generate_comment_hash( $post_id );
		return array(
			$comment_hash,
			self::get_comment_id_by_hash( $comment_hash, $post_id ),
		);
	}

	/**
	 * Generate comment hash for specific post and the current user.
	 *
	 * @since 3.12
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private static function generate_comment_hash( $post_id ) {
		$params = array(
			$post_id,
			Vars::server_param( 'REMOTE_ADDR' ),
			Vars::server_param( 'HTTP_USER_AGENT' ),
			wp_nonce_tick( 'tasty-recipes-rating-hash-tick' ),
		);

		if ( is_user_logged_in() ) {
			$params[] = get_current_user_id();
		}

		if ( defined( 'AUTH_SALT' ) && ! empty( AUTH_SALT ) ) {
			$params[] = AUTH_SALT;
		}

		return md5( implode( '::', $params ) );
	}

	/**
	 * Get comment ID by hash and possibly post ID.
	 *
	 * @since 3.12
	 *
	 * @param string $hash    Comment hash.
	 * @param int    $post_id Post ID.
	 *
	 * @return int
	 */
	private static function get_comment_id_by_hash( $hash, $post_id = 0 ) {
		$args = array(
			'fields'     => 'ids',
			'meta_key'   => self::RATING_COMMENT_HASH_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $hash, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		);
		if ( $post_id ) {
			$args['post_id'] = $post_id;
		}

		$comments_query = new WP_Comment_Query( $args );
		$comments       = $comments_query->get_comments();

		// Return the first comment ID.
		return $comments ? $comments[0] : 0;
	}

	/**
	 * Get rating message with the message ID.
	 *
	 * @since 3.12
	 *
	 * @param string $message_id Message ID to get its message.
	 * @param int    $rating     Rating value.
	 *
	 * @return string
	 */
	private static function rating_message( $message_id, $rating ) {
		$messages = array(
			'create-high-rating'         => array(
				'message' => __( 'Thank you for rating!', 'tasty-recipes-lite' ),
				'button'  => self::leave_comment_message(),
			),
			'update-high-rating-comment' => array(
				'message' => self::rating_updated_message( $rating ),
				'button'  => __( 'Update comment', 'tasty-recipes-lite' ),
			),
			'update-high-rating'         => array(
				'message' => self::rating_updated_message( $rating ),
				'button'  => self::leave_comment_message(),
			),
		);

		/**
		 * Filter the rating messages.
		 *
		 * @since 1.0
		 *
		 * @param array $messages Array of messages.
		 *
		 * @return array
		 */
		$messages = apply_filters( 'tasty_recipes_rating_messages', $messages );

		if ( ! isset( $messages[ $message_id ] ) ) {
			$messages[ $message_id ] = array(
				'message' => self::comment_required_message( $rating ),
				'button'  => self::leave_comment_message(),
			);
		}

		return sprintf(
			'%1$s<a href="#respond" class="tasty-recipes-scrollto">%2$s</a>',
			esc_html( $messages[ $message_id ]['message'] ),
			esc_html( $messages[ $message_id ]['button'] )
		);
	}

	/**
	 * Get message when comment is required to create new rating.
	 *
	 * @since 3.12
	 *
	 * @param int $rating The selected rating.
	 *
	 * @return string
	 */
	protected static function comment_required_message( $rating ) {
		$min_rating = self::get_minimum_rating_without_comment();
		if ( $min_rating > 5 ) {
			return __( 'To leave a rating, please share your feedback.', 'tasty-recipes-lite' );
		}

		return sprintf(
			// translators: %1$d star rating.
			__( 'To leave a %1$d-star rating, please share your feedback.', 'tasty-recipes-lite' ),
			$rating
		);
	}

	/**
	 * Get success message when rating is updated.
	 *
	 * @since 3.12
	 *
	 * @param int $rating The selected rating.
	 *
	 * @return string
	 */
	private static function rating_updated_message( $rating ) {
		return sprintf(
			// translators: %1$d star rating.
			__( 'Rating updated to %1$d stars!', 'tasty-recipes-lite' ),
			$rating
		);
	}

	/**
	 * Get the message for leaving a comment.
	 *
	 * @since 3.12
	 *
	 * @return string
	 */
	protected static function leave_comment_message() {
		return __( 'Leave a comment', 'tasty-recipes-lite' );
	}

	/**
	 * Return comment details in ajax response to prefill for editing.
	 *
	 * @since 3.12.2
	 *
	 * @param bool|object $has_comment The comment if it exists.
	 *
	 * @return array
	 */
	private static function prepare_comment_response( $has_comment ) {
		$comment = array();
		if ( ! $has_comment || ! is_object( $has_comment ) ) {
			return $comment;
		}

		$comment['content'] = $has_comment->comment_content;
		if ( ! is_user_logged_in() ) {
			$comment['email'] = $has_comment->comment_author_email;
			$comment['name']  = $has_comment->comment_author;
		}
		return $comment;
	}

	/**
	 * Generate rating message ID based on some criteria.
	 *
	 * @param bool $create       Create new rating or update current one.
	 * @param bool $low_rating   This rating is less than the minimum rating.
	 * @param bool $with_comment This rating has a comment message.
	 *
	 * @return string
	 */
	private static function generate_message_id( $create, $low_rating, $with_comment = false ) {
		$message_id_components   = array();
		$message_id_components[] = $create ? 'create' : 'update';
		$message_id_components[] = $low_rating ? 'low-rating' : 'high-rating';
		if ( ! $create && $with_comment ) {
			$message_id_components[] = 'comment';
		}
		return implode( '-', $message_id_components );
	}

	/**
	 * Get rating label HTML.
	 *
	 * @param int   $total_reviews  Reviews count.
	 * @param float $average_rating Average rating.
	 *
	 * @return string
	 */
	public static function get_rating_label( $total_reviews, $average_rating ) {
		return sprintf(
			// translators: Ratings from number of reviews.
			_n( '%1$s from %2$s review', '%1$s from %2$s reviews', (int) $total_reviews, 'tasty-recipes-lite' ),
			'<span class="average">' . $average_rating . '</span>',
			'<span class="count">' . (int) $total_reviews . '</span>'
		);
	}

	/**
	 * Renders ratings CSS in <head> when enabled.
	 *
	 * @deprecated 3.11.1
	 *
	 * @return void
	 */
	public static function action_wp_head() {
		_deprecated_function( __METHOD__, '3.11.1' );
	}

	/**
	 * Get rating needed styles.
	 *
	 * @since 3.11.1
	 *
	 * @return string
	 */
	public static function get_styles() {
		_deprecated_function( __METHOD__, '1.0' );
		return '';
	}

	/**
	 * Add ratings CSS inline on the page.
	 *
	 * @since 3.12.3
	 *
	 * @return void
	 */
	public static function add_inline_style() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Add rating meta box in admin comment form.
	 *
	 * @since 3.12
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function add_rating_in_admin() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Show star rating options in admin comment form.
	 *
	 * @since 3.12
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function render_rating_meta_box() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Update rating in admin comment.
	 *
	 * @since 3.12
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function update_rating_in_admin() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Filters the comment query to exclude empty comments.
	 * Triggered from the recipe card, so we already know there is
	 * a recipe on the page.
	 *
	 * @since 3.12
	 * @deprecated 1.0
	 *
	 * @param array $clauses The current comment query clauses.
	 *
	 * @return array
	 */
	public static function exclude_empty_comments( $clauses ) {
		_deprecated_function( __METHOD__, '1.0' );
		return $clauses;
	}
}

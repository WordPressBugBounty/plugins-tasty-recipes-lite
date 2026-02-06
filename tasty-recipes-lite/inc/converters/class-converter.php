<?php
/**
 * Base converter class.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Converters;

use Tasty_Recipes\Block_Editor;
use Tasty_Recipes\Objects\Recipe;
use Tasty_Recipes\Ratings;
use Tasty_Recipes\Shortcodes;
use Tasty_Recipes\Utils;

/**
 * Base converter class.
 */
abstract class Converter {

	/**
	 * Matching string for existing recipes.
	 *
	 * @var array|string
	 */
	protected static $match_string = null;

	/**
	 * Get the total number of posts with recipes in their content.
	 *
	 * @return int
	 */
	public static function get_count() {
		if ( is_null( static::$match_string ) ) {
			return 0;
		}
		return (int) self::get_select_query( 'COUNT(ID)', array( 'type' => 'get_var' ) );
	}

	/**
	 * Get post ids for posts with recipes in their content.
	 *
	 * @param int $per_page Number of posts to fetch. Defaults to 10.
	 *
	 * @return array
	 */
	public static function get_post_ids( $per_page = 10 ) {
		if ( is_null( static::$match_string ) ) {
			return array();
		}
		$post_ids = self::get_select_query(
			'ID',
			array(
				'type'  => 'get_col',
				'limit' => '0, ' . (int) $per_page,
			)
		);
		return array_map( 'intval', $post_ids );
	}

	/**
	 * Get a SELECT query for posts matching the $match_string.
	 *
	 * @param string $select_var Selection to perform.
	 * @param array  $query_details {
	 *   Details for the query.
	 *
	 *   @type string $type  Type of query to perform.
	 *   @type string $limit Limit for the query.
	 * }
	 *
	 * @return array|int|string
	 */
	protected static function get_select_query( $select_var, $query_details = array() ) {
		global $wpdb;

		$defaults      = array(
			'type'  => '',
			'limit' => '',
		);
		$query_details = wp_parse_args( $query_details, $defaults );
		$select_var    = esc_sql( $select_var );

		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT {$select_var} FROM {$wpdb->posts} WHERE post_type != %s AND post_status != %s",
			'revision',
			'trash'
		);

		// Add already escaped values to the query.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query .= ' AND ' . self::get_prepared_like_query() . Utils::esc_limit( $query_details['limit'] );

		// Validate query type to prevent misuse.
		$allowed_query_types = array( 'get_col', 'get_row', 'get_results', 'get_var' );
		if ( ! in_array( $query_details['type'], $allowed_query_types, true ) ) {
			// For reverse compatibility.
			_deprecated_argument( __METHOD__, '1.0', 'The $query_details[type] argument is required.' );
			return $query;
		}

		$query_type = $query_details['type'];
		return $wpdb->$query_type( $query );
	}

	/**
	 * Get the LIKE query for the match string.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected static function get_prepared_like_query() {
		global $wpdb;

		$match_strings = is_array( static::$match_string ) ? static::$match_string : array( static::$match_string );
		$like_query    = array();
		foreach ( $match_strings as $match_string ) {
			$like_query[] = $wpdb->prepare( 'post_content LIKE %s', '%' . $wpdb->esc_like( $match_string ) . '%' );
		}
		$like_query = implode( ' OR ', $like_query );
		if ( count( $match_strings ) > 1 ) {
			$like_query = ' ( ' . $like_query . ' ) ';
		}
		return $like_query;
	}

	/**
	 * Gets the existing content to convert, if it exists.
	 *
	 * @param string $content Content to search within.
	 *
	 * @return mixed.
	 */
	public static function get_existing_to_convert( $content ) {
		return static::get_existing_block_or_shortcode( $content );
	}

	/**
	 * Gets an existing block to convert, if one exists.
	 * Otherwise, it looks for a shortcode.
	 *
	 * @param string $content Content to search within.
	 * @param string $retval  Return 'match' or 'data'.
	 *
	 * @return mixed.
	 */
	public static function get_existing_block_or_shortcode( $content, $retval = 'match' ) {
		if ( isset( static::$block_name ) ) {
			if ( preg_match(
				'#<!-- wp:' . static::$block_name . '(?<attr>.*)-->(.*)<!-- /wp:' . static::$block_name . ' -->#Us',
				$content,
				$matches
			) ) {
				return 'match' === $retval ? $matches[0] : json_decode( trim( $matches['attr'] ), true );
			}
			if ( preg_match(
				'#<!-- wp:' . static::$block_name . '(?<attr>.*) /-->#Us',
				$content,
				$matches
			) ) {
				return 'match' === $retval ? $matches[0] : json_decode( trim( $matches['attr'] ), true );
			}
		}
		if ( isset( static::$shortcode_tag ) ) {
			// First, look inside all wp:shortcode instances.
			if ( preg_match_all(
				'#<!-- wp:shortcode.*-->(.*)<!-- /wp:shortcode -->#Us',
				$content,
				$matches
			) ) {
				foreach ( $matches[1] as $i => $match ) {
					$shortcode = Utils::get_existing_shortcode( $match, static::$shortcode_tag );
					if ( $shortcode ) {
						// Return the full match so it gets replaced entirely.
						if ( 'match' === $retval ) {
							return $matches[0][ $i ];
						}
						// Otherwise, parse the shortcode.
						$existing = substr( $shortcode, 1, -1 ); // Remove '[' and ']' from shortcode.
						return shortcode_parse_atts( $existing );
					}
				}
			}
			// Otherwise, look for the first shortcode instance.
			$shortcode = Utils::get_existing_shortcode( $content, static::$shortcode_tag );
			// Return the full match so it gets replaced entirely.
			if ( $shortcode ) {
				if ( 'match' === $retval ) {
					return $shortcode;
				}
				// Otherwise, parse the shortcode.
				$existing = substr( $shortcode, 1, -1 ); // Remove '[' and ']' from shortcode.
				return shortcode_parse_atts( $existing );
			}
		}

		return false;
	}

	/**
	 * Convert the recipe content within a given post.
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$content = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE ID=%d", $post_id ) );
		// Correct Windows-style line endings.
		$content  = str_replace( "\r\n", "\n", $content );
		$content  = str_replace( "\r", "\n", $content );
		$existing = static::get_existing_to_convert( $content );
		if ( ! $existing ) {
			return false;
		}
		$recipe = static::create_recipe_from_existing( $existing, $post_id );
		if ( ! $recipe ) {
			return false;
		}
		Ratings::update_recipe_rating( $recipe, $post_id );
		if ( 'shortcode' === $type ) {
			$shortcode = PHP_EOL . Shortcodes::get_shortcode_for_recipe( $recipe ) . PHP_EOL;
			$content   = str_replace( $existing, $shortcode, $content );
		} elseif ( 'block' === $type ) {
			$block   = PHP_EOL . Block_Editor::get_block_for_recipe( $recipe ) . PHP_EOL;
			$content = str_replace( $existing, $block, $content );
		}
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);
		return $recipe;
	}

	/**
	 * Convert recipe content to Tasty Recipes format.
	 *
	 * @param string $existing Existing content that may have recipe content.
	 * @param int    $post_id  ID for the post with the recipe.
	 *
	 * @return false|Recipe
	 */
	public static function create_recipe_from_existing( $existing, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable, SlevomatCodingStandard.Files.LineLength.LineTooLong
		return false;
	}

	/**
	 * Apply a filter to converted data before saving to the database.
	 *
	 * @param array  $converted_data Data to be converted.
	 * @param Recipe $recipe         Created recipe object.
	 *
	 * @return Recipe
	 */
	protected static function save_converted_data_to_recipe( $converted_data, $recipe ) {
		/**
		 * Filter converted data before it's applied to the recipe.
		 *
		 * @param array  $converted_data
		 * @param string $type
		 */
		$called_class   = get_called_class();
		$bits           = explode( '\\', $called_class );
		$type           = array_pop( $bits );
		$type           = strtolower( $type );
		$converted_data = apply_filters( 'tasty_recipes_convert_recipe', $converted_data, $type );
		foreach ( $converted_data as $key => $value ) {
			$setter = "set_{$key}";
			$recipe->$setter( $value );
		}
		return $recipe;
	}

	/**
	 * Transform lines into a list, and ! section headings into <h4>.
	 *
	 * Used by ZipList and Meal Planner Pro.
	 *
	 * @param string $content    Content to be transformed.
	 * @param string $list_style Either an ordered or unordered list.
	 *
	 * @return string
	 */
	protected static function process_lines_into_lists_and_headings( $content, $list_style ) {
		$list_opening = '<' . $list_style . '>';
		$list_closing = '</' . $list_style . '>';
		$is_open      = false;
		$bits         = explode( PHP_EOL, $content );
		$new_bits     = array();
		foreach ( $bits as $line ) {
			$pre  = '';
			$line = trim( $line );
			if ( 0 === stripos( $line, '!' ) ) {
				if ( $is_open ) {
					$pre     = $list_closing . PHP_EOL;
					$is_open = false;
				}
				$line       = substr( $line, 1 );
				$new_bits[] = $pre . '<h4>' . static::process_markdownish_into_html( $line ) . '</h4>';
			} elseif ( ! empty( $line ) ) {
				if ( ! $is_open ) {
					$pre     = $list_opening . PHP_EOL;
					$is_open = true;
				}
				$new_bits[] = $pre . '<li>' . static::process_markdownish_into_html( $line ) . '</li>';
			}
		}
		$content = implode( PHP_EOL, $new_bits );
		if ( $is_open ) {
			$content .= PHP_EOL . $list_closing;
		}
		return $content;
	}

	/**
	 * Process MPP, Yummly, and ZipList markdown-ish markup into HTML.
	 *
	 * @param string $item Content to be transformed.
	 *
	 * @return string
	 */
	protected static function process_markdownish_into_html( $item ) {
		$output   = $item;
		$link_ptr = '#\[(.*?)\| *(.*?)( (.*?))?\]#';
		preg_match_all( $link_ptr, $item, $matches );
		if ( ! empty( $matches[0] ) ) {
			$orig         = $matches[0];
			$substitution = preg_replace( $link_ptr, '<a href="$2"$3>$1</a>', str_replace( '"', '', $orig ) );
			$output       = str_replace( $orig, $substitution, $item );
		}

		// Must be an image.
		if ( '%http' === substr( $output, 0, 5 ) ) {
			$output = '<img src="' . esc_url( substr( $output, 1 ) ) . '">';
		}
		$output = preg_replace( '/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '$1<strong>$2</strong>$3', $output );
		$output = preg_replace( '/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '$1<em>$2</em>$3', $output );
		return $output;
	}

	/**
	 * Get the image id for a given media file.
	 *
	 * If the image doesn't exist in the media library, it's imported.
	 *
	 * @param string $file Path for the file.
	 *
	 * @return int
	 */
	protected static function get_image_id_from_file( $file ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$image_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' and guid=%s", $file ) );
		if ( ! $image_id ) {
			$image_id = static::media_sideload_image( $file );
		}
		return $image_id;
	}

	/**
	 * Sideload an image URL into WordPress.
	 *
	 * @param string $file Path for the file.
	 *
	 * @return int
	 */
	public static function media_sideload_image( $file ) {
		/**
		 * Fires before an image is imported into WordPress.
		 *
		 * Can be used to prevent images from being imported, or to apply
		 * a custom algorithm to matching images.
		 *
		 * @param null   $retval Return value.
		 * @param string $file   Image file to import.
		 */
		$retval = apply_filters( 'tasty_recipes_pre_import_image', null, $file );
		if ( null !== $retval ) {
			return $retval;
		}

		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		if ( ! $matches ) {
			do_action( 'tasty_recipes_imported_image', 0 );
			return 0;
		}

		$file_array             = array();
		$file_array['name']     = basename( $matches[0] );
		$file_array['tmp_name'] = download_url( $file );
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			do_action( 'tasty_recipes_imported_image', 0 );
			return 0;
		}

		$id = media_handle_sideload( $file_array, 0 );
		if ( is_wp_error( $id ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
			unlink( $file_array['tmp_name'] );
			do_action( 'tasty_recipes_imported_image', 0 );
			return 0;
		}
		do_action( 'tasty_recipes_imported_image', $id );
		return $id;
	}

	/**
	 * Format numerical times (eg. 00:10).
	 *
	 * @param string $value Time string to format.
	 *
	 * @return string
	 */
	public static function format_times( $value ) {
		$value     = preg_replace( '/(\d+):(\d+)/', '$1 hours $2 minutes', $value );
		$incorrect = array(
			'/00 hours /',
			'/ 00 minutes/',
			'/01 hours/',
			'/1 hours/',
			'/0(\d)/',
		);
		$correct   = array(
			'',
			'',
			'1 hour',
			'1 hour',
			'$1',
		);
		$value     = preg_replace( $incorrect, $correct, $value );
		return $value;
	}
}

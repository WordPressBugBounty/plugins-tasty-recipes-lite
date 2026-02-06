<?php
/**
 * Utility methods used across classes.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use WP_Post;

/**
 * Utility methods used across classes.
 */
class Utils {

	/**
	 * Get existing shortcode to convert.
	 *
	 * @param string $content       Content to parse for a shortcode.
	 * @param string $shortcode_tag Shortcode tag to look for.
	 *
	 * @return false|string
	 */
	public static function get_existing_shortcode( $content, $shortcode_tag ) {
		if ( false === stripos( $content, $shortcode_tag ) ) {
			return false;
		}
		$backup_tags = $GLOBALS['shortcode_tags'];
		remove_all_shortcodes();
		add_shortcode( $shortcode_tag, '__return_false' ); // @phpstan-ignore argument.type
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			$GLOBALS['shortcode_tags'] = $backup_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			return false;
		}

		$existing = false;
		foreach ( $matches as $shortcode ) {
			if ( $shortcode_tag === $shortcode[2] ) {
				$existing = $shortcode[0];
				break;
			}
		}
		$GLOBALS['shortcode_tags'] = $backup_tags; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return $existing;
	}

	/**
	 * Transforms a fraction amount into a proper number.
	 *
	 * @param string $amount Existing amount to process.
	 *
	 * @return float
	 */
	public static function process_amount_into_float( $amount ) {
		$vulgar_fractions = array(
			'¼' => '1/4',
			'½' => '1/2',
			'¾' => '3/4',
			'⅐' => '1/7',
			'⅑' => '1/9',
			'⅒' => '1/10',
			'⅓' => '1/3',
			'⅔' => '2/3',
			'⅕' => '1/5',
			'⅖' => '2/5',
			'⅗' => '3/5',
			'⅘' => '4/5',
			'⅙' => '1/6',
			'⅚' => '5/6',
			'⅛' => '1/8',
			'⅜' => '3/8',
			'⅝' => '5/8',
			'⅞' => '7/8',
		);
		// Transform '1½' into '1 ½' to avoid interpretation as '11/2'.
		$amount = preg_replace( '#^([\d+])(' . implode( '|', array_keys( $vulgar_fractions ) ) . ')#', '$1 $2', $amount );
		// Now transform vulgar fractions to their numeric equivalent.
		$amount = str_replace( array_keys( $vulgar_fractions ), array_values( $vulgar_fractions ), $amount );

		// Replace unicode ⁄ with standard forward slash.
		$amount = str_replace( '⁄', '/', $amount );

		// Handle English language 1-10.
		$english_amounts = array(
			'one half of an' => 0.5,
			'one half of a'  => 0.5,
			'half of an'     => 0.5,
			'half of a'      => 0.5,
			'half an'        => 0.5,
			'half a'         => 0.5,
			'one'            => 1,
			'two'            => 2,
			'three'          => 3,
			'four'           => 4,
			'five'           => 5,
			'six'            => 6,
			'seven'          => 7,
			'eight'          => 8,
			'nine'           => 9,
			'ten'            => 10,
		);
		$amount          = str_replace( array_keys( $english_amounts ), array_values( $english_amounts ), $amount );

		// This is an amount with fractions.
		if ( false !== stripos( $amount, '/' ) ) {
			$bits = explode( ' ', $amount );
			// Something like "1 1/2".
			// Otherwise something like "1/4".
			if ( count( $bits ) === 2 ) {
				$base = (int) array_shift( $bits );
			} elseif ( count( $bits ) === 1 ) {
				$base = 0;
			}
			if ( isset( $base ) ) {
				$frac_bits = explode( '/', array_shift( $bits ) );
				$amount    = $base + ( intval( $frac_bits[0] ) / intval( $frac_bits[1] ) );
			}
		}
		return $amount;
	}

	/**
	 * Makes a unit singular.
	 *
	 * @param string $unit The unit to make singular.
	 *
	 * @return string
	 */
	public static function make_singular( $unit ) {
		return preg_replace( '#s$#', '', $unit );
	}

	/**
	 * Gets the ID from a YouTube URL, if one exists.
	 *
	 * @param string $url URL to inspect.
	 *
	 * @return false|string
	 */
	public static function get_youtube_id( $url ) {
		$url = trim( $url );
		if ( empty( $url ) ) {
			return false;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$base_host = implode( '.', array_slice( explode( '.', $host ), -2, 2 ) );

		if ( 'youtube.com' === $base_host ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			// Something like https://www.youtube.com/embed/HZpnBPiCYnA?feature=oembed.
			if ( 0 === stripos( $path, '/embed/' ) ) {
				return trim( str_replace( '/embed/', '', $path ), '/' );
			}
			// Something like https://youtube.com/shorts/JnLsjVy3soI.
			if ( 0 === stripos( $path, '/shorts/' ) ) {
				return trim( str_replace( '/shorts/', '', $path ), '/' );
			}
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );
			return ! empty( $args['v'] ) ? $args['v'] : false;
		}
		if ( 'youtu.be' === $base_host ) {
			return trim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
		}
		return false;
	}

	/**
	 * Gets an attribute value from a given element.
	 *
	 * @param string $html Original HTML.
	 * @param string $el   Base HTML element.
	 * @param string $attr Attribute name.
	 *
	 * @return false|string
	 */
	public static function get_element_attribute( $html, $el, $attr ) {
		if ( false === stripos( $html, '<' . $el ) ) {
			return false;
		}
		if ( preg_match( '#<' . $el . '[^>]+' . $attr . '=[\'"]([^\'"]+)[\'"][^>]*>#', $html, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	/**
	 * Minify a CSS string with PHP.
	 *
	 * @see https://github.com/matthiasmullie/minify/blob/master/src/CSS.php
	 *
	 * @param string $content Existing CSS.
	 *
	 * @return string
	 */
	public static function minify_css( $content ) {
		/*
		 * Remove whitespace
		 */
		// remove leading & trailing whitespace.
		$content = preg_replace( '/^\s*/m', '', $content );
		$content = preg_replace( '/\s*$/m', '', $content );
		// replace newlines with a single space.
		$content = preg_replace( '/\s+/', ' ', $content );
		// remove whitespace around meta characters.
		// inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex.
		$content = preg_replace( '/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content );
		$content = preg_replace( '/([\[(:>\+])\s+/', '$1', $content );
		$content = preg_replace( '/\s+([\]\)>\+])/', '$1', $content );
		$content = preg_replace( '/\s+(:)(?![^\}]*\{)/', '$1', $content );
		// whitespace around + and - can only be stripped inside some pseudo-
		// classes, like `:nth-child(3+2n)`
		// not in things like `calc(3px + 2px)`, shorthands like `3px -2px`, or
		// selectors like `div.weird- p`.
		$pseudos = array( 'nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type' );
		$content = preg_replace( '/:(' . implode( '|', $pseudos ) . ')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*(.*?)\s*\)/', ':$1($2$3$4$5)', $content );
		// remove semicolon/whitespace followed by closing bracket.
		$content = str_replace( ';}', '}', $content );
		// Shorten colors.
		$content = preg_replace( '/(?<=[: ])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?:([0-9a-z])\\4)?(?=[; }])/i', '#$1$2$3$4', $content );
		// remove alpha channel if it's pointless...
		$content = preg_replace( '/(?<=[: ])#([0-9a-z]{6})ff?(?=[; }])/i', '#$1', $content );
		$content = preg_replace( '/(?<=[: ])#([0-9a-z]{3})f?(?=[; }])/i', '#$1', $content );
		return trim( $content );
	}

	/**
	 * Remove tabs and comments before echoing the javascript.
	 *
	 * @since 3.13
	 *
	 * @param string $scripts All the scripts to minify.
	 *
	 * @return string
	 */
	public static function minify_js( $scripts ) {
		if ( empty( $scripts ) ) {
			return '';
		}

		$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		if ( ! $debug ) {
			// Remove comments.
			$scripts = preg_replace( '/\/\*(.|\s)*?\*\//', '', $scripts );

			// Remove extra white space.
			$scripts = str_replace( array( "\t", "\n\n", "\r\n\r\n" ), '', $scripts );
		}
		return $scripts;
	}

	/**
	 * Check if it's the block or classic editor.
	 *
	 * @param WP_Post $current_post Current post object.
	 *
	 * @return bool
	 */
	public static function is_block_editor( $current_post = null ) {
		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return true;
		}

		$current_screen = get_current_screen();
		if ( $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}

		if ( is_null( $current_post ) ) {
			global $post;
			$current_post = $post;
		}

		return function_exists( 'has_blocks' ) && has_blocks( $current_post );
	}

	/**
	 * Sanitize $_GET parameter.
	 *
	 * @param string $input_name    The GET parameter name.
	 * @param string $default_value Default value if this key is not found in GET.
	 *
	 * @return string
	 */
	public static function sanitize_get_key( $input_name, $default_value = '' ) {
		return self::get_param( $input_name, 'sanitize_key', $default_value );
	}

	/**
	 * Returns true if the request is a REST API request.
	 *
	 * @since 3.8
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public static function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		return str_contains( self::server_param( 'REQUEST_URI' ), $rest_prefix );
	}

	/**
	 * Get any value from the $_SERVER.
	 *
	 * @since 3.8
	 * @deprecated 3.9
	 *
	 * @param string $key Server key to get its value.
	 *
	 * @return string
	 */
	public static function get_server_value( $key ) {
		_deprecated_function( __FUNCTION__ . '()', '3.9', 'Utils::server_param' );
		return self::server_param( $key );
	}

	/**
	 * Sanitize item value.
	 *
	 * @param mixed  $item              Item to be sanitized.
	 * @param string $sanitize_callback Sanitize callback function.
	 *
	 * @return mixed
	 */
	public static function sanitize_item( $item, $sanitize_callback = 'sanitize_text_field' ) {
		if ( empty( $item ) ) {
			return $item;
		}

		if ( empty( $sanitize_callback ) || ! function_exists( $sanitize_callback ) ) {
			return $item;
		}

		if ( ! is_array( $item ) ) {
			return call_user_func( $sanitize_callback, $item );
		}

		return array_map( __METHOD__, $item );
	}

	/**
	 * Check if a REQUEST value exists and sanitize it.
	 *
	 * @since 3.10
	 *
	 * @param string $key           The REQUEST key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function request_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		return isset( $_REQUEST[ $key ] ) ? self::sanitize_item( wp_unslash( $_REQUEST[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a GET value exists and sanitize it.
	 *
	 * @since 3.10
	 *
	 * @param string $key           The GET key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function get_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] ) ? self::sanitize_item( wp_unslash( $_GET[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a POST value exists and sanitize it.
	 *
	 * @since 3.10
	 *
	 * @param string $key           The POST key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function post_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		return isset( $_POST[ $key ] ) ? self::sanitize_item( wp_unslash( $_POST[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Check if a SERVER value exists and sanitize it.
	 *
	 * @since 3.10
	 *
	 * @param string $key           The GET key to check.
	 * @param string $sanitize      The type of sanitization to apply.
	 * @param mixed  $default_value The default value to return if the key is not set.
	 *
	 * @return mixed
	 */
	public static function server_param( $key, $sanitize = 'sanitize_text_field', $default_value = '' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return isset( $_SERVER[ $key ] ) ? self::sanitize_item( wp_unslash( $_SERVER[ $key ] ), $sanitize ) : $default_value;
	}

	/**
	 * Get path contents.
	 *
	 * @param string $path     File's path to get contents.
	 * @param string $use_path Use include path or not.
	 *
	 * @return false|string
	 */
	public static function get_contents( $path, $use_path = '' ) {
		if ( $use_path ) {
			$path = dirname( TASTY_RECIPES_LITE_FILE ) . '/' . $path;
		}

		if ( ! file_exists( $path ) ) {
			return false;
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( $path );
	}

	/**
	 * Filters text content and strips out disallowed HTML.
	 *
	 * @param string $content HTML Content.
	 * @param bool   $show    Echo sanitized input or not, default to false.
	 *
	 * @return string|void
	 */
	public static function kses( $content, $show = false ) {
		if ( empty( $content ) ) {
			return '';
		}
		$allowed_html = self::get_allowed_html();
		if ( ! $show ) {
			return wp_kses( $content, $allowed_html );
		}
		echo wp_kses( $content, $allowed_html );
	}

	/**
	 * Get allowed HTML to be used in kses.
	 *
	 * @return array
	 */
	public static function get_allowed_html() {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$add_html = array(
			'defs'           => array(),
			'desc'           => array(
				'id' => true,
			),
			'g'              => array(
				'fill' => true,
			),
			'path'           => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
			),
			'lineargradient' => array(
				'id' => true,
			),
			'stop'           => array(
				'stop-opacity' => true,
				'offset'       => true,
				'stop-color'   => true,
			),
			'svg'            => array(
				'class'           => true,
				'id'              => true,
				'xmlns'           => true,
				'viewbox'         => true,
				'width'           => true,
				'height'          => true,
				'style'           => true,
				'fill'            => true,
				'aria-label'      => true,
				'aria-hidden'     => true,
				'aria-labelledby' => true,
				'stroke'          => true,
			),
			'symbol'         => array(
				'id'      => true,
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
			),
			'use'            => array(
				'href'        => true,
				'xlink:href'  => true,
				'class'       => true,
				'aria-hidden' => true,
			),
		);

		// Allow display none on an svg sprite.
		add_filter(
			'safe_style_css',
			function ( $styles ) {
				$styles[] = 'display';
				return $styles;
			}
		);

		$allowed_html = array_replace_recursive( $add_html, $allowed_html );
		return (array) apply_filters( 'tasty_recipes_allowed_html', $allowed_html );
	}

	/**
	 * Get any value from the $_POST.
	 *
	 * @since 3.9
	 *
	 * @param string $key      POST key to get its value.
	 * @param string $sanitize Sanitize method.
	 *
	 * @return string
	 */
	public static function get_post_value( $key, $sanitize = 'sanitize_text_field' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return function_exists( $sanitize ) ? call_user_func( $sanitize, $value ) : $value;
	}

	/**
	 * Send a JSON response with a success message.
	 *
	 * @since 3.12
	 *
	 * @param string $message The message to send.
	 *
	 * @return void
	 */
	public static function send_json_error( $message ) {
		wp_send_json_error(
			array(
				'message' => esc_html( $message ),
			)
		);
	}

	/**
	 * Prepare the limit clause for a SQL query.
	 *
	 * @since 1.0
	 *
	 * @param string $limit Limit clause to prepare.
	 *
	 * @return string
	 */
	public static function esc_limit( $limit ) {
		if ( empty( $limit ) ) {
			return '';
		}

		$limit = trim( str_replace( 'limit ', '', strtolower( $limit ) ) );
		if ( is_numeric( $limit ) ) {
			return ' LIMIT ' . $limit;
		}

		$limit = explode( ',', trim( $limit ) );
		foreach ( $limit as $k => $l ) {
			if ( is_numeric( $l ) ) {
				$limit[ $k ] = $l;
			}
		}

		$limit = implode( ',', $limit );

		return ' LIMIT ' . $limit;
	}

	/**
	 * Check for certain page in Formidable settings
	 *
	 * @since 1.0
	 *
	 * @param string $page The name of the page to check.
	 *
	 * @return bool
	 */
	public static function is_admin_page( $page = 'tasty' ) {
		global $pagenow;
		$get_page = self::sanitize_get_key( 'page' );

		if ( $pagenow ) {
			// Allow this to be true during ajax load i.e. ajax form builder loading.
			$is_page = ( $pagenow === 'admin.php' || $pagenow === 'admin-ajax.php' ) && $get_page === $page;
			if ( $is_page ) {
				return true;
			}
		}

		return is_admin() && $get_page === $page;
	}
}

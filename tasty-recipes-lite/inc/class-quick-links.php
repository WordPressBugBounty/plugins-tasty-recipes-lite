<?php
/**
 * Holds the Quick Links class.
 *
 * @since 1.0
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use WP_Post;

/**
 * Manages the Quick Links and the SVG sprite.
 * 
 * @since 1.0
 */
class Quick_Links {

	/**
	 * Quick Links shortcode name.
	 *
	 * @var string
	 * 
	 * @since 1.0
	 */
	private static $shortcode_name = 'tasty-recipes-quick-links';

	/**
	 * Whether or not we're currently doing the excerpt.
	 *
	 * @var bool
	 * 
	 * @since 1.0
	 */
	private static $doing_excerpt = false;

	/**
	 * Load hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_shortcode( self::$shortcode_name, array( __CLASS__, 'render_quick_links_shortcode' ) );

		add_action( 'wp_head', array( __CLASS__, 'doing_header' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'ending_header' ), 1000 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'filter_get_the_excerpt_early' ), 1 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'filter_get_the_excerpt_late' ), 1000 );
		add_filter( 'the_content', array( __CLASS__, 'filter_the_content_late' ), 100 );
	}

	/**
	 * Prevent the quicklinks from being loaded within the head tags.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function doing_header() {
		self::$doing_excerpt = true;
	}

	/**
	 * Indicate the end of the WP head.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function ending_header() {
		self::$doing_excerpt = false;
	}

	/**
	 * Keeps track of when 'get_the_excerpt' is running.
	 *
	 * @since 1.0
	 *
	 * @param string $excerpt Existing excerpt.
	 *
	 * @return string
	 */
	public static function filter_get_the_excerpt_early( $excerpt ) {
		self::doing_header();
		return $excerpt;
	}

	/**
	 * Keeps track of when 'get_the_excerpt' is running.
	 *
	 * @since 1.0
	 *
	 * @param string $excerpt Existing excerpt.
	 *
	 * @return string
	 */
	public static function filter_get_the_excerpt_late( $excerpt ) {
		self::ending_header();
		return $excerpt;
	}

	/**
	 * Add "Jump to Recipe" and "Print Recipe" buttons when the post has a recipe.
	 *
	 * @since 1.0
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string
	 */
	public static function filter_the_content_late( $content ) {

		if ( self::$doing_excerpt || ! is_singular() || is_front_page() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post ) {
			return $content;
		}

		$quick_links = self::prepended_quick_links( $post, $content );

		if ( empty( $quick_links ) ) {
			return $content;
		}

		$html    = do_shortcode( '[' . self::$shortcode_name . ' links="' . $quick_links . '" prepended=1]' );
		$content = $html . PHP_EOL . PHP_EOL . $content;
		if ( strpos( $html, '#wpt-star-full' ) ) {
			self::move_sprite_up( $content );
		}

		return $content;
	}

	/**
	 * Move SVG definitions to the top of the content to avoid
	 * rendering issues and CLS.
	 *
	 * @since 1.0
	 *
	 * @param string $content Post content.
	 *
	 * @return void
	 */
	private static function move_sprite_up( &$content ) {
		if ( ! strpos( $content, 'wpt-star-full' ) ) {
			return;
		}

		// Find the <svg> block containing <symbol id="wpt-star-full">.
		$pattern = '/<svg[^>]*>\s*<defs>(?:(?!<\/svg>).)*?<symbol[^>]*\bid="wpt-star-full"[^>]*>.*?<\/symbol>.*?<\/defs>\s*<\/svg>/is';

		// Check if the pattern matches and move the <svg> block to the top.
		if ( preg_match( $pattern, $content, $matches ) ) {
			$svg_block = $matches[0];

			// Remove the matched <svg> block from its current position.
			$content = preg_replace( $pattern, '', $content, 1 );
			$content = $svg_block . "\n" . $content;
		}
	}

	/**
	 * Which quick links will be prepended to the post content.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $post    Post object.
	 * @param string  $content Post content.
	 *
	 * @return string
	 */
	private static function prepended_quick_links( $post, $content ) {
		$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( $post->ID );

		// Check if the content includes a single or double quote.
		$shown_quick_links = preg_match( '/["\']' . self::$shortcode_name . '/', $content ) !== 0;

		switch ( true ) {
			case empty( $recipe_ids ):
			case $shown_quick_links:
				$quick_links = array();
				break;
			default:
				$quick_links = Settings::handle_quick_links_backward_compatibility( get_option( Tasty_Recipes::QUICK_LINKS_OPTION, array( 'jump', 'print' ) ) );
				break;
		}

		/**
		 * Filter to allow for more granular control over whether 'Jump to'
		 * should appear.
		 *
		 * @since 1.0
		 *
		 * @param array $quick_links The links to prepend.
		 * @param int   $post_id     ID for the recipe's post.
		 * @param array $recipe_ids  Recipe IDs within the post.
		 */
		$quick_links = apply_filters( 'tasty_recipes_should_prepend_jump_to', $quick_links, $post->ID, $recipe_ids );

		if ( empty( $quick_links ) ) {
			return '';
		}

		if ( ! Ratings::is_enabled() ) {
			$jump_key = array_search( 'rating', $quick_links, true );
			if ( false !== $jump_key ) {
				unset( $quick_links[ $jump_key ] );
			}
		}

		return implode( ',', (array) $quick_links );
	}

	/**
	 * Renders the Quick Links shortcode.
	 *
	 * @since 1.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function render_quick_links_shortcode( $atts ) {
		$defaults = array(
			'links' => 'jump,print',
		);
		if ( ! empty( $atts['links'] ) ) {
			$atts['links'] = implode( '|', Settings::handle_quick_links_backward_compatibility( $atts['links'] ) );
		}
		$atts = array_merge( $defaults, (array) $atts );

		$should_prepend_jump_to = is_array( $atts['links'] ) ? $atts['links'] : explode( ',', $atts['links'] );
		$should_prepend_jump_to = array_map( 'trim', $should_prepend_jump_to );

		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( $post->ID );
		if ( empty( $recipe_ids ) ) {
			return '';
		}
		$recipe_id = array_shift( $recipe_ids );
		$open_new  = '';

		/**
		 * Filter to control whether the print link opens in a new window.
		 *
		 * @since 1.0
		 *
		 * @param bool
		 */
		if ( apply_filters( 'tasty_recipes_print_link_open_new', false ) ) {
			$open_new = ' target="_blank"';
		}

		$btn_class = self::quick_links_classes( '' );

		$links = array();
		if ( in_array( 'jump', $should_prepend_jump_to, true ) ) {
			$links['jump'] = '<a class="tasty-recipes-jump-link tasty-recipes-scrollto' . esc_attr( $btn_class ) . '" ' .
				'href="#tasty-recipes-' . esc_attr( $recipe_id ) . '-jump-target">' .
				self::get_label_value( 'jump', $recipe_id ) .
				'</a>';
		}
		if ( in_array( 'print', $should_prepend_jump_to, true ) ) {
			$links['print'] = '<a class="tasty-recipes-print-link' . esc_attr( $btn_class ) . '" ' .
				'href="' . esc_url( tasty_recipes_get_print_url( $post->ID, $recipe_id ) ) . '"' . $open_new . ' target="_blank">' .
				self::get_label_value( 'print', $recipe_id ) .
				'</a>';
		}

		/**
		 * Filter to modify the links used in Quick Links.
		 *
		 * @since 1.0
		 *
		 * @param array $links Existing links.
		 */
		$links = apply_filters(
			'tasty_recipes_quick_links',
			$links,
			$post,
			$recipe_id,
			$should_prepend_jump_to
		);

		self::maybe_remove_smooth_scroll( $links );

		$html  = '<div class="tasty-recipes-quick-links">';
		$html .= implode( '<span>&middot;</span>', $links );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Remove the smooth scroll class if the jump link is not present.
	 *
	 * @since 1.0
	 *
	 * @param array $links Links to check.
	 *
	 * @return void
	 */
	private static function maybe_remove_smooth_scroll( array &$links ) {
		/**
		 * This filter allows disabling the smooth scrolling for quick links.
		 *
		 * @since 3.14
		 */
		$use_smooth = apply_filters( 'tasty_recipes_smooth_scroll', true );
		if ( $use_smooth ) {
			return;
		}

		foreach ( $links as $key => $link ) {
			if ( str_contains( $link, 'tasty-recipes-scrollto' ) ) {
				$links[ $key ] = str_replace( 'tasty-recipes-scrollto', '', $link );
			}
		}
	}

	/**
	 * Get the classes for the quick links.
	 *
	 * @since 1.0
	 *
	 * @param string $classes Existing classes.
	 *
	 * @return string
	 */
	public static function quick_links_classes( $classes ) {
		return apply_filters( 'tasty_recipes_quick_links_class', $classes );
	}

	/**
	 * Get the label for the jump to recipe link.
	 *
	 * @since 1.2.2
	 *
	 * @param string $type       The type of link.
	 * @param int    $recipe_id  The recipe ID.
	 *
	 * @return string
	 */
	public static function get_label_value( $type, $recipe_id = null ) {
		$labels = array(
			'jump'   => esc_html__( 'Jump to Recipe', 'tasty-recipes-lite' ),
			'print'  => esc_html__( 'Print Recipe', 'tasty-recipes-lite' ),
			'rating' => esc_html__( 'Leave a Review', 'tasty-recipes-lite' ),
		);

		if ( ! isset( $labels[ $type ] ) ) {
			return '';
		}

		return apply_filters( 'tasty_recipes_quick_links_label', $labels[ $type ], $type, $recipe_id );
	}
}

<?php
/**
 * Same-origin editor preview for recipe video embeds.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

/**
 * Hosts YouTube/Vimeo iframes on a real site URL in the block editor.
 *
 * @since 1.2.9
 */
class Editor_Embed_Preview {

	/**
	 * Query var for the editor embed preview page.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'tasty_recipes_embed_preview';

	/**
	 * Register hooks.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'send_headers', array( __CLASS__, 'action_send_headers' ) );
		add_action( 'template_redirect', array( __CLASS__, 'action_template_redirect' ) );
	}

	/**
	 * Drop X-Frame-Options so the blob editor canvas can frame the preview.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function action_send_headers() {
		if ( empty( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		header_remove( 'X-Frame-Options' );
	}

	/**
	 * Render a same-origin page that hosts the provider iframe.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function action_template_redirect() {
		if ( empty( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::QUERY_VAR ) || ! current_user_can( 'edit_posts' ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}

		$src = esc_url_raw( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
		if ( ! self::is_allowed_src( $src ) ) {
			wp_die( '', '', array( 'response' => 400 ) );
		}

		$title = ! empty( $_GET['title'] ) ? sanitize_text_field( wp_unslash( $_GET['title'] ) ) : '';
		if ( '' === $title ) {
			$title = __( 'Recipe video preview', 'tasty-recipes-lite' );
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Referrer-Policy: origin' );
		header_remove( 'X-Frame-Options' );

		echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
		echo '<meta name="referrer" content="origin">';
		echo '<style>html,body{margin:0;height:100%;overflow:hidden}';
		echo 'iframe{position:absolute;inset:0;width:100%;height:100%;border:0}</style></head><body>';
		$allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		printf(
			'<iframe src="%1$s" title="%2$s" allow="%3$s" referrerpolicy="origin" allowfullscreen></iframe>',
			esc_url( $src ),
			esc_attr( $title ),
			esc_attr( $allow )
		);
		echo '</body></html>';
		exit;
	}

	/**
	 * Wrap a cached oEmbed iframe in a same-origin editor preview iframe.
	 *
	 * @since 1.2.9
	 *
	 * @param object $embed_data Cached oEmbed response.
	 *
	 * @return string
	 */
	public static function wrap_oembed( $embed_data ) {
		if ( empty( $embed_data->html )
			|| ! preg_match( '#src=["\']([^"\']+)["\']#', $embed_data->html, $matches ) ) {
			return $embed_data->html;
		}

		$src = $matches[1];
		if ( ! self::is_allowed_src( $src ) ) {
			return $embed_data->html;
		}

		$title = ! empty( $embed_data->title ) ? $embed_data->title : __( 'Recipe video preview', 'tasty-recipes-lite' );

		$preview_url = add_query_arg(
			array(
				self::QUERY_VAR => $src,
				'_wpnonce'      => wp_create_nonce( self::QUERY_VAR ),
				'title'         => $title,
			),
			home_url( '/' )
		);

		$iframe  = '<iframe class="fitvidsignore tasty-recipe-editor-embed-preview"';
		$iframe .= ' src="' . esc_url( $preview_url ) . '"';
		$iframe .= ' title="' . esc_attr( $title ) . '" allowfullscreen></iframe>';

		return '<div class="tasty-recipe-responsive-iframe-container" style="aspect-ratio: 16 / 9;">' . $iframe . '</div>';
	}

	/**
	 * Whether a provider embed URL may be loaded in the editor preview page.
	 *
	 * @since 1.2.9
	 *
	 * @param string $src Embed iframe src.
	 *
	 * @return bool
	 */
	private static function is_allowed_src( $src ) {
		$host   = wp_parse_url( $src, PHP_URL_HOST );
		$scheme = wp_parse_url( $src, PHP_URL_SCHEME );
		if ( empty( $host ) || ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		$allowed_hosts = array(
			'www.youtube.com',
			'youtube.com',
			'www.youtube-nocookie.com',
			'youtube-nocookie.com',
			'player.vimeo.com',
			'embed.mediavine.com',
		);

		return in_array( $host, $allowed_hosts, true );
	}
}

<?php
/**
 * AI Scraper Prevention.
 *
 * Blocks AI training/scraping bots via robots.txt rules and injects
 * noai/noimageai meta tags on posts that contain recipes.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;

/**
 * AI Scraper Prevention.
 *
 * @since 1.2.5
 */
class AI_Scraper_Prevention {

	/**
	 * Maybe load hooks if the feature is enabled.
	 *
	 * @since 1.2.5
	 *
	 * @return void
	 */
	public static function maybe_load_hooks() {
		if ( get_option( Tasty_Recipes::AI_SCRAPER_PREVENTION_OPTION ) !== '1' ) {
			return;
		}

		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.robots_txt
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 100000, 2 );

		add_action( 'template_redirect', array( __CLASS__, 'maybe_block_ai_bot' ) );

		self::register_meta_tag_hooks();
	}

	/**
	 * Return a 403 response when a known AI bot requests a recipe page.
	 *
	 * @since 1.2.5
	 *
	 * @return void
	 */
	public static function maybe_block_ai_bot() {
		if ( ! self::current_post_has_recipe() ) {
			return;
		}

		if ( ! self::is_ai_bot_request() ) {
			return;
		}

		nocache_headers();
		wp_die( '', '', array( 'response' => 403 ) );
	}

	/**
	 * Whether the current request's User-Agent matches a blocked AI bot.
	 *
	 * @since 1.2.5
	 *
	 * @return bool
	 */
	private static function is_ai_bot_request() {
		// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$user_agent = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

		if ( '' === $user_agent ) {
			return false;
		}

		foreach ( self::get_blocked_bots() as $bot ) {
			if ( false !== stripos( $user_agent, $bot ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register the noai/noimageai meta tag hooks for the active SEO plugin,
	 * or fall back to a standalone wp_head output.
	 *
	 * @since 1.2.5
	 *
	 * @return void
	 */
	private static function register_meta_tag_hooks() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_filter( 'wpseo_robots_array', array( __CLASS__, 'filter_listed_robots' ) );
			return;
		}

		if ( class_exists( 'RankMath' ) ) {
			add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'filter_keyed_robots' ) );
			return;
		}

		if ( function_exists( 'aioseo' ) ) {
			add_filter( 'aioseo_robots_meta', array( __CLASS__, 'filter_keyed_robots' ) );
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'action_wp_head' ), 7 );
	}

	/**
	 * Append AI bot disallow rules to robots.txt output.
	 *
	 * Runs at priority 100000 (after all known SEO plugins) so that
	 * parse_existing_user_agents() naturally deduplicates against any
	 * bots already added by Yoast Premium, AIOSEO, Rank Math, etc.
	 *
	 * @since 1.2.5
	 *
	 * @param string $output    Robots.txt content.
	 * @param bool   $is_public Whether the site is public.
	 *
	 * @return string
	 */
	public static function filter_robots_txt( $output, $is_public ) {
		if ( ! $is_public ) {
			return $output;
		}

		$existing_agents = self::parse_existing_user_agents( $output );
		$bots            = self::get_blocked_bots();
		$rules           = '';

		foreach ( $bots as $bot ) {
			if ( in_array( strtolower( $bot ), $existing_agents, true ) ) {
				continue;
			}

			$rules .= "\nUser-agent: " . $bot . "\nDisallow: /\n";
		}

		if ( ! in_array( '*', $existing_agents, true ) ) {
			$rules .= "\nUser-agent: *\nAllow: /\n";
		}

		if ( '' === $rules ) {
			return $output;
		}

		$trimmed   = rtrim( $output );
		$separator = "\n\n";

		return $trimmed . $separator . "# START TASTY RECIPES BLOCK\n# ---------------------------" . $rules . "# ---------------------------\n# END TASTY RECIPES BLOCK\n";
	}

	/**
	 * Get the list of AI bot user-agents to block.
	 *
	 * @since 1.2.5
	 *
	 * @return string[] Array of user-agent strings to block.
	 */
	public static function get_blocked_bots() {
		$bots = array(
			'ClaudeBot',
			'claudebot',
			'GPTBot',
			'Google-Extended',
			'PerplexityBot',
			'Meta-ExternalAgent',
			'Applebot-Extended',
			'CCBot',
			'cohere-ai',
			'Bytespider',
			'Amazonbot',
			'ai2bot',
			'Diffbot',
			'Omgilibot',
		);

		/**
		 * Filters the list of AI training/scraping bot user-agents to block.
		 *
		 * @since 1.2.5
		 *
		 * @param string[] $bots Array of user-agent strings to block.
		 */
		$bots = apply_filters( 'tasty_recipes_ai_scraper_bots', $bots );

		return self::sanitize_token_list( $bots );
	}

	/**
	 * Parse existing User-agent values from a robots.txt string.
	 *
	 * @since 1.2.5
	 *
	 * @param string $output Robots.txt content.
	 *
	 * @return string[] Lowercased user-agent values.
	 */
	private static function parse_existing_user_agents( $output ) {
		$agents = array();

		if ( preg_match_all( '/^User-agent:\s*(.+)$/mi', $output, $matches ) ) {
			foreach ( $matches[1] as $agent ) {
				$agents[] = strtolower( trim( $agent ) );
			}
		}

		return array_unique( $agents );
	}

	/**
	 * Add AI directives to a list-based robots array (Yoast SEO).
	 *
	 * @since 1.2.5
	 *
	 * @param array $robots Existing robots directive tokens.
	 *
	 * @return array
	 */
	public static function filter_listed_robots( $robots ) {
		if ( ! self::current_post_has_recipe() ) {
			return $robots;
		}

		foreach ( self::get_ai_directives() as $directive ) {
			if ( ! in_array( $directive, $robots, true ) ) {
				$robots[] = $directive;
			}
		}

		return $robots;
	}

	/**
	 * Add AI directives to a keyed robots array (Rank Math, AIOSEO).
	 *
	 * @since 1.2.5
	 *
	 * @param array $robots Existing robots directives, keyed by token.
	 *
	 * @return array
	 */
	public static function filter_keyed_robots( $robots ) {
		if ( ! self::current_post_has_recipe() ) {
			return $robots;
		}

		foreach ( self::get_ai_directives() as $directive ) {
			if ( ! isset( $robots[ $directive ] ) ) {
				$robots[ $directive ] = $directive;
			}
		}

		return $robots;
	}

	/**
	 * Output noai/noimageai meta tag on recipe posts (standalone, no SEO plugin).
	 *
	 * @since 1.2.5
	 *
	 * @return void
	 */
	public static function action_wp_head() {
		if ( ! self::current_post_has_recipe() ) {
			return;
		}

		echo '<meta name="robots" content="' . esc_attr( implode( ', ', self::get_ai_directives() ) ) . '">' . PHP_EOL;
	}

	/**
	 * Get the robots meta directives that opt out of AI training and image scraping.
	 *
	 * @since 1.2.5
	 *
	 * @return string[] Array of robots meta directive tokens.
	 */
	public static function get_ai_directives() {
		/**
		 * Filters the robots meta directives used to opt out of AI training and image scraping.
		 *
		 * @since 1.2.5
		 *
		 * @param string[] $directives Array of robots meta directive tokens.
		 */
		$directives = apply_filters( 'tasty_recipes_ai_directives', array( 'noai', 'noimageai' ) );

		return self::sanitize_token_list( $directives );
	}

	/**
	 * Whether the current singular request is a post that contains a recipe.
	 *
	 * @since 1.2.5
	 *
	 * @return bool
	 */
	public static function current_post_has_recipe() {
		if ( ! is_singular() ) {
			return false;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return false;
		}

		return Tasty_Recipes::has_recipe( $post_id );
	}

	/**
	 * Coerce a filter return value into a clean, re-indexed list of trimmed strings.
	 *
	 * @since 1.2.5
	 *
	 * @param mixed $values Raw value returned by a filter.
	 *
	 * @return string[]
	 */
	private static function sanitize_token_list( $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$tokens = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			$value = trim( $value );

			if ( '' === $value ) {
				continue;
			}

			$tokens[] = $value;
		}

		return array_values( array_unique( $tokens ) );
	}
}

<?php
/**
 * Collects usage snapshots and loads PostHog on recipe-related admin screens.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

/**
 * Collects usage snapshots and loads PostHog on recipe-related admin screens.
 *
 * @since 1.2.9
 */
class Telemetry {

	/**
	 * Cron hook name for the daily usage snapshot.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const CRON_HOOK = 'tasty_recipes_telemetry_snapshot';

	/**
	 * Max parent posts to inspect for editor engagement.
	 *
	 * @since 1.2.9
	 *
	 * @var int
	 */
	const EDITOR_ENGAGEMENT_PARENT_LIMIT = 200;

	/**
	 * PostHog project API key.
	 *
	 * Public project key from the PostHog snippet, not a secret.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	// @mago-expect lint:no-literal-password
	const POSTHOG_API_KEY = 'phc_B22bzZor2zBV3yExxE9BAPL5b6wPkz3y3ZB2mrWSS9g9';

	/**
	 * PostHog API host.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const POSTHOG_API_HOST = 'https://us.i.posthog.com';

	/**
	 * PostHog defaults snapshot date.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const POSTHOG_DEFAULTS = '2026-05-30';

	/**
	 * Script handle for the PostHog loader.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const POSTHOG_SCRIPT_HANDLE = 'tasty-recipes-posthog';

	/**
	 * Script handle for the PostHog init helper.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const POSTHOG_INIT_SCRIPT_HANDLE = 'tasty-recipes-posthog-init';

	/**
	 * Script handle for the block editor PostHog watcher.
	 *
	 * @since 1.2.9
	 *
	 * @var string
	 */
	const POSTHOG_BLOCK_EDITOR_SCRIPT_HANDLE = 'tasty-recipes-posthog-block-editor';

	/**
	 * Register telemetry hooks.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'send_usage_snapshot' ) );
		add_action( 'update_option_' . Options::ONBOARDING_USAGE_DATA, array( __CLASS__, 'handle_consent_option_change' ), 10, 2 );
		add_action( 'add_option_' . Options::ONBOARDING_USAGE_DATA, array( __CLASS__, 'handle_consent_option_add' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_posthog' ) );
	}

	/**
	 * Whether usage tracking consent is effectively granted.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	public static function has_usage_consent() {
		$stored = get_option( Options::ONBOARDING_USAGE_DATA, null );

		if ( 'yes' === $stored ) {
			return true;
		}

		if ( 'no' === $stored ) {
			return false;
		}

		/**
		 * Filters the default usage consent when the option is unset.
		 *
		 * Lite defaults to opt-in (false). Extensions may return true.
		 *
		 * @since 1.2.9
		 *
		 * @param bool $default Default consent when unset.
		 */
		return (bool) apply_filters( 'tasty_recipes_usage_consent_default', false );
	}

	/**
	 * Whether a usage snapshot may be sent for this site/request.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	public static function can_send() {
		return self::has_usage_consent() && ! self::is_excluded_environment();
	}

	/**
	 * Whether the PostHog snippet may load in wp-admin.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	public static function should_load_posthog() {
		$should_load = self::has_usage_consent() && ! self::is_cypress_request();

		/**
		 * Filters whether PostHog may load on recipe-related admin screens.
		 *
		 * @since 1.2.9
		 *
		 * @param bool $should_load Whether PostHog should load.
		 */
		return (bool) apply_filters( 'tasty_recipes_load_posthog', $should_load );
	}

	/**
	 * Enqueue PostHog on recipe admin screens, or in the block editor when a Tasty block is present.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function maybe_enqueue_posthog() {
		if ( ! self::should_load_posthog() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( Settings::is_recipes_admin() ) {
			self::enqueue_posthog();
			return;
		}

		if ( $screen->is_block_editor() ) {
			self::enqueue_posthog_for_block_editor();
		}
	}

	/**
	 * Enqueue PostHog immediately, including the init call.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function enqueue_posthog() {
		self::enqueue_posthog_loader( true );
	}

	/**
	 * Enqueue PostHog for the block editor, initializing only when a Tasty block is present.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function enqueue_posthog_for_block_editor() {
		if ( self::editor_post_has_tasty_block() ) {
			self::enqueue_posthog();
			return;
		}

		self::enqueue_posthog_loader();

		wp_enqueue_script(
			self::POSTHOG_BLOCK_EDITOR_SCRIPT_HANDLE,
			plugins_url( 'assets/js/posthog/block-editor.js', TASTY_RECIPES_LITE_FILE ),
			array( self::POSTHOG_INIT_SCRIPT_HANDLE, 'wp-data' ),
			TASTY_RECIPES_LITE_VERSION,
			true
		);
	}

	/**
	 * Register the PostHog loader and init helper without capturing yet.
	 *
	 * @since 1.2.9
	 *
	 * @param bool $auto_init Whether to initialize PostHog immediately on load.
	 *
	 * @return void
	 */
	private static function enqueue_posthog_loader( $auto_init = false ) {
		if ( wp_script_is( self::POSTHOG_INIT_SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		wp_enqueue_script(
			self::POSTHOG_SCRIPT_HANDLE,
			plugins_url( 'assets/js/posthog/snippet.js', TASTY_RECIPES_LITE_FILE ),
			array(),
			TASTY_RECIPES_LITE_VERSION,
			false
		);

		wp_enqueue_script(
			self::POSTHOG_INIT_SCRIPT_HANDLE,
			plugins_url( 'assets/js/posthog/init.js', TASTY_RECIPES_LITE_FILE ),
			array( self::POSTHOG_SCRIPT_HANDLE ),
			TASTY_RECIPES_LITE_VERSION,
			false
		);

		$config = wp_json_encode(
			array(
				'apiKey'   => self::POSTHOG_API_KEY,
				'apiHost'  => self::POSTHOG_API_HOST,
				'defaults' => self::POSTHOG_DEFAULTS,
				'siteId'   => self::get_site_id(),
				'autoInit' => (bool) $auto_init,
			)
		);

		if ( ! is_string( $config ) ) {
			return;
		}

		wp_add_inline_script( self::POSTHOG_INIT_SCRIPT_HANDLE, 'window.tastyRecipesPosthog = ' . $config . ';', 'before' );
	}

	/**
	 * Whether the post currently being edited already contains a Tasty Recipes block.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	private static function editor_post_has_tasty_block() {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			$post_id = Utils::get_param( 'post', 'intval', 0 );
			$post    = $post_id ? get_post( $post_id ) : null;
		}

		if ( ! $post instanceof \WP_Post || ! is_string( $post->post_content ) ) {
			return false;
		}

		return false !== strpos( $post->post_content, '<!-- wp:wp-tasty/' );
	}

	/**
	 * Whether the current environment should never send telemetry.
	 *
	 * Blocks Cypress sessions and local/development sites.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	public static function is_excluded_environment() {
		return self::is_cypress_request() || self::is_local_site();
	}

	/**
	 * Whether the current request appears to come from Cypress.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	private static function is_cypress_request() {
		if ( defined( 'CYPRESS' ) && CYPRESS ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__ -- Cypress UA check; not used for page cache variance.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';

		return is_string( $user_agent ) && false !== stripos( $user_agent, 'Cypress' );
	}

	/**
	 * Whether the site looks like a local or development install.
	 *
	 * @since 1.2.9
	 *
	 * @return bool
	 */
	private static function is_local_site() {
		if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			return true;
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		$host = strtolower( $host );

		if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		$local_suffixes = array(
			'.local',
			'.test',
			'.localhost',
			'.invalid',
			'.example',
			'.lndo.site',
			'.docksal',
			'.ddev.site',
		);

		foreach ( $local_suffixes as $suffix ) {
			$suffix_length = strlen( $suffix );
			if ( strlen( $host ) >= $suffix_length && substr( $host, -$suffix_length ) === $suffix ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Schedule or clear the daily snapshot cron based on consent.
	 *
	 * @since 1.2.9
	 *
	 * @return void
	 */
	public static function maybe_schedule_cron() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( self::can_send() ) {
			if ( ! $scheduled ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
			}
			return;
		}

		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}
	}

	/**
	 * Handle consent option updates.
	 *
	 * @since 1.2.9
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 *
	 * @return void
	 */
	public static function handle_consent_option_change( $old_value, $value ) {
		self::maybe_schedule_cron();

		if ( 'yes' === $value && 'yes' !== $old_value ) {
			self::send_usage_snapshot();
		}
	}

	/**
	 * Handle first-time consent option creation.
	 *
	 * @since 1.2.9
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 *
	 * @return void
	 */
	public static function handle_consent_option_add( $option, $value ) {
		unset( $option );
		self::maybe_schedule_cron();

		if ( 'yes' === $value ) {
			self::send_usage_snapshot();
		}
	}

	/**
	 * Build and send a usage snapshot.
	 *
	 * @since 1.2.9
	 *
	 * @return bool True when the request received a 2xx response.
	 */
	public static function send_usage_snapshot() {
		if ( ! self::can_send() ) {
			return false;
		}

		$payload = self::build_payload();

		$response = wp_remote_post(
			self::get_capture_url(),
			array(
				'timeout'  => 5,
				'blocking' => true,
				'headers'  => array(
					'Content-Type' => 'application/json',
				),
				'body'     => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$success = $code >= 200 && $code < 300;

		if ( $success ) {
			/**
			 * Fires after a usage snapshot is sent successfully.
			 *
			 * @since 1.2.9
			 *
			 * @param array $payload Payload that was sent.
			 */
			do_action( 'tasty_recipes_telemetry_snapshot_sent', $payload );
		}

		return $success;
	}

	/**
	 * Build the usage snapshot payload.
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	public static function build_payload() {
		$site_id = self::get_site_id();

		$properties = array(
			'$process_person_profile' => false,
			'$groups'                 => array(
				'site' => $site_id,
			),
			'plugin_version'          => defined( 'TASTY_RECIPES_LITE_VERSION' ) ? TASTY_RECIPES_LITE_VERSION : '',
			'product'                 => 'lite',
			'wp_version'              => self::get_wp_version(),
			'php_version'             => PHP_VERSION,
			'active_theme'            => self::get_active_theme(),
			'template'                => (string) get_option( Options::TEMPLATE, 'default' ),
			'template_variation'      => (int) get_option( Options::TEMPLATE_VARIATION, 1 ),
			'ratings_enabled'         => Ratings::is_enabled( 'any' ),
			'recipe_count'            => self::get_recipe_count(),
			'editor_engagement'       => self::get_editor_engagement(),
			'features'                => self::get_features(),
		);

		/**
		 * Filters telemetry properties before send.
		 *
		 * @since 1.2.9
		 *
		 * @param array $properties Snapshot properties.
		 */
		$properties = apply_filters( 'tasty_recipes_telemetry_properties', $properties );

		return array(
			'api_key'     => self::POSTHOG_API_KEY,
			'event'       => 'tasty_recipes_usage_snapshot',
			'distinct_id' => $site_id,
			'properties'  => $properties,
		);
	}

	/**
	 * PostHog capture URL for server-side events.
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public static function get_capture_url() {
		return self::POSTHOG_API_HOST . '/i/v0/e/';
	}

	/**
	 * Get or create the stable site UUID used as distinct_id.
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	public static function get_site_id() {
		$site_id = get_option( Options::TELEMETRY_SITE_ID, '' );

		if ( is_string( $site_id ) && '' !== $site_id ) {
			return $site_id;
		}

		$site_id = wp_generate_uuid4();
		update_option( Options::TELEMETRY_SITE_ID, $site_id, false );

		return $site_id;
	}

	/**
	 * Collect Lite-owned feature toggle state.
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	private static function get_features() {
		$features = array(
			'enable_taxonomy_links' => self::is_option_enabled( Options::ENABLE_TAXONOMY_LINKS ),
			'poweredby'             => self::is_option_enabled( Options::POWEREDBY ),
			'ai_scraper_prevention' => self::is_option_enabled( Options::AI_SCRAPER_PREVENTION ),
			'shareasale'            => '' !== (string) get_option( Options::SHAREASALE, '' ),
			'copy_to_clipboard'     => self::is_option_enabled( Options::COPY_TO_CLIPBOARD ),
			'quick_links'           => array_values( array_filter( (array) get_option( Options::QUICK_LINKS, array() ) ) ),
			'card_buttons'          => array_values( array_filter( (array) get_option( Options::CARD_BUTTONS, array() ) ) ),
		);

		/**
		 * Filters the feature toggle snapshot.
		 *
		 * @since 1.2.9
		 *
		 * @param array $features Feature toggles currently in use.
		 */
		return apply_filters( 'tasty_recipes_telemetry_features', $features );
	}

	/**
	 * Count published tasty_recipe posts.
	 *
	 * @since 1.2.9
	 *
	 * @return int
	 */
	private static function get_recipe_count() {
		$counts = wp_count_posts( 'tasty_recipe' );

		if ( ! is_object( $counts ) || ! isset( $counts->publish ) ) {
			return 0;
		}

		return (int) $counts->publish;
	}

	/**
	 * Infer editor engagement from parent posts that embed recipes.
	 *
	 * @since 1.2.9
	 *
	 * @return array
	 */
	private static function get_editor_engagement() {
		global $wpdb;

		$engagement = array(
			'block'     => 0,
			'shortcode' => 0,
			'embed'     => 0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$embed_count         = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT post_id) FROM %i WHERE meta_key = %s',
				$wpdb->postmeta,
				Meta_Keys::RECIPE_PARENTS
			)
		);
		$engagement['embed'] = (int) $embed_count;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$parent_ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT meta_value FROM %i WHERE meta_key = %s LIMIT %d',
				$wpdb->postmeta,
				Meta_Keys::RECIPE_PARENTS,
				self::EDITOR_ENGAGEMENT_PARENT_LIMIT * 2
			)
		);

		if ( ! is_array( $parent_ids ) ) {
			return $engagement;
		}

		$inspected = 0;
		foreach ( $parent_ids as $parent_id ) {
			if ( ! is_numeric( $parent_id ) ) {
				continue;
			}

			$post = get_post( (int) $parent_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			if ( function_exists( 'has_block' ) && has_block( Block_Editor::RECIPE_BLOCK_TYPE, $post ) ) {
				++$engagement['block'];
			}

			if ( has_shortcode( $post->post_content, Shortcodes::RECIPE_SHORTCODE ) ) {
				++$engagement['shortcode'];
			}

			++$inspected;
			if ( $inspected >= self::EDITOR_ENGAGEMENT_PARENT_LIMIT ) {
				break;
			}
		}

		return $engagement;
	}

	/**
	 * Whether a stored option value represents an enabled toggle.
	 *
	 * @since 1.2.9
	 *
	 * @param string $option_name    Option name.
	 * @param mixed  $default_value  Default when unset.
	 *
	 * @return bool
	 */
	private static function is_option_enabled( $option_name, $default_value = false ) {
		$value = get_option( $option_name, $default_value );

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		if ( ! is_string( $value ) ) {
			return ! empty( $value );
		}

		$disabled_values = array( '', '0', 'off', 'false', 'no' );

		return ! in_array( strtolower( $value ), $disabled_values, true );
	}

	/**
	 * Current WordPress version string.
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	private static function get_wp_version() {
		global $wp_version;

		return is_string( $wp_version ) ? $wp_version : '';
	}

	/**
	 * Active theme stylesheet slug.
	 *
	 * @since 1.2.9
	 *
	 * @return string
	 */
	private static function get_active_theme() {
		$theme = wp_get_theme();

		if ( ! $theme instanceof \WP_Theme ) {
			return (string) get_stylesheet();
		}

		$stylesheet = $theme->get_stylesheet();

		return is_string( $stylesheet ) ? $stylesheet : (string) get_stylesheet();
	}
}

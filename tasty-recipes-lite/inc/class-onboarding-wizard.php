<?php
/**
 * Onboarding Wizard Controller class.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty\Framework\Admin\Menu;
use Tasty\Framework\Traits\Singleton;
use Tasty\Framework\Utils\Url;
use Tasty_Recipes;
use Tasty_Recipes\Utils;

/**
 * Handles the Onboarding Wizard page in the admin area.
 *
 * @since 1.0
 */
class Onboarding_Wizard {
	use Singleton;

	/**
	 * The slug of the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'tasty-recipes-onboarding-wizard';

	/**
	 * Transient name used for managing redirection to the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const TRANSIENT_NAME = 'tasty_recipes_activation_redirect';

	/**
	 * Transient value associated with the redirection to the Onboarding Wizard page.
	 * Used when activating a single plugin.
	 *
	 * @var string
	 */
	const TRANSIENT_VALUE = 'tasty-recipes-welcome';

	/**
	 * Transient value associated with the redirection to the Onboarding Wizard page.
	 * Used when activating multiple plugins at once.
	 *
	 * @var string
	 */
	const TRANSIENT_MULTI_VALUE = 'tasty-recipes-welcome-multi';

	/**
	 * Option name for storing the redirect status for the Onboarding Wizard page.
	 *
	 * @var string
	 */
	const REDIRECT_STATUS_OPTION = 'tasty_recipes_welcome_redirect';

	/**
	 * Option name for tracking if the onboarding wizard was skipped.
	 *
	 * @var string
	 */
	const ONBOARDING_SKIPPED_OPTION = 'tasty_recipes_onboarding_skipped';

	/**
	 * Defines the initial step for redirection within the application flow.
	 *
	 * @var string
	 */
	const INITIAL_STEP = 'consent-tracking';

	/**
	 * Option name to store usage data.
	 *
	 * @var string
	 */
	const USAGE_DATA_OPTION = 'tasty_recipes_onboarding_usage_data';

	/**
	 * Holds the URL to access the Onboarding Wizard's page.
	 *
	 * @var string
	 */
	private static $page_url = '';

	/**
	 * Register the API routes.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public static function register_api_routes() {
		register_rest_route(
			'tasty-recipes-lite/v1',
			'/usage-consent',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_update_usage_consent' ),
				'permission_callback' => function () {
					$nonce = Utils::server_param( 'HTTP_X_WP_NONCE' );

					return current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, 'wp_rest' );
				},
			) 
		);
	}

	/**
	 * Update usage consent.
	 *
	 * @since 1.1
	 *
	 * @param object $request The request object.
	 *
	 * @return void
	 */
	public static function rest_update_usage_consent( $request ) {
		$consent = sanitize_text_field( $request->get_param( 'consent' ) );

		if ( ! in_array( $consent, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error();
		}

		update_option( self::USAGE_DATA_OPTION, $consent );
		wp_send_json_success();
	}

	/**
	 * Initialize hooks for template page only.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function load_hooks() {
		$this->set_page_url();

		add_action( 'admin_init', array( $this, 'do_admin_redirects' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_consent_modal' ) );

		// Load page if admin page is Onboarding Wizard.
		$this->maybe_load_page();
	}

	/**
	 * Performs a safe redirect to the welcome screen when the plugin is activated.
	 * On single activation, we will redirect immediately.
	 * When activating multiple plugins, the redirect is delayed until a BD page is loaded.
	 *
	 * @return void
	 */
	public function do_admin_redirects() {
		$current_page = Utils::sanitize_get_key( 'page' );

		// Prevent endless loop.
		if ( $current_page === self::PAGE_SLUG ) {
			return;
		}

		// Only do this for single site installs.
		if ( is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->mark_onboarding_as_skipped();
			return;
		}

		if ( $this->has_onboarding_been_skipped() || class_exists( 'Tasty_Recipes_Pro' ) ) {
			return;
		}

		$transient_value = get_transient( self::TRANSIENT_NAME );
		if ( ! in_array( $transient_value, array( self::TRANSIENT_VALUE, self::TRANSIENT_MULTI_VALUE ), true ) ) {
			return;
		}

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			/**
			 * $_GET['activate-multi'] is set after activating multiple plugins.
			 * In this case, change the transient value so we know for future checks.
			 */
			set_transient( self::TRANSIENT_NAME, self::TRANSIENT_MULTI_VALUE, 60 );
			return;
		}

		if ( self::TRANSIENT_MULTI_VALUE === $transient_value && ! Url::is_wpt_page() ) {
			// For multi-activations we want to only redirect when a user loads a BD page.
			return;
		}

		set_transient( self::TRANSIENT_NAME, 'no', 60 );

		// Prevent redirect with every activation.
		if ( $this->has_already_redirected() ) {
			return;
		}

		// Redirect to the onboarding wizard's initial step.
		$page_url = add_query_arg( 'step', self::INITIAL_STEP, self::$page_url );
		if ( wp_safe_redirect( esc_url_raw( $page_url ) ) ) {
			exit;
		}
	}

	/**
	 * Initializes the Onboarding Wizard setup if on its designated admin page.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function maybe_load_page() {
		add_action( 'wp_ajax_tasty_recipes_onboarding_consent_tracking', array( $this, 'ajax_consent_tracking' ) );

		if ( $this->is_onboarding_wizard_page() ) {
			add_action( 'admin_menu', array( $this, 'menu' ), 99 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			add_action(
				'init',
				function () {
					remove_action( 'in_admin_header', array( Menu::instance(), 'show_admin_header' ), 100 );
				},
				PHP_INT_MAX
			);

			add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ), 999 );
		}
	}

	/**
	 * Actions to perform when activating the plugin.
	 *
	 * @return void
	 */
	public static function plugin_activation() {
		if ( get_transient( self::TRANSIENT_NAME ) !== 'no' ) {
			set_transient( self::TRANSIENT_NAME, self::TRANSIENT_VALUE, 60 );
		}
	}

	/**
	 * Add Onboarding Wizard menu item to sidebar and define index page.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function menu() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$label = __( 'Onboarding Wizard', 'tasty-recipes-lite' );

		add_submenu_page(
			'tasty',
			__( 'WP Tasty', 'tasty-recipes-lite' ) . ' | ' . $label,
			$label,
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the Onboarding Wizard page in the WordPress admin area.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function render() {
		if ( $this->has_onboarding_been_skipped() ) {
			delete_option( self::ONBOARDING_SKIPPED_OPTION );
			$this->has_already_redirected();
		}

		Tasty_Recipes::echo_template_part(
			'admin/onboarding-wizard/index',
			[
				// Note: Add step parts in order.
				'step_parts' => array(
					'consent-tracking' => 'steps/consent-tracking-step.php',
					'success'          => 'steps/success-step.php',
				),
			]
		);
	}

	/**
	 * Handle AJAX request to setup the "Never miss an important update" step.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function ajax_consent_tracking() {
		// Check permission and nonce.
		check_ajax_referer( 'tasty_recipes_onboarding_nonce', 'nonce' );
		Admin::validate_ajax_capability();

		add_option( self::USAGE_DATA_OPTION, 'yes' );
		$this->subscribe_to_active_campaign();

		// Send response.
		wp_send_json_success();
	}

	/**
	 * When the user consents to receiving news of updates, subscribe their email to ActiveCampaign.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function subscribe_to_active_campaign() {
		$user = wp_get_current_user();
		if ( empty( $user->user_email ) ) {
			return;
		}

		if ( ! self::should_send_email_to_active_campaign( $user->user_email ) ) {
			return;
		}

		$user_id    = $user->ID;
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name  = get_user_meta( $user_id, 'last_name', true );

		wp_remote_post(
			'https://feedback.strategy11.com/wp-admin/admin-ajax.php?action=frm_forms_preview&form=tasty-onboarding',
			array(
				'body' => http_build_query(
					array(
						'form_key'       => 'subscribe-onboarding',
						'frm_action'     => 'create',
						'form_id'        => 21,
						'item_key'       => '',
						'item_meta[0]'   => '',
						'item_meta[228]' => $user->user_email,
						'item_meta[231]' => 'Source - WPT Plugin Onboarding',
						'item_meta[229]' => is_string( $first_name ) ? $first_name : '',
						'item_meta[230]' => is_string( $last_name ) ? $last_name : '',
					)
				),
			)
		);
	}

	/**
	 * Try to skip any fake emails.
	 *
	 * @since 1.0
	 *
	 * @param string $email The user email.
	 *
	 * @return bool
	 */
	private static function should_send_email_to_active_campaign( $email ) {
		$substrings = array(
			'@wpengine.local',
			'@example.com',
			'@localhost',
			'@local.dev',
			'@local.test',
			'test@gmail.com',
			'admin@gmail.com',

		);
		foreach ( $substrings as $substring ) {
			if ( str_contains( $email, $substring ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Enqueues the Onboarding Wizard page scripts and styles.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( self::PAGE_SLUG, plugins_url( 'assets/dist/onboarding-wizard.css', __DIR__ ), array( 'editor-buttons' ), TASTY_RECIPES_LITE_VERSION );

		wp_enqueue_script( self::PAGE_SLUG, plugins_url( 'assets/dist/onboarding-wizard.build.js', __DIR__ ), array( 'wp-i18n' ), TASTY_RECIPES_LITE_VERSION, true );
		wp_localize_script( self::PAGE_SLUG, 'tastyRecipesOnboardingWizardVars', $this->get_js_variables() );
	}

	/**
	 * Get the Onboarding Wizard JS variables as an array.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_js_variables() {
		return array(
			'NONCE'        => wp_create_nonce( 'tasty_recipes_onboarding_nonce' ),
			'INITIAL_STEP' => self::INITIAL_STEP,
		);
	}

	/**
	 * Adds custom classes to the existing string of admin body classes.
	 *
	 * The function appends a custom class to the existing admin body classes, enabling full-screen mode for the admin interface.
	 *
	 * @since 1.0
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string Updated list of body classes, including the newly added classes.
	 */
	public function add_admin_body_classes( $classes ) {
		return $classes . ' tasty-recipes-admin-full-screen';
	}

	/**
	 * Checks if the Onboarding Wizard was skipped during the plugin's installation.
	 *
	 * @since 1.0
	 *
	 * @return bool True if the Onboarding Wizard was skipped, false otherwise.
	 */
	public function has_onboarding_been_skipped() {
		return get_option( self::ONBOARDING_SKIPPED_OPTION, false );
	}

	/**
	 * Marks the Onboarding Wizard as skipped to prevent automatic redirects to the wizard.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function mark_onboarding_as_skipped() {
		update_option( self::ONBOARDING_SKIPPED_OPTION, true );
	}

	/**
	 * Check if the current page is the Onboarding Wizard page.
	 *
	 * @since 1.0
	 *
	 * @return bool True if the current page is the Onboarding Wizard page, false otherwise.
	 */
	public function is_onboarding_wizard_page() {
		return Utils::is_admin_page( self::PAGE_SLUG );
	}

	/**
	 * Checks if the plugin has already performed a redirect to avoid repeated redirections.
	 *
	 * @return bool Returns true if already redirected, otherwise false.
	 */
	private function has_already_redirected() {
		if ( get_option( self::REDIRECT_STATUS_OPTION ) ) {
			return true;
		}

		update_option( self::REDIRECT_STATUS_OPTION, TASTY_RECIPES_LITE_VERSION );
		return false;
	}

	/**
	 * Get the path to the Onboarding Wizard views.
	 *
	 * @since 1.0
	 *
	 * @return string Path to views.
	 */
	public static function get_page_url() {
		return self::$page_url;
	}

	/**
	 * Set the URL to access the Onboarding Wizard's page.
	 *
	 * @return void
	 */
	private function set_page_url() {
		self::$page_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Maybe show the consent modal.
	 * 
	 * @since 1.1
	 *
	 * @return void
	 */
	public function maybe_show_consent_modal() {
		if ( $this->is_onboarding_wizard_page() || get_option( self::USAGE_DATA_OPTION ) ) {
			return;
		}

		$onboarding_version = get_option( 'tasty_recipes_welcome_redirect' );

		// We return if the redirect version is greater than or equal to 1.0.4 because the usage consent was added in 1.0.4.
		if ( $onboarding_version && version_compare( $onboarding_version, '1.0.4', '>=' ) ) {
			return;
		}

		$asset_meta = tasty_get_asset_meta( dirname( TASTY_RECIPES_LITE_FILE ) . '/assets/dist/usage-modal.build.asset.php' );

		wp_enqueue_style(
			'tasty-recipes-usage-modal',
			plugins_url( 'assets/dist/usage-modal.css', TASTY_RECIPES_LITE_FILE ),
			array(),
			$asset_meta['version']
		);

		wp_enqueue_script(
			'usage-modal',
			plugins_url( 'assets/dist/usage-modal.build.js', TASTY_RECIPES_LITE_FILE ),
			$asset_meta['dependencies'],
			$asset_meta['version'],
			true
		);

		?>
		<div id="tasty-recipes-usage-modal" class="hide-if-no-js"></div>
		<?php
	}
}

<?php
/**
 * Manages interactions with the admin.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty\Framework\Utils\Url;
use Tasty_Recipes;
use Tasty_Recipes\Settings;
use Tasty_Recipes\Objects\Recipe;

/**
 * Manages interactions with the admin.
 */
class Admin {

	/**
	 * Capability required to manage settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Plugin name for EDD registration.
	 *
	 * @var string
	 */
	const ITEM_AUTHOR = 'Tasty Recipes';

	/**
	 * Plugin author for EDD registration.
	 *
	 * @var string
	 */
	const ITEM_NAME = 'Tasty Recipes';

	/**
	 * Cache key used to store license check data.
	 *
	 * @var string
	 */
	const LICENSE_CHECK_CACHE_KEY = 'tasty-recipes-license-check';

	/**
	 * Key used for nonce authentication.
	 *
	 * @var string
	 */
	const NONCE_KEY = 'tasty-recipes-settings';

	/**
	 * URL to the plugin store.
	 *
	 * @var string
	 */
	const STORE_URL = 'https://www.wptasty.com';

	/**
	 * Parent page for the settings page.
	 *
	 * @var string
	 */
	const PAGE_BASE = 'options-general.php';

	/**
	 * Slug for the settings page.
	 *
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'tasty-recipes';

	/**
	 * Group used for authentication settings.
	 *
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	const SETTINGS_GROUP_LICENSE = '';

	/**
	 * Section used for authentication settings.
	 *
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	const SETTINGS_SECTION_LICENSE = '';

	/**
	 * Group used for card settings.
	 *
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	const SETTINGS_GROUP_CARD = '';

	/**
	 * Section used for card settings.
	 *
	 * @deprecated 1.0
	 *
	 * @var string
	 */
	const SETTINGS_SECTION_CARD_DESIGN = '';

	/**
	 * Available template options.
	 *
	 * @deprecated 1.0
	 *
	 * @var array
	 */
	const TEMPLATE_OPTIONS = array(
		'deprecated',
	);

	/**
	 * ID used for TinyMCE instance in settings.
	 *
	 * @var string
	 */
	private static $editor_id = 'tasty-recipes-settings';

	/**
	 * Option name for storing the database version.
	 *
	 * @since 1.2
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'tasty_recipes_db_version';

	/**
	 * Current database version.
	 *
	 * @since 1.2
	 *
	 * @var string
	 */
	const CURRENT_DB_VERSION = '1.0';

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_args
		add_action( 'http_request_args', array( __CLASS__, 'filter_http_request_args' ), 10, 2 );
		add_filter( 'manage_posts_columns', array( __CLASS__, 'action_manage_posts_columns' ) );
		add_filter( 'manage_pages_columns', array( __CLASS__, 'action_manage_posts_columns' ) );
		add_action( 'manage_posts_custom_column', array( __CLASS__, 'action_manage_posts_custom_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( __CLASS__, 'action_manage_posts_custom_column' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'action_quick_edit_custom_box' ) );
		add_filter( 'hidden_columns', array( __CLASS__, 'filter_hidden_columns' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TASTY_RECIPES_LITE_FILE ), array( __CLASS__, 'filter_plugin_action_links' ) );
		add_filter( 'tasty_framework_admin_header_title', array( __CLASS__, 'update_admin_dashboard_title' ) );
		add_action( 'admin_notices', array( __CLASS__, 'action_admin_notices_db_migration' ) );

		if ( ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'wp_ajax_tasty_recipes_get_count', array( __CLASS__, 'handle_wp_ajax_get_count' ) );
		add_action( 'wp_ajax_tasty_recipes_convert', array( __CLASS__, 'handle_wp_ajax_convert' ) );
		add_action( 'wp_ajax_tasty_recipes_run_db_migration', array( __CLASS__, 'handle_wp_ajax_run_db_migration' ) );
	}

	/**
	 * Includes PHP and plugin versions in the user agent for update checks.
	 *
	 * @param array  $r   An array of HTTP request arguments.
	 * @param string $url The request URL.
	 *
	 * @return array
	 */
	public static function filter_http_request_args( $r, $url ) {
		if ( self::STORE_URL !== $url || 'POST' !== $r['method'] ) {
			return $r;
		}

		if ( ! isset( $r['body'] ) || ! is_array( $r['body'] ) ) {
			return $r;
		}

		$body = $r['body'];
		if ( empty( $body['item_name'] ) || self::ITEM_NAME !== $body['item_name'] ) {
			return $r;
		}

		$r['user-agent'] = rtrim( $r['user-agent'], ';' )
			. '; PHP/' . PHP_VERSION . '; '
			. self::ITEM_NAME . '/' . TASTY_RECIPES_LITE_VERSION;
		return $r;
	}

	/**
	 * Registers used settings.
	 *
	 * @return void
	 */
	public static function action_admin_menu() {
		_deprecated_function( __METHOD__, '1.0', 'Settings::register_settings' );
		Settings::register_settings();
	}

	/**
	 * Renders the admin notice nag when license key isn't set or invalid.
	 *
	 * @deprecated 3.10
	 *
	 * @return void
	 */
	public static function action_admin_notices_license_key() {
		_deprecated_function( __METHOD__, '3.10' );
	}

	/**
	 * Add the callback for the plugin menu item.
	 *
	 * @param array $menu_items Admin menu items.
	 *
	 * @return array
	 */
	public static function add_menu_item( $menu_items ) {
		_deprecated_function( __METHOD__, '1.0', 'Settings::add_menu_item' );
		return Settings::add_menu_item( $menu_items );
	}

	/**
	 * Registers a new custom column for Tasty Recipes.
	 *
	 * @param array $columns Existing column names.
	 *
	 * @return array
	 */
	public static function action_manage_posts_columns( $columns ) {
		global $post_type;
		
		if ( 'tasty_recipe' === $post_type ) {
			return $columns;
		}

		$columns['tasty_recipe'] = esc_html__( 'Tasty Recipe', 'tasty-recipes-lite' );

		return $columns;
	}

	/**
	 * Renders an 'Edit Tasty Recipe' button for the column if the post has a recipe.
	 *
	 * @param string $column_name Name of the column.
	 * @param int    $post_id     ID of the post being displayed.
	 *
	 * @return void
	 */
	public static function action_manage_posts_custom_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'tasty_recipe':
				$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( $post_id );
				if ( ! empty( $recipe_ids ) ) {
					$recipe_id = array_shift( $recipe_ids );
					echo '<button class="button tasty-recipes-edit-button" data-recipe-id="' . esc_attr( $recipe_id ) . '">' .
						esc_html__( 'Edit Tasty Recipe', 'tasty-recipes-lite' ) .
						'</button>';
				}
				break;
		}
	}

	/**
	 * Renders the 'Edit Tasty Recipe' button on Quick Edit.
	 *
	 * @param string $column_name Name of the column.
	 *
	 * @return void
	 */
	public static function action_quick_edit_custom_box( $column_name ) {
		switch ( $column_name ) {
			case 'tasty_recipe':
				echo '<button class="button tasty-recipes-edit-button" data-recipe-id="">' . esc_html__( 'Edit Tasty Recipe', 'tasty-recipes-lite' ) . '</button>';
				break;
		}
	}

	/**
	 * Hides the 'Tasty Recipe' column by default.
	 *
	 * @param array $columns Existing default hidden columns.
	 *
	 * @return array
	 */
	public static function filter_hidden_columns( $columns ) {
		$columns[] = 'tasty_recipe';
		return $columns;
	}

	/**
	 * Activates the license when the option is updated.
	 *
	 * @deprecated 3.10
	 *
	 * @return void
	 */
	public static function action_update_option_register_license() {
		_deprecated_function( __METHOD__, '3.10' );
	}

	/**
	 * Clears the license and version check cache when license key is updated.
	 *
	 * @return void
	 */
	public static function action_update_option_clear_transient() {
		delete_transient( self::LICENSE_CHECK_CACHE_KEY );
		$cache_key = md5( 'edd_plugin_' . sanitize_key( plugin_basename( TASTY_RECIPES_LITE_FILE ) ) . '_version_info' );
		delete_site_transient( $cache_key );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cache_key = 'edd_api_request_' . substr( md5( serialize( basename( TASTY_RECIPES_LITE_FILE, '.php' ) ) ), 0, 15 );
		delete_site_transient( $cache_key );
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * Adds 'Settings' and 'Remove license' links to plugin settings.
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array
	 */
	public static function filter_plugin_action_links( $links ) {
		$links['tr_settings']        = '<a href="' . esc_url( add_query_arg( 'tab', 'settings', menu_page_url( Settings::PAGE_SLUG, false ) ) ) . '">' .
			esc_html__( 'Settings', 'tasty-recipes-lite' ) .
			'</a>';
		$links['tr_license_manager'] = '<a href="' . esc_url( Url::get_main_admin_url() ) . '">' . esc_html__( 'License manager', 'tasty-recipes-lite' ) . '</a>';
		return $links;
	}

	/**
	 * Update the admin dashboard title.
	 *
	 * @param string $title The title to update.
	 *
	 * @return string
	 */
	public static function update_admin_dashboard_title( $title ) {
		if ( 'Tasty Recipes Lite' === $title ) {
			$title = 'Recipes Lite';
		}
		return $title;
	}

	/**
	 * Handles a request to remove the license key.
	 *
	 * @deprecated 3.10
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_remove_license_key() {
		_deprecated_function( __METHOD__, '3.10' );

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Handles an AJAX request to preview a recipe template.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_preview_recipe_card() {
		_deprecated_function( __METHOD__, '1.0', 'Templates::handle_wp_ajax_preview_recipe_card' );
	}

	/**
	 * Handles an AJAX request to get the number of matching recipes to convert.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_get_count() {
		$nonce = Utils::get_param( 'nonce', 'sanitize_key' );
		if ( empty( $nonce ) || ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			Utils::send_json_error( __( 'Sorry, you don\'t have permission to do this.', 'tasty-recipes-lite' ) );
		}

		$class      = false;
		$converters = Tasty_Recipes::get_converters();
		$get_type   = Utils::get_param( 'type' );

		if ( ! empty( $get_type ) && isset( $converters[ $get_type ] ) ) {
			$class = $converters[ $get_type ]['class'];
		} else {
			Utils::send_json_error( __( 'Couldn\'t count recipes. Please contact support for assistance.', 'tasty-recipes-lite' ) );
		}

		wp_send_json_success(
			array(
				'count' => (int) $class::get_count(),
			)
		);
	}

	/**
	 * Handles an AJAX request to batch convert some recipes.
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_convert() {
		$nonce = Utils::get_param( 'nonce', 'sanitize_key' );
		if ( empty( $nonce ) || ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			Utils::send_json_error( __( 'Sorry, you don\'t have permission to do this.', 'tasty-recipes-lite' ) );
		}

		$class      = false;
		$converters = Tasty_Recipes::get_converters();
		$get_type   = Utils::get_param( 'type' );

		if ( ! empty( $get_type ) && isset( $converters[ $get_type ] ) ) {
			$class = $converters[ $get_type ]['class'];
		} else {
			Utils::send_json_error(
				__( 'Couldn\'t convert recipes. Please contact support for assistance.', 'tasty-recipes-lite' )
			);
		}

		$post_ids  = $class::get_post_ids();
		$converted = 0;
		foreach ( $post_ids as $post_id ) {
			$type = 'shortcode';
			if ( function_exists( 'has_blocks' )
				&& has_blocks( $post_id ) ) {
				$type = 'block';
			}
			$recipe = $class::convert_post( $post_id, $type );
			if ( $recipe ) {
				++$converted;
			} else {
				Utils::send_json_error(
					sprintf(
						/* translators: %1$s: post title, %2$d: post ID */
						__( 'Couldn\'t convert recipe in \'%1$s\' (post %2$d). Please contact support for assistance.', 'tasty-recipes-lite' ),
						get_the_title( $post_id ),
						$post_id
					)
				);
			}
		}

		$after_count = $class::get_count();
		wp_send_json_success(
			array(
				'converted' => $converted,
				'count'     => (int) $after_count,
			)
		);
	}

	/**
	 * Renders in the admin header.
	 *
	 * @deprecated 3.10
	 *
	 * @return void
	 */
	public static function action_in_admin_header() {
		_deprecated_function( __METHOD__, '3.10' );
	}

	/**
	 * Renders the Tasty Recipes settings page.
	 *
	 * @return void
	 */
	public static function handle_settings_page() {
		_deprecated_function( __METHOD__, '1.0', 'Settings::handle_settings_page' );
		Settings::handle_settings_page();
	}

	/**
	 * Sets the TinyMCE editor buttons for our editor instance.
	 *
	 * @param array  $buttons   Existing TinyMCE buttons.
	 * @param string $editor_id ID for the editor instance.
	 *
	 * @return array
	 */
	public static function filter_teeny_mce_buttons( $buttons, $editor_id ) {
		if ( self::$editor_id !== $editor_id ) {
			return $buttons;
		}
		return array( 'bold', 'italic', 'underline', 'bullist', 'numlist', 'link', 'unlink', 'removeformat' );
	}

	/**
	 * Prints the recipe editor modal template, but only once.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function action_admin_footer_render_template() {
		_deprecated_function( __METHOD__, '1.0', 'Settings::action_admin_footer_render_template' );
		Settings::action_admin_footer_render_template();
	}

	/**
	 * Render an input field.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function render_input_field() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Sanitize quick links option field.
	 *
	 * @deprecated 1.0
	 *
	 * @param array $item Array of items being selected.
	 *
	 * @return array
	 */
	public static function sanitize_quick_links( $item ) {
		_deprecated_function( __METHOD__, '1.0', 'Settings::sanitize_quick_links' );
		return Settings::sanitize_quick_links( $item );
	}

	/**
	 * Sanitizes the card buttons option.
	 *
	 * @deprecated 1.0
	 *
	 * @param array $original Original card button option value.
	 *
	 * @return array
	 */
	public static function sanitize_card_buttons( $original ) {
		_deprecated_function( __METHOD__, '1.0', 'Settings::sanitize_card_buttons' );
		return Settings::sanitize_card_buttons( $original );
	}

	/**
	 * Sanitizes the customization option to make sure there are only expected values.
	 *
	 * @deprecated 1.0
	 *
	 * @param array $original Original customization option value.
	 *
	 * @return array
	 */
	public static function sanitize_customization_option( $original ) {
		_deprecated_function( __METHOD__, '1.0', 'Settings::sanitize_customization_option' );
		return Settings::sanitize_customization_option( $original );
	}

	/**
	 * Render a select field.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function render_select_field() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Renders multiselect checkbox field.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function render_multiselect_checkboxes_field() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Renders the card buttons field.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function render_card_buttons_field() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Gets the license check object.
	 *
	 * @deprecated 3.10
	 *
	 * @return bool|object
	 */
	public static function get_license_check() {
		_deprecated_function( __METHOD__, '3.10' );

		return false;
	}

	/**
	 * Validate the current user capability and exit with json error in case of permission denied.
	 *
	 * @since 3.8
	 *
	 * @return void
	 */
	public static function validate_ajax_capability() {
		if ( current_user_can( self::CAPABILITY ) ) {
			return;
		}

		Utils::send_json_error( __( 'You don\'t have permission to do this.', 'tasty-recipes-lite' ) );
	}

	/**
	 * Filter quick links admin field to handle the removed item "both"
	 * and cast the string option to an array.
	 *
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function filter_quick_links_backward_compatibility() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Handle the removed item "both" and cast the string option to an array.
	 *
	 * @deprecated 1.0
	 *
	 * @param mixed $option_value Option value to be handled.
	 *
	 * @return array|string[]
	 */
	public static function handle_quick_links_backward_compatibility( $option_value ) {
		_deprecated_function( __METHOD__, '1.0', 'Settings::handle_quick_links_backward_compatibility' );
		return Settings::handle_quick_links_backward_compatibility( $option_value );
	}

	/**
	 * Show SSL certificate error message.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function show_ssl_error() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Print instacart image in admin instead of real iframe.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return void
	 */
	public static function print_mock_instacart_html() {
		_deprecated_function( __METHOD__, '1.0' );
	}

	/**
	 * Check if the Instacart button can be shown.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function can_show_instacart_button() {
		_deprecated_function( __METHOD__, '1.0' );
		return false;
	}

	/**
	 * Check if Cook Mode can be enabled in this site or not.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function can_enable_cook_mode() {
		_deprecated_function( __METHOD__, '1.0' );
		return false;
	}

	/**
	 * Is Cook Mode enabled or not.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return bool
	 */
	public static function is_cook_mode_enabled() {
		_deprecated_function( __METHOD__, '1.0' );
		return false;
	}

	/**
	 * Get Cook Mode helper message.
	 *
	 * @since 3.8
	 * @deprecated 1.0
	 *
	 * @return string
	 */
	public static function get_cook_mode_helper() {
		_deprecated_function( __METHOD__, '1.0' );
		return '';
	}

	/**
	 * Renders the admin notice when database migration is needed.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function action_admin_notices_db_migration() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		if ( ! self::needs_db_migration() ) {
			return;
		}

		$asset_meta = tasty_get_asset_meta( dirname( TASTY_RECIPES_LITE_FILE ) . '/assets/dist/database-migration.build.asset.php' );
		wp_enqueue_script(
			'tasty-recipes-database-migration',
			plugins_url( 'assets/dist/database-migration.build.js', TASTY_RECIPES_LITE_FILE ),
			$asset_meta['dependencies'],
			$asset_meta['version'],
			true
		);

		wp_localize_script(
			'tasty-recipes-database-migration',
			'tastyRecipesDatabaseMigration',
			array(
				'nonce'   => wp_create_nonce( 'tasty_recipes_db_migration' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
		
		wp_enqueue_style(
			'tasty-recipes-database-migration',
			plugins_url( 'assets/dist/database-migration.css', TASTY_RECIPES_LITE_FILE ),
			array(),
			$asset_meta['version']
		);

		$migration_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'tasty_recipes_run_db_migration',
				),
				admin_url( 'admin-ajax.php' )
			),
			'tasty_recipes_db_migration'
		);

		Tasty_Recipes::echo_template_part(
			'admin/notice-db-migration',
			array(
				'migration_url' => $migration_url,
			)
		);
	}

	/**
	 * Check if database migration is needed.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	private static function needs_db_migration() {
		$current_version = get_option( self::DB_VERSION_OPTION, '0' );

		return version_compare( $current_version, self::CURRENT_DB_VERSION, '<' ) && 
				( self::get_recipes_needing_migration_count() > 0 );
	}

	/**
	 * Handles the AJAX request to run database migration.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public static function handle_wp_ajax_run_db_migration() {
		check_ajax_referer( 'tasty_recipes_db_migration' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'tasty-recipes-lite' ) ) );
		}

		$batch_size = 50;
		$offset     = isset( $_POST['offset'] ) ? absint( wp_unslash( $_POST['offset'] ) ) : 0;
		$recipes    = self::get_recipes_needing_migration( $batch_size, $offset );
		$migrated   = 0;

		foreach ( $recipes as $recipe_id ) {
			self::migrate_recipe_taxonomies( $recipe_id );
			++$migrated;
		}

		$remaining = self::get_recipes_needing_migration_count();

		if ( empty( $recipes ) || $remaining === 0 ) {
			update_option( self::DB_VERSION_OPTION, self::CURRENT_DB_VERSION );
			wp_send_json_success(
				array(
					'complete'  => true,
					'migrated'  => $migrated,
					'remaining' => 0,
					'message'   => __( 'Database migration completed successfully.', 'tasty-recipes-lite' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'complete'  => false,
				'migrated'  => $migrated,
				'remaining' => $remaining,
				'offset'    => $offset + $batch_size,
			)
		);
	}

	/**
	 * Get recipes that need migration (have legacy meta).
	 *
	 * @since 1.2
	 *
	 * @param int $limit  Number of recipes to retrieve.
	 * @param int $offset Offset for pagination.
	 *
	 * @return array Array of recipe post IDs.
	 */
	private static function get_recipes_needing_migration( $limit = 50, $offset = 0 ) {
		global $wpdb;

		// We use a direct database query to avoid the overhead of get_posts() with meta_query. It is a lot faster and more efficient.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'tasty_recipe'
				AND pm.meta_key IN ( 'category', 'cuisine', 'method', 'diet' )
				ORDER BY p.ID ASC
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Get count of recipes that need migration.
	 *
	 * @since 1.2
	 *
	 * @return int
	 */
	private static function get_recipes_needing_migration_count() {
		global $wpdb;

		// We use a direct database query to avoid the overhead of get_posts(). It is a lot faster and more efficient.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT p.ID )
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'tasty_recipe'
			AND pm.meta_key IN ( 'category', 'cuisine', 'method', 'diet' )"
		);
	}

	/**
	 * Migrate a single recipe's meta to taxonomies.
	 *
	 * @since 1.2
	 *
	 * @param int $recipe_id Recipe post ID.
	 *
	 * @return void
	 */
	private static function migrate_recipe_taxonomies( $recipe_id ) {
		$recipe = Recipe::get_by_id( $recipe_id );

		if ( ! $recipe ) {
			return;
		}

		self::migrate_recipe_meta( $recipe, 'category' );
		self::migrate_recipe_meta( $recipe, 'cuisine' );
		self::migrate_recipe_meta( $recipe, 'method' );
		self::migrate_recipe_meta( $recipe, 'diet' );
	}

	/**
	 * Migrate a single meta key to taxonomy for a recipe.
	 *
	 * @since 1.2
	 *
	 * @param Recipe $recipe   Recipe object.
	 * @param string $meta_key Meta key to migrate.
	 *
	 * @return void
	 */
	private static function migrate_recipe_meta( $recipe, $meta_key ) {
		$recipe_id = $recipe->get_id();
		$value     = get_post_meta( $recipe_id, $meta_key, true );

		if ( ! empty( $value ) ) {
			$method = "set_{$meta_key}";
			$recipe->$method( $value );

			return;
		}

		delete_post_meta( $recipe_id, $meta_key );
	}
}

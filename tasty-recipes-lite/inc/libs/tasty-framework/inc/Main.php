<?php
/**
 * Main entry point class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework;

use Tasty\Framework\Admin\BackwardCompatibility;
use Tasty\Framework\Admin\JSMessageBox;
use Tasty\Framework\Admin\License\APIClient;
use Tasty\Framework\Admin\License\Manager;
use Tasty\Framework\Admin\Menu;
use Tasty\Framework\Admin\Update;

/**
 * Main entry point class.
 */
abstract class Main {

	/**
	 * Current framework plugin base file path.
	 *
	 * @var string
	 */
	private static $plugin_file = '';

	/**
	 * Registery array.
	 *
	 * @var array
	 */
	private static $registry = array();

	/**
	 * Define constants.
	 *
	 * @return void
	 */
	public static function initialize_framework() {
		define( 'TASTY_FRAMEWORK_VERSION', '1.1.11' );
		define( 'TASTY_FRAMEWORK_PATH', self::plugin_path() );
		define( 'TASTY_FRAMEWORK_PATH_ASSETS', self::plugin_path( 'assets' ) );

		define( 'TASTY_FRAMEWORK_URL_ASSETS', self::plugin_url( 'assets' ) );
		define( 'TASTY_FRAMEWORK_URL_ASSETS_IMAGES', TASTY_FRAMEWORK_URL_ASSETS . '/images' );
		define( 'TASTY_FRAMEWORK_URL_ASSETS_STYLES', TASTY_FRAMEWORK_URL_ASSETS . '/dist/css' );
		define( 'TASTY_FRAMEWORK_URL_ASSETS_SCRIPTS', TASTY_FRAMEWORK_URL_ASSETS . '/dist/js' );

		self::load_components();
	}

	/**
	 * Load main components.
	 *
	 * @return void
	 */
	public static function load_components() {
		if ( is_admin() ) {
			self::load_admin_components();
		}

		/**
		 * After initializing tasty framework components.
		 *
		 * @since 1.0.0
		 *
		 * @param array $registry Registry array.
		 */
		do_action( 'tasty_framework_after_init', self::$registry );
	}

	/**
	 * Load admin components.
	 *
	 * @return void
	 */
	private static function load_admin_components() {
		self::$registry['template_admin']         = tasty_get_admin_template();
		self::$registry['js_messagebox']          = JSMessageBox::instance()->init( get_current_user_id() );
		self::$registry['admin_menu']             = Menu::instance()->init( self::$registry['template_admin'], self::$registry['js_messagebox'] );
		self::$registry['update']                 = Update::instance()->init();
		self::$registry['license_apiclient']      = APIClient::instance();
		self::$registry['license_manager']        = Manager::instance()->init(
			self::$registry['license_apiclient'],
			self::$registry['template_admin'],
			self::$registry['js_messagebox']
		);
		self::$registry['backward_compatibility'] = BackwardCompatibility::instance()->init();
	}

	/**
	 * Initialize class.
	 *
	 * @param string $plugin_file Base framework plugin path.
	 *
	 * @throws \Exception For autoloader.
	 *
	 * @return void
	 */
	public static function init( $plugin_file ) {
		self::$plugin_file = $plugin_file;

		load_plugin_textdomain( 'tasty', false, self::plugin_path( 'languages' ) );

		spl_autoload_register( array( __CLASS__, 'autoload' ) );

		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath
		require_once self::plugin_path( 'functions.php' );

		/**
		 * Before initializing tasty framework components.
		 */
		do_action( 'tasty_framework_pre_init' );

		add_action( 'init', array( __CLASS__, 'initialize_framework' ) );
	}

	/**
	 * Get realpath based on the framework plugin base path.
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public static function plugin_path( $path = '' ) {
		$base = dirname( self::$plugin_file );

		if ( ! empty( $path ) ) {
			return trailingslashit( $base ) . $path;
		}

		return untrailingslashit( $base );
	}

	/**
	 * Get plugin url.
	 *
	 * @param string $path Path to get the full url for.
	 *
	 * @return string
	 */
	public static function plugin_url( $path ) {
		return plugins_url( $path, self::$plugin_file );
	}

	/**
	 * Plugin custom autoloader.
	 *
	 * @param string $current_class Current class to be loaded.
	 *
	 * @return void
	 */
	public static function autoload( $current_class ) {
		$prefix   = 'Tasty\\Framework\\';
		$base_dir = self::plugin_path() . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;

		// does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $current_class, $len ) !== 0 ) {
			// no, move to the next registered autoloader.
			return;
		}

		$relative_class = substr( $current_class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// if the file exists, require it.
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			require_once $file;
		}
	}

	/**
	 * No clone.
	 *
	 * @return void
	 */
	final public function __clone() {
		_doing_it_wrong( __METHOD__, esc_attr__( 'Don\'t try again!', 'tasty-recipes-lite' ), '1.0.0' );
	}

	/**
	 * No serialization.
	 *
	 * @return void
	 */
	final public function __wakeup() {
		_doing_it_wrong( __METHOD__, esc_attr__( 'Don\'t try again!', 'tasty-recipes-lite' ), '1.0.0' );
	}

	/**
	 * No direct instance.
	 */
	final private function __construct() {}
}

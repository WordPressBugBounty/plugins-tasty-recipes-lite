<?php
/**
 * Plugin Name: Tasty Framework
 * Plugin URI:  https://wptasty.com/
 * Description: Common functionality between all WP Tasty plugins.
 * Author:      WP Tasty team
 * Author URI:  https://wptasty.com/
 * Version:     1.1.11
 * License:     GPLv2 or later (license.txt).
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Tasty\Framework\Versions;
use Tasty\Framework\Main;

if ( ! function_exists( 'tasty_register_1_dot_1_dot_11' ) ) {

	if ( ! class_exists( Versions::class ) ) {
		require_once __DIR__ . '/inc/Versions.php';
		add_action( 'plugins_loaded', array( Versions::class, 'initialize_latest_version' ), 1 );
	}

	add_action( 'plugins_loaded', 'tasty_register_1_dot_1_dot_11', 0 );

	/**
	 * Register current version of the framework.
	 *
	 * @return void
	 */
	function tasty_register_1_dot_1_dot_11() {
		Versions::instance()->register( '1.1.11', 'tasty_initialize_1_dot_1_dot_11' );
	}

	/**
	 * Initialize current version of the framework.
	 *
	 * @return void
	 */
	function tasty_initialize_1_dot_1_dot_11() {
		if ( class_exists( Main::class ) ) {
			return;
		}

		require_once __DIR__ . '/inc/Main.php';
		Main::init( __FILE__ );
	}
}

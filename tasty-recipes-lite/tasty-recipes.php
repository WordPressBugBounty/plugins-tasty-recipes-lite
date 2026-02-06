<?php
/**
 * Plugin Name:     Tasty Recipes Lite
 * Plugin URI:      https://www.wptasty.com/tasty-recipes
 * Description:     Tasty Recipes is the easiest way to publish recipes on your WordPress blog.
 * Author:          WP Tasty Recipes Team
 * Author URI:      https://www.wptasty.com
 * License:         GPLv2 or later
 * License URI:     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:     tasty-recipes-lite
 * Domain Path:     /languages
 * Version: 1.2.1
 *
 * @package         Tasty_Recipes
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! defined( 'TASTY_RECIPES_LITE_VERSION' ) ) {
	define( 'TASTY_RECIPES_LITE_VERSION', '1.2.1' );
	define( 'TASTY_RECIPES_LITE_FILE', __FILE__ );
}

if ( ! defined( 'TASTY_RECIPES_NUTRIFOX_DOMAIN' ) ) {
	define( 'TASTY_RECIPES_NUTRIFOX_DOMAIN', 'nutrifox.com' );
}

// Load Tasty framework.
require_once dirname( TASTY_RECIPES_LITE_FILE ) . '/inc/libs/tasty-framework/tasty-framework.php';

if ( ! function_exists( 'tasty_recipes_lite' ) ) {
	/**
	 * Access the Tasty Recipes instance.
	 *
	 * @return Tasty_Recipes
	 */
	function tasty_recipes_lite() {
		if ( ! class_exists( 'Tasty_Recipes' ) ) {
			require_once dirname( TASTY_RECIPES_LITE_FILE ) . '/inc/class-tasty-recipes.php';
		}

		return Tasty_Recipes::get_instance();
	}
	add_action( 'plugins_loaded', 'tasty_recipes_lite' );
}

if ( ! function_exists( 'tasty_recipes_lite_activation' ) ) {
	/**
	 * Run the plugin activation hook.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	function tasty_recipes_lite_activation() {
		if ( ! class_exists( 'Tasty_Recipes' ) ) {
			require_once dirname( TASTY_RECIPES_LITE_FILE ) . '/inc/class-tasty-recipes.php';
		}
		Tasty_Recipes::plugin_activation();

		if ( ! trait_exists( 'Tasty\Framework\Traits\Singleton' ) ) {
			require_once dirname( TASTY_RECIPES_LITE_FILE ) . '/inc/libs/tasty-framework/inc/Traits/Singleton.php';
		}
		if ( ! class_exists( 'Tasty_Recipes\Onboarding_Wizard' ) ) {
			require_once dirname( TASTY_RECIPES_LITE_FILE ) . '/inc/class-onboarding-wizard.php';
		}
		Tasty_Recipes\Onboarding_Wizard::plugin_activation();
	}
	register_activation_hook( __FILE__, 'tasty_recipes_lite_activation' );
}

<?php
/**
 * Helper functions.
 *
 * @package Tasty/Framework
 */

use Tasty\Framework\Utils\Template;

if ( ! function_exists( 'tasty_get_filesystem' ) ) {
	/**
	 * Get filesystem instance.
	 *
	 * @return WP_Filesystem_Direct Filesystem instance
	 */
	function tasty_get_filesystem() {
		static $filesystem = null;

		if ( is_null( $filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$filesystem = new WP_Filesystem_Direct( new stdClass() );
		}

		return $filesystem;
	}
}

if ( ! function_exists( 'tasty_get_admin_template' ) ) {
	/**
	 * Get admin template instance.
	 *
	 * @return Template Filesystem instance
	 */
	function tasty_get_admin_template() {
		return Template::instance()->init( __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Admin', tasty_get_filesystem() );
	}
}

if ( ! function_exists( 'tasty_get_asset_meta' ) ) {
	/**
	 * Retrieve the metadata from the provided asset map.
	 *
	 * @since x.x
	 *
	 * @param string $path The path to the asset map.
	 *
	 * @return array The asset map metadata.
	 */
	function tasty_get_asset_meta( $path ) {
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		return file_exists( $path ) ? require $path : array(
			'dependencies' => array(),
			'version'      => TASTY_FRAMEWORK_VERSION,
		);
	}
}

if ( ! function_exists( 'tasty_enqueue_editor_scripts' ) ) {
	/**
	 * Enqueue the wp editor scripts used by the settings page.
	 * 
	 * @since x.x
	 *
	 * @return void
	 */
	function tasty_enqueue_editor_scripts() {
		wp_enqueue_editor();
		wp_enqueue_script( 'wp-editor' );
		wp_enqueue_script( 'wp-tinymce' );
		wp_enqueue_style( 'wp-editor-css' );
	}
}

<?php
/**
 * Database migration notice template.
 *
 * @package Tasty_Recipes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="notice notice-warning tasty-recipes-db-migration-notice" id="tasty-recipes-db-migration-notice">
	<p class="tasty-recipes-migration-message">
		<strong><?php esc_html_e( 'A database update', 'tasty-recipes-lite' ); ?></strong> <?php esc_html_e( 'is required to ensure WP Tasty continues to work properly.', 'tasty-recipes-lite' ); ?>
	</p>
	<button type="button" class="tasty-recipes-migration-button" id="tasty-recipes-run-migration">
		<span class="tasty-recipes-spinner"></span>
		<span class="tasty-recipes-button-text"><?php esc_html_e( 'Update WP Tasty Database', 'tasty-recipes-lite' ); ?></span>
		<span class="tasty-recipes-loading-text"><?php esc_html_e( 'Updating...', 'tasty-recipes-lite' ); ?></span>
	</button>
</div>

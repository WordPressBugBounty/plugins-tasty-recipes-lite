<?php
/**
 * Template for the editor modal.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="tasty-recipes-modal-container" style="display:none">
	<div class="tasty-recipes-modal wp-core-ui">
		<button type="button" class="tasty-recipes-modal-close"><span class="tasty-recipes-modal-icon"><span class="screen-reader-text"><?php esc_html_e( 'Close Modal', 'tasty-recipes-lite' ); ?></span></span></button>

		<div class="tasty-recipes-modal-content">
			<div class="tasty-recipes-frame wp-core-ui">
				<div class="tasty-recipes-frame-title">
					<h1 class="tasty-recipes-state-creating"><?php echo esc_html( 'Create Recipe' ); ?></h1>
					<h1 class="tasty-recipes-state-editing"><?php echo esc_html( 'Edit Recipe' ); ?></h1>
				</div>
				<div class="tasty-recipes-frame-content" tabindex="-1"></div>

				<div class="tasty-recipes-frame-toolbar">
					<div class="tasty-recipes-toolbar">
						<div class="tasty-recipes-toolbar-primary search-form">
							<button type="button" class="tasty-button tasty-recipes-button tasty-recipes-state-creating tasty-recipes-button-insert"><?php esc_html_e( 'Insert Recipe', 'tasty-recipes-lite' ); ?></button>
							<button type="button" class=" tasty-button tasty-recipes-button tasty-recipes-state-editing tasty-recipes-button-update"><?php esc_html_e( 'Update Recipe', 'tasty-recipes-lite' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="tasty-recipes-modal-backdrop"></div>

</div>

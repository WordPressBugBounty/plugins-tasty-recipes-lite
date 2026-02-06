<?php
/**
 * Dashboard modal template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="tasty-framework-modal tasty-framework-dashboard-modal" id="tasty_framework_license_modal">
	<div class="tasty-framework-modal-container">
		<a href="#" class="tasty-framework-close-modal tasty-framework-modal-x"></a>
		<div class="tasty-framework-modal-loader" id="tasty_framework_modal_loader"></div>
		<div id="tasty_framework_license_modal_form_page">
			<div class="tasty-framework-modal-header">
				<h2>
					<?php esc_html_e( 'Enter License Key', 'tasty-recipes-lite' ); ?>
				</h2>
			</div>
			<div class="tasty-framework-modal-body">
				<div class="tasty-framework-modal-body-message">
					<p><?php echo esc_html__( 'Activating a plugin, requires a valid license. If you have one, please fill in the form below.', 'tasty-recipes-lite' ); ?></p>
				</div>
				<div class="tasty-framework-modal-body-form">
					<div class="tasty-framework-modal-body-form-field">
						<label for="tasty_framework_modal_license_plugin">
							<?php esc_html_e( 'Select Plugin', 'tasty-recipes-lite' ); ?>
						</label>
						<select name="tasty_framework_license_plugin" id="tasty_framework_modal_license_plugin"
								data-default="<?php esc_attr_e( 'Add a valid license first', 'tasty-recipes-lite' ); ?>">
							<option value="">
								<?php esc_html_e( 'Add a valid license first', 'tasty-recipes-lite' ); ?>
							</option>
						</select>
					</div>
					<div class="tasty-framework-modal-body-form-field">
						<label for="tasty_framework_modal_license_key">
							<?php esc_html_e( 'License Key', 'tasty-recipes-lite' ); ?>
						</label>
						<input type="text" name="tasty_framework_license_key" id="tasty_framework_modal_license_key">
					</div>
					<div class="tasty-flex">
						<a href="#" id="tasty_framework_activate_license">
							<?php esc_html_e( 'Activate License', 'tasty-recipes-lite' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<div id="tasty_framework_license_modal_success_page" class="tasty-framework-modal-success-page">
			<div class="tasty-framework-modal-header">
				<h2>
					<?php esc_html_e( 'License Saved', 'tasty-recipes-lite' ); ?>
				</h2>
			</div>
			<div class="tasty-framework-modal-body">
				<div class="tasty-framework-modal-for-not-installed">
					<?php esc_html_e( 'After activating your plugin license, you must download the plugin before you can use it. Here are the steps:', 'tasty-recipes-lite' ); ?>
					<ol class="tasty-numbered-steps">
						<li><?php esc_html_e( 'Download plugin files', 'tasty-recipes-lite' ); ?></li>
						<li><?php esc_html_e( 'Upload plugin', 'tasty-recipes-lite' ); ?></li>
						<li><?php esc_html_e( 'Activate and install it', 'tasty-recipes-lite' ); ?></li>
						<li><?php esc_html_e( 'Enjoy! 🎉', 'tasty-recipes-lite' ); ?></li>
					</ol>
				</div>

				<div class="tasty-framework-modal-for-installed">
					<?php esc_html_e( 'Just one more step and you’ll be ready to start.', 'tasty-recipes-lite' ); ?>
				</div>

				<div class="tasty-framework-modal-for-active">
					<?php esc_html_e( 'Thanks for choosing WP Tasty. Enjoy!', 'tasty-recipes-lite' ); ?>
				</div>

				<div class="tasty-framework-spacer"></div>

				<div class="tasty-flex" id="tasty_framework_modal_buttons">
					<a href="#" id="tasty_framework_download_plugin" class="tasty-framework-modal-for-not-installed">
						<?php esc_html_e( 'Download Plugin', 'tasty-recipes-lite' ); ?>
					</a>

					<a href="#" class="tasty-framework-modal-for-installed"
					id="tasty_framework_activate_plugin">
						<?php esc_html_e( 'Activate Plugin', 'tasty-recipes-lite' ); ?>
					</a>

					<a href="#" class="tasty-framework-close-modal tasty-framework-modal-for-active" id="tasty_framework_modal_close_button">
						<?php esc_html_e( 'Close', 'tasty-recipes-lite' ); ?>
					</a>
				</div>
			</div>

		</div>
	</div>
</section>

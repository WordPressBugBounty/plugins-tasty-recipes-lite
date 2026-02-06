<?php
/**
 * Onboarding Wizard - Never miss an important update step.
 *
 * @package Tasty_Recipes
 */

use Tasty_Recipes\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<section
	id="tasty-recipes-onboarding-consent-tracking-step"
	class="tasty-recipes-onboarding-step tasty-recipes-card-box tasty-shadow tasty-recipes-current"
	data-step-name="<?php echo esc_attr( $step ); ?>"
	>
	<div class="tasty-recipes-card-box-header">
		<?php Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/logo.svg' ), true ); ?>
	</div>

	<div class="tasty-recipes-card-box-content">
		<h2 class="tasty-recipes-card-box-title"><?php esc_html_e( 'Never Miss an Important Update', 'tasty-recipes-lite' ); ?></h2>
		<p class="tasty-recipes-card-box-text">
			<?php esc_html_e( 'Get key updates, tips, and occasional offers to enhance your WordPress experience.', 'tasty-recipes-lite' ); ?>
		</p>
	</div>

	<div class="tasty-recipes-card-box-footer">
		<a href="#" class="tasty-button tasty-highlight tasty-recipes-onboarding-skip-step">
			<?php esc_html_e( 'Skip', 'tasty-recipes-lite' ); ?>
		</a>

		<a href="#" id="tasty-recipes-onboarding-consent-tracking" class="tasty-button tasty-button-pink">
			<?php
			esc_html_e( 'Allow & Continue', 'tasty-recipes-lite' );

			Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/arrow-right-icon.svg' ), true );
			?>
		</a>
	</div>

	<div class="tasty-recipes-card-box-permission">
		<span class="tasty-recipes-collapsible">
			<?php
			esc_html_e( 'Allow Tasty Recipes to', 'tasty-recipes-lite' );

			Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/arrow-bottom-icon.svg' ), true );
			?>
		</span>

		<div class="tasty-recipes-collapsible-content hidden">
			<div class="tasty-recipes-card-box-permission-item">
				<span>
					<?php
					Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/user-icon.svg' ), true );
					?>
				</span>

				<div class="tasty-recipes-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Basic Profile Info', 'tasty-recipes-lite' ); ?></h4>
					<span><?php esc_html_e( 'Your WordPress user full name and email address', 'tasty-recipes-lite' ); ?></span>
				</div>
			</div>

			<div class="tasty-recipes-card-box-permission-item">
				<span>
					<?php
					Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/layout-icon.svg' ), true );
					?>
				</span>

				<div class="tasty-recipes-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Basic Website Info', 'tasty-recipes-lite' ); ?></h4>
					<span><?php esc_html_e( 'Homepage URL & title, WP & PHP versions, site language', 'tasty-recipes-lite' ); ?></span>
				</div>
			</div>

			<div class="tasty-recipes-card-box-permission-item">
				<span>
					<?php
					Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/puzzle-icon.svg' ), true );
					?>
				</span>

				<div class="tasty-recipes-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Basic Tasty Plugin Info', 'tasty-recipes-lite' ); ?></h4>
					<span><?php esc_html_e( 'Current plugin & SDK versions, and if active or uninstalled', 'tasty-recipes-lite' ); ?></span>
				</div>
			</div>

			<div class="tasty-recipes-card-box-permission-item">
				<span>
					<?php
					Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/field-colors-style-icon.svg' ), true );
					?>
				</span>

				<div class="tasty-recipes-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Plugins & Themes List', 'tasty-recipes-lite' ); ?></h4>
					<span><?php esc_html_e( 'Names, slugs, versions, and if active or not', 'tasty-recipes-lite' ); ?></span>
				</div>
			</div>
			
		</div>
	</div>
</section>

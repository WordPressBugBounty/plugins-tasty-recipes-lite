<?php
/**
 * Onboarding Wizard - Success (You're All Set!) Step.
 *
 * @package Tasty_Recipes
 */

use Tasty\Framework\Utils\Url;
use Tasty_Recipes\Settings;
use Tasty_Recipes\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<section id="tasty-recipes-onboarding-success-step"
	class="tasty-recipes-onboarding-step tasty-recipes-card-box tasty-shadow hidden"
	data-step-name="<?php echo esc_attr( $step ); ?>"
	>
	<div class="tasty-recipes-card-box-header">
		<?php Utils::kses( Utils::get_contents( TASTY_FRAMEWORK_PATH_ASSETS . '/images/logo.svg' ), true ); ?>
	</div>

	<div class="tasty-recipes-card-box-content">
		<h2 class="tasty-recipes-card-box-title"><?php esc_html_e( 'You\'re All Set!', 'tasty-recipes-lite' ); ?></h2>
		<p class="tasty-recipes-card-box-text">
			<?php esc_html_e( 'We hope you enjoy using Tasty Recipes!', 'tasty-recipes-lite' ); ?>
		</p>
	</div>

	<div class="tasty-recipes-card-box-footer">
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'about', menu_page_url( Settings::PAGE_SLUG, false ) ) ); ?>" class="tasty-button tasty-button-pink">
			<?php esc_html_e( 'Get Started', 'tasty-recipes-lite' ); ?>
		</a>
	</div>
</section>

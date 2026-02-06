<?php
/**
 * Template for the about tab on the settings page.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="tasty-settings-main">
	<main>
		<section>
			<div class="tasty-flex tasty-about-double-row">
				<div>
					<h2><?php esc_html_e( 'Welcome to Tasty Recipes! 🎉', 'tasty-recipes-lite' ); ?></h2>
					<p><?php esc_html_e( 'Tasty Recipes is a fast, simple, and SEO-optimized recipe plugin for food bloggers. By purchasing Tasty Recipes, you\'re getting access to an impeccable recipe creation experience, superior code quality, and a helpful support team ready to answer all your questions.', 'tasty-recipes-lite' ); ?></p>
					<h2><?php esc_html_e( 'Getting Started', 'tasty-recipes-lite' ); ?></h2>
					<p><?php esc_html_e( 'Tasty Recipes is extremely easy to configure. Visit the settings page to select a recipe card theme, add your Instagram information, and set the default Author URL.', 'tasty-recipes-lite' ); ?></p>
					<p><a class="tasty-button" href="<?php menu_page_url( Tasty_Recipes\Settings::PAGE_SLUG, true ); ?>"><?php esc_html_e( 'Visit Settings', 'tasty-recipes-lite' ); ?></a>
					<h2><?php esc_html_e( 'Convert Recipes', 'tasty-recipes-lite' ); ?></h2>
					<p><?php esc_html_e( 'Tasty Recipes converts recipes from many sources, including WP Recipe Maker, Easy Recipe, Zip Recipes, and more. We recommend converting a single recipe first to try out the conversion process, then converting everything in bulk once you\'re satisfied.', 'tasty-recipes-lite' ); ?></p>
					<p>
					<?php
					printf(
						// translators: Read more about converting recipes individually and converting recipes in bulk.
						esc_html__( 'Read more about %1$s and %2$s.', 'tasty-recipes-lite' ),
						sprintf(
							'<a href="https://www.wptasty.com/convert-single" target="_blank">%s</a>',
							esc_html__( 'converting recipes individually', 'tasty-recipes-lite' )
						),
						sprintf(
							'<a href="https://www.wptasty.com/convert-all" target="_blank">%s</a>',
							esc_html__( 'converting recipes in bulk', 'tasty-recipes-lite' )
						)
					);
					?>
					</p>
				</div>
				<div>
					<img src="<?php echo esc_url( plugins_url( 'assets/images/theme-bold.png', __DIR__ ) ); ?>" class="first" style="max-width:300px" data-pin-nopin="true" alt="Screenshot of the Bold recipe template">
				</div>
			</div>
		</section>
		<section>
			<div class="tasty-flex tasty-about-double-row">
				<div>
					<h2><?php esc_html_e( 'Create New Recipes', 'tasty-recipes-lite' ); ?></h2>
					<p><?php esc_html_e( 'Creating new recipes is easy. Just add a new "Tasty Recipe" block to a post and you\'ll be on your way.', 'tasty-recipes-lite' ); ?></p>
					<p><?php esc_html_e( 'We recommend filling out all the fields for the best SEO potential. Tasty Recipes creates amazing structured data - but it needs the proper information in order to do it!', 'tasty-recipes-lite' ); ?></p>
					<h2><?php esc_html_e( 'Visit Our Documentation', 'tasty-recipes-lite' ); ?></h2>
					<p><?php esc_html_e( 'We pride ourselves on our plugin documentation. If you have questions, head on over to our support site - your question is likely answered there! If not, send us a quick chat and we\'ll be happy to help.', 'tasty-recipes-lite' ); ?></p>
					<p><a class="tasty-button" href="https://support.wptasty.com" target="_blank"><?php esc_html_e( 'Visit Documentation', 'tasty-recipes-lite' ); ?></a></p>
				</div>
				<div>
					<img src="<?php echo esc_url( plugins_url( 'assets/images/tasty-recipes-block.png', __DIR__ ) ); ?>" class="" style="max-width:300px" data-pin-nopin="true" alt="Screenshot of adding a Tasty Recipes block to a post">
				</div>
			</div>
		</section>
		<div>
			<form id="tasty-recipes-onboarding-consent-tracking" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tasty_recipes_onboarding_consent_tracking">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'tasty_recipes_onboarding_consent_tracking' ) ); ?>">
				<label>
					<input
						type="checkbox"
						name="tasty_recipes_onboarding_consent_tracking"
						value="1"
						<?php checked( get_option( Tasty_Recipes\Onboarding_Wizard::USAGE_DATA_OPTION, false ) === 'yes' ); ?>
					>
					<?php esc_html_e( 'Allow WP Tasty to track plugin usage to help us ensure compatibility and simplify our settings.', 'tasty-recipes-lite' ); ?>
				</label>
				<button style="margin-top: 15px;" class="tasty-button-primary" type="submit"><?php esc_html_e( 'Save', 'tasty-recipes-lite' ); ?></button>
			</form>
		</div>
	</main>
</div>

<?php
/**
 * Template for the settings page.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Tasty_Recipes\Designs\Template;

?>

<div class="wrap">
	<div class="wp-header-end"></div>

	<?php if ( 'design' === $active_tab ) : ?>
		<?php
		if ( Template::has_custom_template() ) :
			?>
			<div class="tasty-recipes-custom-template-notice tasty-tab-content"><p><?php esc_html_e( 'Looks like you\'re using a custom recipe card template. To enable design customization options, you\'ll need to remove the custom tasty-recipes.php file from your theme folder.', 'tasty-recipes-lite' ); ?></p></div>
			<?php
		else :
			?>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="tasty-tab-content">
				<?php settings_fields( Tasty_Recipes\Settings::SETTINGS_SECTION_CARD_DESIGN ); ?>
				<div id="tasty-recipes-design-tab" class="tasty-settings-main tasty-lateral-padding"></div>
			</form>
			<?php
		endif;
	elseif ( 'settings' === $active_tab ) : 
		?>
		<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="tasty-tab-content">
			<?php settings_fields( Tasty_Recipes\Settings::SETTINGS_GROUP_CARD ); ?>
			<div id="tasty-recipes-settings-tab" class="tasty-settings-main"></div>
		</form>
		<?php
	elseif ( 'about' === $active_tab ) :
		?>
		<div class="tasty-tab-content tasty-padding">
			<?php include __DIR__ . '/about.php'; ?>
		</div>
		<?php
	elseif ( 'converters' === $active_tab ) :
		?>
		<div class="tasty-tab-content">
			<div id="tasty-recipes-converters-tab" class="tasty-settings-main tasty-lateral-padding"></div>
		</div>
	<?php elseif ( 'debug' === $active_tab ) : ?>
	<div class="tasty-recipes-settings tasty-settings">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">License Key</th>
					<td><input class="regular-text" type="text" readonly value="<?php echo esc_attr( get_option( Tasty_Recipes::LICENSE_KEY_OPTION ) ); ?>"></td>
				</tr>
				<?php do_action( 'tasty_recipes_debug_settings' ); ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>

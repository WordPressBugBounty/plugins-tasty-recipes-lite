<?php
/**
 * Common engine class.
 *
 * @package Tasty/Framework
 */

namespace Tasty\Framework\Utils;

use Tasty\Framework\Main;
use Tasty\Framework\Utils\Url;
use Tasty\Framework\Utils\Vars;

/**
 * Common engine class for reusable components.
 */
class Common {

	/**
	 * Show a message about joining the affiliate program.
	 *
	 * @return void
	 */
	public static function affiliate_banner() {
		$utm_params = array(
			'utm_medium'  => 'banner',
			'utm_content' => Vars::get_param( 'page' ),
		);
		$link       = Url::add_utm_params( 'affiliate', $utm_params );
		?>
		<div class="tasty-banner-grey tasty-flex">
			<img src="<?php echo esc_url( Main::plugin_url( 'assets/images/affiliate-icon.png' ) ); ?>"
				width="24" height="24" data-pin-nopin="true" alt="affiliate program graphic" />
			<p>
				Get started today earning <strong>30% commissions</strong>
				on all referrals with the WP Tasty Affiliate Program!
				<a href="<?php echo esc_url( $link ); ?>" target="_blank"><strong>Join today!</strong></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Show the settings tabs.
	 *
	 * @param array     $tabs             The menu items to show.
	 * @param bool|null $tasty_is_savable Whether the tabs are savable.
	 *
	 * @return void
	 */
	public static function show_tabs( $tabs, $tasty_is_savable = null ) {
		$template = tasty_get_admin_template();
		$template->render(
			'tabs',
			array(
				'tabs'             => $tabs,
				'tasty_is_savable' => $tasty_is_savable,
			),
			true
		);
	}

	/**
	 * Prepare and echo the HTML attributes.
	 *
	 * @param array $attributes Array of attributes to render.
	 *
	 * @return void
	 */
	public static function render_html_attributes( $attributes ) {
		foreach ( $attributes as $key => $value ) {
			printf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}
	}

	/**
	 * Render the save button for the tabs.
	 *
	 * @since x.x
	 * 
	 * @return string The HTML for the save button.
	 */
	public static function get_tabs_save_button() {
		$html  = '<p>';
		$html .= '<button type="submit" name="submit" id="tasty-nav-submit" class="button button-primary">' . esc_html__( 'Save Changes', 'tasty-recipes-lite' ) . '</button>';
		$html .= '</p>';

		return $html;
	}
}

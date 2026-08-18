<?php
/**
 * Expired license grace period admin notice template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template data.
 *
 * @var array $data
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$days_remaining = (int) $data['days_remaining'];
$plugin_name    = (string) $data['plugin_name'];
$renew_url      = (string) $data['renew_url'];
?>
<div class="tasty-notice tasty-flex tasty-license-grace-notice">
	<div class="tasty-license-grace-notice__content">
		<?php
		echo wp_kses_post(
			sprintf(
				// translators: %1$s is the plugin name, %2$s is the number of days remaining.
				_n(
					'<strong>%1$s license expired.</strong> You have %2$s day left before some features are disabled.',
					'<strong>%1$s license expired.</strong> You have %2$s days left before some features are disabled.',
					$days_remaining,
					'tasty'
				),
				esc_html( $plugin_name ),
				esc_html( number_format_i18n( $days_remaining ) )
			)
		);
		?>
	</div>
	<a class="tasty-button tasty-license-grace-notice__button" href="<?php echo esc_url( $renew_url ); ?>" target="_blank" rel="noopener">
		<?php esc_html_e( 'Renew License', 'tasty-recipes-lite' ); ?>
	</a>
</div>

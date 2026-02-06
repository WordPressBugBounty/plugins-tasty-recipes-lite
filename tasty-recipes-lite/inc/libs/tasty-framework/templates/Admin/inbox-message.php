<?php
/**
 * Inbox message template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tasty-framework-inbox-message">
	<div class="tasty-framework-inbox-message-time-ago">
		<?php
		printf(
			/* translators: %s: Time stamp */
			esc_html__( '%s ago', 'tasty-recipes-lite' ),
			esc_html( $data['ago'] )
		);
		?>
	</div>
	<strong class="tasty-framework-inbox-message-title"><?php echo esc_html( $data['subject'] ); ?></strong>
	<div class="tasty-framework-inbox-message-body"><?php echo esc_html( $data['message'] ); ?></div>
	<div><?php echo wp_kses_post( $data['cta'] ); ?></div>
	<?php if ( ! $data['is_dismissed_tab'] ) { ?>
		<a href="<?php echo esc_url( $data['dismiss_url'] ); ?>" class="tasty-framework-inbox-message-dismiss"><?php esc_html_e( 'Dismiss', 'tasty-recipes-lite' ); ?></a>
	<?php } ?>
</div>

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
<div class="tasty-framework-inbox-banner">
	<strong><?php echo esc_html( $data['subject'] ); ?></strong>
	<span class="tasty-framework-inbox-banner-content"><?php echo esc_html( $data['message'] ); ?></span>
	<span><?php echo wp_kses_post( $data['cta'] ); ?></span>
	<a href="<?php echo esc_url( $data['dismiss_url'] ); ?>"><?php esc_html_e( 'Dismiss', 'tasty-recipes-lite' ); ?></a>
</div>

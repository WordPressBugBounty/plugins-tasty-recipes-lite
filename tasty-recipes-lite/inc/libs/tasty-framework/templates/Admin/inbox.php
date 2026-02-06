<?php
/**
 * Inbox template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="tasty-framework-inbox tasty-shadow">
	<div class="tasty-framework-inbox-container">
		<?php
		Tasty\Framework\Utils\Common::show_tabs(
			array(
				array(
					'url'    => admin_url( 'admin.php?page=tasty' ),
					'title'  => __( 'Inbox', 'tasty-recipes-lite' ),
					'active' => ! $data['is_dismissed_tab'],
					'count'  => $data['unread_count'],
				),
				array(
					'url'    => admin_url( 'admin.php?page=tasty&tab=inboxdismissed' ),
					'title'  => __( 'Dismissed', 'tasty-recipes-lite' ),
					'active' => $data['is_dismissed_tab'],
				),
			),
			false
		);
		?>
		<div class="tasty-framework-inbox-messages">
			<?php
			if ( $data['messages'] ) {
				Tasty\Framework\Admin\Inbox\Template::render_messages( $data['messages'] );
			} else {
				?>
				<div class="tasty-framework-inbox-empty">
					<img src="<?php echo esc_url( Tasty\Framework\Main::plugin_url( 'assets/images/icon-cross-bell.svg' ) ); ?>" alt="<?php esc_attr_e( 'No messages', 'tasty-recipes-lite' ); ?>" />
					<p><strong><?php esc_html_e( 'You don\'t have any messages', 'tasty-recipes-lite' ); ?></strong></p>
					<p>
						<?php esc_html_e( 'Good news! No notifications at the moment.', 'tasty-recipes-lite' ); ?>
					</p>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</section>

<?php
/**
 * Dashboard template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tasty-framework-content tasty-framework-dashboard">
	<div class="tasty-framework-container">

		<div class="tasty-framework-dashboard-left">
			<section class="tasty-framework-licensing tasty-shadow">
				<div class="tasty-framework-licensing-header tasty-flex">
					<h2 class="tasty-framework-licensing-header-title">
						<?php esc_html_e( 'Licensing', 'tasty-recipes-lite' ); ?>
					</h2>
					<?php if ( $data['show_add_license'] ) { ?>
					<p>
						<a href="#" class="tasty-framework-licensing-header-add">
							<?php esc_html_e( 'Enter License Key', 'tasty-recipes-lite' ); ?>
						</a>
					</p>
					<?php } ?>
				</div>
				<?php if ( ! empty( $data['plugins'] ) ) { ?>
				<div class="tasty-framework-licensing-rows">

					<?php foreach ( $data['plugins'] as $tasty_plugin ) { ?>
					<div class="tasty-framework-licensing-row">
						<div class="tasty-framework-licensing-row-container tasty-flex">
							<div>
								<h3 class="tasty-framework-licensing-row-title">
									<?php if ( ! empty( $tasty_plugin['actions']['settings'] ) ) { ?>
										<a href="<?php echo esc_url( $tasty_plugin['actions']['settings']['link'] ); ?>">
									<?php } ?>
									<?php echo esc_html( $tasty_plugin['name'] ); ?>
									<?php if ( ! empty( $tasty_plugin['actions']['settings'] ) ) { ?>
										</a>
									<?php } ?>
								</h3>
								<div class="tasty-framework-licensing-row-status">
									<div class="tasty-framework-status-container tasty-framework-status-<?php echo esc_attr( $tasty_plugin['status'] ); ?>">
										<span class="tasty-framework-status-title">
											<?php echo esc_html( $tasty_plugin['status_text'] ); ?>
										</span>
										<?php if ( ! empty( $tasty_plugin['type_text'] ) ) { ?>
										<span class="tasty-framework-status-type">
											<?php echo esc_html( $tasty_plugin['type_text'] ); ?>
										</span>
										<?php } ?>
									</div>
								</div>
							</div>
							<?php if ( $tasty_plugin['message'] ) { ?>
								<div class="tasty-framework-licensing-row-buttons">
									<span class="tasty-msg tasty-msg-error" style="opacity:1">
										<?php echo esc_html( $tasty_plugin['message'] ); ?>
									</span>
								</div>
							<?php } elseif ( 'error' === $tasty_plugin['status'] ) { ?>
								<div class="tasty-framework-licensing-row-buttons">
									<span class="tasty-msg tasty-msg-error" style="opacity:1">
										<?php esc_html_e( 'Refresh the page in 1 minute. If the problem continues, please reach out to support.', 'tasty-recipes-lite' ); ?>
									</span>
								</div>
							<?php } elseif ( ! empty( $tasty_plugin['buttons'] ) ) { ?>
								<div class="tasty-framework-licensing-row-buttons">
									<?php foreach ( $tasty_plugin['buttons'] as $tasty_plugin_button ) { ?>
										<a href="<?php echo esc_url( $tasty_plugin_button['link'] ); ?>" class="tasty-button <?php echo esc_attr( empty( $tasty_plugin_button['class'] ) ? 'tasty-button-secondary' : $tasty_plugin_button['class'] ); ?>"
															<?php
															if ( 'ajax' === $tasty_plugin_button['type'] ) {
																?>
										data-tasty-ajax="true"<?php } else { ?>
											target="_blank" rel="noopener"
										<?php } ?>>
											<?php echo esc_html( $tasty_plugin_button['name'] ); ?>
										</a>
									<?php } ?>
								</div>
							<?php } ?>

							<?php if ( ! empty( $tasty_plugin['actions'] ) ) { ?>
								<div class="tasty-framework-licensing-row-actions">
									<a href="#" class="tasty-framework-licensing-row-actions-trigger"></a>
									<ul class="tasty-framework-licensing-row-actions-list">
										<?php foreach ( $tasty_plugin['actions'] as $tasty_plugin_action ) { ?>
											<li>
												<a href="<?php echo esc_url( $tasty_plugin_action['link'] ); ?>"
													class="<?php echo esc_attr( empty( $tasty_plugin_action['class'] ) ? '' : $tasty_plugin_action['class'] ); ?>"
													<?php if ( 'ajax' === $tasty_plugin_action['type'] ) { ?>
														data-tasty-ajax="true"
													<?php } elseif ( 'external' === $tasty_plugin_action['type'] ) { ?>
													target="_blank" rel="noopener"
													<?php } ?>
												>
													<?php echo esc_html( $tasty_plugin_action['name'] ); ?>
												</a>
											</li>
										<?php } ?>
									</ul>
								</div>
							<?php } ?>
						</div>
					</div>
					<?php } ?>

				</div>
				<?php } ?>
			</section>

			<?php if ( $data['show_banner'] ) { ?>
			<section class="tasty-framework-promo tasty-shadow">
				<div class="tasty-framework-promo-container">
					<div class="tasty-framework-promo-left">
						<div class="tasty-framework-promo-header tasty-flex">
							<h2 class="tasty-framework-promo-header-title">
								<?php esc_html_e( 'all access', 'tasty-recipes-lite' ); ?>
							</h2>
							<span class="tasty-framework-promo-header-badge">
								<?php esc_html_e( 'Save', 'tasty-recipes-lite' ); ?> $<?php echo esc_html( $data['all_access_amount_saved'] ); ?>*
							</span>
						</div>
						<div class="tasty-framework-promo-body">
							<p><?php echo esc_html( $data['all_access_body'] ); ?>*</p>
							<ul class="tasty-framework-promo-body-features tasty-flex">
								<li>Tasty Recipes</li>
								<li>Tasty Roundups</li>
								<li>Tasty Links</li>
								<li>Tasty Pins</li>
								<li>Priority Support</li>
							</ul>
						</div>
					</div>
					<div class="tasty-framework-promo-right">
						<a href="<?php echo esc_url( $data['all_access_cta_url'] ); ?>" target="_blank" rel="noreferrer" class="tasty-button tasty-button-pink">
							<?php echo esc_html( $data['all_access_cta_text'] ); ?>
						</a>
					</div>
				</div>
			</section>
			<?php } ?>
		</div>
		<div class="tasty-framework-dashboard-right"><?php Tasty\Framework\Admin\Inbox\Template::render(); ?></div>
	</div>
</div>

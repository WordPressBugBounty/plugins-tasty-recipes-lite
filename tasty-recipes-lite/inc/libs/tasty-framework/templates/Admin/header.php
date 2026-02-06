<?php
/**
 * Our header template.
 *
 * @package Tasty/Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Tasty\Framework\Utils\Url;
?>
<div class="tasty_framework_loader"></div>

<?php \Tasty\Framework\Admin\Inbox\Template::render_banner(); ?>

<div class="tasty-framework-header tasty-flex">
	<div class="tasty-framework-header-container tasty-flex">
		<div class="tasty-framework-header-logo tasty-flex tasty-no-gap">
			<img src="<?php echo esc_url( $data['logo'] ); ?>" alt="WP Tasty" title="WP Tasty">
			<h1 class="tasty-framework-header-title">
				<span class="tasty-framework-header-title-separator">/</span> <?php echo esc_html( $data['title'] ); ?>
			</h1>
		</div>
		<p class="tasty-framework-header-support">
			<a target="_blank" href="<?php echo esc_url( Url::add_utm_params( 'support', array( 'utm_content' => 'header' ) ) ); ?>">
				<?php esc_html_e( 'Support & Documentation', 'tasty-recipes-lite' ); ?>
			</a>
		</p>
	</div>
</div>

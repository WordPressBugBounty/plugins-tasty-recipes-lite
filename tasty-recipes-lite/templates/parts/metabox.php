<?php
/**
 * Template for admin metabox.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>
<div id='tasty_recipes_ignored_converters'>
	<p class="howto">
		<?php esc_html_e( 'Conversion options for the following products will not be shown:', 'tasty-recipes-lite' ); ?>
	</p>
<?php
foreach ( $dismissed_converters as $converter_key => $converter ) {
	?>
	<p>
		<a href="<?php echo esc_url( $converter['url'] ); ?>" class="button button-primary"><?php echo esc_html( $converter['title'] ); ?></a>
	</p>
	<?php
}
?>
</div>

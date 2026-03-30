<?php
/**
 * Title: Basic Tile
 * Slug: greyd-plugin/dynamic-basic-tile
 * Description: This is a Dynamic Template for a basic tile. It comes with a title, subtitle, text, button and an optional image. You can also link the whole box to whatever target you like. All contents are made dynamic!
 * Categories: greyd-tiles
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"1em","left":"1em","right":"1em","bottom":"1em"},"background":"var(--wp--preset--color--lightest)"},"className":"dyn"} -->
<div class="wp-block-greyd-box dyn">
	<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-and-black-cubes-and-pyramids.webp' ); ?>","tag":""},"greydStyles":{"width":"200px"},"className":"dyn","dynamic_fields":[{"key":"image","title":"Image","enable":true}]} /-->

	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"dyn","dynamic_fields":[{"key":"content","title":"Subtitle","enable":true}]} -->
		<p class="dyn"><?php esc_html_e( 'Overline', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":3,"className":"dyn","dynamic_fields":[{"key":"content","title":"Title","enable":true},{"key":"level","title":"Title H-Tag","enable":true}]} -->
		<h3 class="wp-block-heading dyn"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h3>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
	<p><?php esc_html_e( 'This is a basic tile. It has a title, subtitle, text, a button and an optional image. You can also link the whole box to whatever target you like.', 'greyd_hub' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:greyd/buttons {"className":""} -->
	<div class="wp-block-greyd-buttons">
		<!-- wp:greyd/button {"trigger":{},"content":"<?php esc_html_e( 'Learn more about us', 'greyd_hub' ); ?>","className":"dyn is-style-sec","dynamic_fields":[{"key":"content","title":"Buttontext","enable":true},{"key":"trigger","title":"Buttontrigger","enable":true}]} -->
		<a role="trigger" class="button dyn is-style-sec"><?php esc_html_e( 'Learn more about us', 'greyd_hub' ); ?></a>
		<!-- /wp:greyd/button -->
	</div>
	<!-- /wp:greyd/buttons -->
</div>
<!-- /wp:greyd/box -->
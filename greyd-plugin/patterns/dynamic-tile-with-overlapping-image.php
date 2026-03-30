<?php
/**
 * Title: Tile with overlapping Image
 * Slug: greyd-plugin/dynamic-tile-with-overlapping-image
 * Description: This is a Dynamic Template for a basic tile. It comes with a title, subtitle, text, button and an optional image. You can also link the whole box to whatever target you like. All contents are made dynamic!
 * Categories: greyd-tiles
 * Keywords: 
 * Viewport Width: 600
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:greyd/box {"greydStyles":{"background":"var(--wp--preset--color--lightest)","borderRadius":"2px","margin":{"top":"50px","left":null,"right":null,"bottom":null,"value":{}},"padding":{"top":"1em","left":"1em","right":"1em","bottom":"1em"},"responsive":{"lg":{"padding":{"top":"30px","left":"30px","right":"30px","bottom":"30px"}},"md":{"padding":{"top":"15px","left":"15px","right":"15px","bottom":"15px"}}},"maxWidth":"395px","align":"center","boxShadow":""},"className":""} -->
<div class="wp-block-greyd-box">
	<!-- wp:group {"className":"","layout":{"type":"constrained"},"greydStyles":{"margin":{"top":"-60px","left":null,"right":null,"bottom":"20px"}}} -->
	<div class="wp-block-group">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-spheres-and-pyramids-floating.webp' ); ?>","tag":""},"greydStyles":{"width":"100px","height":"100px","objectFit":"fill"},"align":"center","className":"dyn is-style-rounded","dynamic_fields":[{"key":"image","title":"Image","enable":true}]} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:heading {"textAlign":"center","level":3,"className":"dyn","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
	<h3 class="wp-block-heading has-text-align-center dyn" id="name"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h3>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","className":"dyn","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
	<p class="has-text-align-center dyn"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.', 'greyd_hub' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:greyd/box -->
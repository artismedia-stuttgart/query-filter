<?php
/**
 * Title: Dynamic Image Tile
 * Slug: greyd-plugin/dynamic-image-tile-left
 * Description: Dynamic template for a tile with an image and text and button.
 * Categories: greyd-tiles
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:group {"className":"stacked","layout":{"type":"default"}} -->
<div class="wp-block-group stacked">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"className":""} -->
	<div class="wp-block-columns are-vertically-aligned-center" style="margin-top:0;margin-bottom:0">
		<!-- wp:column {"verticalAlignment":"center","width":"","className":"col-12 col-sm-auto"} -->
		<div class="wp-block-column is-vertically-aligned-center col-12 col-sm-auto">
			<!-- wp:greyd/image {"image":{"id":-1,"url":"","tag":"","type":"file"},"greydStyles":{"width":null,"height":null},"className":"dyn","dynamic_fields":[{"key":"image","title":"Image","enable":true}]} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"className":"col-12 col-sm-auto"} -->
		<div class="wp-block-column is-vertically-aligned-center col-12 col-sm-auto">
			<!-- wp:heading {"level":3,"className":"dyn","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
			<h3 class="dyn"><?php esc_html_e( 'Dynamic Template', 'greyd_hub' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"dyn","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
			<p class="dyn"><?php esc_html_e( 'You can change the content of this component dynamically', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"dyn is-style-alternate","dynamic_fields":[{"key":"text","title":"Buttontext","enable":true},{"key":"trigger","title":"Buttontrigger","enable":true}]} -->
				<div class="wp-block-button dyn is-style-alternate"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Learn more about us', 'greyd_hub' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
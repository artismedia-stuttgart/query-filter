<?php
/**
 * Title: Feature banner with overlapping image
 * Slug: greyd-plugin/hero-feature-banner-with-overlapping-image
 * Description: A feature banner with an overlapping image on the right
 * Categories: greyd-hero
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"full","className":"","style":{"spacing":{"padding":{"top":"80px","right":"20px","bottom":"40px","left":"20px"}},"color":{"gradient":"linear-gradient(360deg,rgba(255,255,255,0) 23%,rgb(5,5,5) 23%)"}},"layout":{"wideSize":"1200px","contentSize":"1000px","type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background:linear-gradient(360deg,rgba(255,255,255,0) 23%,rgb(5,5,5) 23%);padding-top:80px;padding-right:20px;padding-bottom:40px;padding-left:20px">
	<!-- wp:columns {"className":""} -->
	<div class="wp-block-columns">
		<!-- wp:column {"style":{"spacing":{"padding":{"top":"40px"}}}} -->
		<div class="wp-block-column" style="padding-top:40px">
			<!-- wp:heading {"className":"","textColor":"white"} -->
			<h2 class="wp-block-heading has-white-color has-text-color"><?php esc_html_e( 'All WordPress.', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"bottom":"40px"}}},"textColor":"tertiary"} -->
			<h2 class="wp-block-heading has-tertiary-color has-text-color" style="margin-bottom:40px"><?php esc_html_e( 'One Suite.', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base"} -->
			<p class="has-base-color has-text-color has-link-color"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":""} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-fill"} -->
				<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Contact us', 'greyd_hub' ); ?></a></div>
				<!-- /wp:button --></div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"col-12 col-sm-auto"} -->
		<div class="wp-block-column col-12 col-sm-auto">
			<!-- wp:greyd/image {"image":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-cubes-floating.webp' ); ?>","tag":"","type":"file"},"greydStyles":{"width":"100%","height":"500px","objectFit":"cover"},"align":"center","className":"is-resized is-style-rounded-corners"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
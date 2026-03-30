<?php
/**
 * Title: Hero section (image right)
 * Slug: greyd-plugin/hero-section-image-right
 * Description: Simple hero section with an image on the right
 * Categories: greyd-hero
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:columns {"verticalAlignment":"center","className":""} -->
<div class="wp-block-columns are-vertically-aligned-center">
	<!-- wp:column {"verticalAlignment":"center","className":"col-12 col-sm-auto"} -->
	<div class="wp-block-column is-vertically-aligned-center col-12 col-sm-auto">
		<!-- wp:heading {"level":3,"className":"","textColor":"mediumdark","fontSize":"medium"} -->
		<h3 class="wp-block-heading has-mediumdark-color has-text-color has-medium-font-size"><?php esc_html_e( 'Get started', 'greyd_hub' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:heading {"textAlign":"left","level":1,"className":""} -->
		<h1 class="wp-block-heading has-text-align-left" id="the-fast-visual-way-to-understand-your-user"><?php esc_html_e( 'Make more time for the work that matters most', 'greyd_hub' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"left","className":""} -->
		<p class="has-text-align-left"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":""} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-fill"} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Get started', 'greyd_hub' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-sec is-style-outline"} -->
			<div class="wp-block-button is-style-sec is-style-outline"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Learn more about us', 'greyd_hub' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"center","className":"col-12 col-sm-auto"} -->
	<div class="wp-block-column is-vertically-aligned-center col-12 col-sm-auto">
		<!-- wp:greyd/image {"image":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-spheres-and-pyramids-floating.webp' ); ?>","tag":"","type":"file"},"greydStyles":{"width":"100%","height":""},"align":"center","className":""} /-->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
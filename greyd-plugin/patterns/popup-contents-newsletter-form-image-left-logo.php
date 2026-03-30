<?php
/**
 * Title: Popup content with newsletter form placeholder, image on the left and site logo
 * Slug: greyd-plugin/popup-contents-newsletter-form-image-left-logo
 * Description: Form placeholder only available with Greyd Forms plugin.
 * Categories: greyd-popup-contents
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Popup content with newsletter form placeholder, image on the left and site logo', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:group {"align":"wide","className":"","style":{"spacing":{"blockGap":"0","padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"base","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"stretch"}} -->
	<div class="wp-block-group alignwide has-base-background-color has-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical"},"greydStyles":{"minWidth":"50%","maxWidth":"50%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp'); ?>","tag":""},"greydStyles":{"width":"100%","height":"50vh","objectFit":"cover","responsive":{"sm":{"height":"35vh","width":"100%","objectFit":"cover"}},"aspectRatio":"custom"},"className":""} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large","right":"var:preset|spacing|large"}},"dimensions":{"minHeight":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"},"greydStyles":{"width":"%","minWidth":"50%","maxWidth":"50%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
		<div class="wp-block-group" style="min-height:100%;padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large)">
			<!-- wp:group {"className":"","style":{"dimensions":{"minHeight":"%"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"space-between"},"greydStyles":{"minWidth":"320px","responsive":{"sm":{"minWidth":"100%"},"md":{"minWidth":"100%"}}}} -->
			<div class="wp-block-group">
				<!-- wp:group {"className":"","layout":{"type":"constrained","justifyContent":"left"}} -->
				<div class="wp-block-group">
					<!-- wp:site-logo {"align":"center","className":""} /-->

					<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"large"} -->
						<h2 class="wp-block-heading has-text-align-center has-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'This pattern works great in popups', 'greyd_hub' ); ?></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"center","className":""} -->
						<p class="has-text-align-center"><?php esc_html_e( 'Please insert your newsletter form here', 'greyd_hub' ); ?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:greyd/form {"className":""} /-->
				</div>
				<!-- /wp:group -->

				<!-- wp:paragraph {"align":"center","className":"","fontSize":"tiny"} -->
				<p class="has-text-align-center has-tiny-font-size">Lorem ipsum dolor sit <span style="text-decoration: underline;">amet</span></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
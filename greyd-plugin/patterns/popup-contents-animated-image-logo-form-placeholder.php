<?php
/**
 * Title: Popup content with an animated image, site logo and form placeholder
 * Slug: greyd-plugin/popup-contents-animated-image-logo-form-placeholder
 * Description: Form placeholder only available with Greyd Forms plugin.
 * Categories: greyd-popup-contents
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Popup content with animated image, site logo and form placeholder', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:cover {"isUserOverlayColor":true,"minHeight":50,"minHeightUnit":"vh","gradient":"cut-transparent-foreground-1-2","align":"wide","className":"is-style-no-background","style":{"border":{"radius":"16px"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-cover alignwide is-style-no-background" style="border-radius:16px;min-height:50vh">
		<span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-cut-transparent-foreground-1-2-gradient-background"></span>
		<div class="wp-block-cover__inner-container">
			<!-- wp:group {"align":"wide","className":"hover","style":{"spacing":{"blockGap":"var:preset|spacing|medium","padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"stretch"},"greydStyles":{"width":"%","maxWidth":null,"minWidth":"%"}} -->
			<div class="wp-block-group alignwide hover" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"dimensions":{"minHeight":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"top"},"greydStyles":{"width":"%","minWidth":"45%","maxWidth":"50%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
				<div class="wp-block-group" style="min-height:100%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
					<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"top"},"greydStyles":{"minWidth":"320px","responsive":{"sm":{"minWidth":"100%"},"md":{"minWidth":"100%"}}}} -->
					<div class="wp-block-group">
						<!-- wp:group {"className":"is-style-default","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"padding":{"right":"0","left":"0"}}},"textColor":"foreground","layout":{"type":"default"}} -->
						<div class="wp-block-group is-style-default has-foreground-color has-text-color has-link-color" style="padding-right:0;padding-left:0">
							<!-- wp:site-logo {"align":"left","className":"","style":{"color":{}}} /-->

							<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
							<div class="wp-block-group">
								<!-- wp:heading {"textAlign":"left","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"large"} -->
								<h2 class="wp-block-heading has-text-align-left has-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'This pattern works great in popups', 'greyd_hub' ); ?></h2>
								<!-- /wp:heading -->

								<!-- wp:paragraph {"align":"left","className":""} -->
								<p class="has-text-align-left"><?php esc_html_e( 'Please insert your newsletter form here', 'greyd_hub' ); ?></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->

							<!-- wp:greyd/form {"className":""} /-->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null},"dimensions":{"minHeight":"50vh"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp'); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"background","layout":{"type":"flex","orientation":"vertical"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--primary)"},"greydStyles":{"minWidth":"45%","maxWidth":"50%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
				<div class="wp-block-group has-background-background-color has-background" style="min-height:50vh"></div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"align":"center","className":"","fontSize":"tiny"} -->
			<p class="has-text-align-center has-tiny-font-size">Lorem ipsum dolor sit <span style="text-decoration: underline;">amet</span></p>
			<!-- /wp:paragraph -->
		</div>
	</div>
	<!-- /wp:cover -->
</div>
<!-- /wp:group -->
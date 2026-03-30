<?php
/**
 * Title: Header with left-aligned navigation, burger with mobile off-canvas and button
 * Slug: greyd-plugin/header-left-navigation-burger-mobile-offcanvas-button
 * Description: 
 * Categories: greyd-headers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Header with left-aligned navigation, burger with mobile off-canvas and button', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:group {"align":"wide","className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"greydStyles":{"responsive":{"sm":{"width":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"width":40,"className":""} /-->

			<!-- wp:greyd/popover {"hidden":{"xs":false,"sm":false,"md":true,"lg":true},"className":""} -->
				<!-- wp:greyd/popover-button {"variation":"burger","burgerStyles":{"--burger-width":null},"className":""} /-->

				<!-- wp:greyd/popover-popup {"variation":"offcanvas","greydStyles":{"--dialog-margin":"0px 0 0px 0","--dialog-background":"var(--wp--preset--color--primary)","--dialog-color":"var(--wp--preset--color--background)","--dialog-width":"480px","responsive":{"sm":{"--dialog-width":"100%"}}},"className":""} -->
					<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
						<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"}} -->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- /wp:navigation -->
					</div>
					<!-- /wp:group -->

					<!-- wp:greyd/box {"greydStyles":{"--offset-bottom":"var:preset|spacing|medium","--offset-left":"var:preset|spacing|medium"},"variation":"absolute","className":"hidden-lg hidden-md","hide":{"xs":false,"sm":false,"md":true,"lg":true}} -->
					<div class="wp-block-greyd-box hidden-lg hidden-md">
						<!-- wp:greyd/buttons {"className":""} -->
						<div class="wp-block-greyd-buttons">
							<!-- wp:greyd/button {"size":"is-size-small","content":"<?php esc_html_e( 'Button', 'greyd_hub' ); ?>","className":"hidden-sm is-style-trd","hide":{"xs":false,"sm":true,"md":false,"lg":false}} -->
								<a role="trigger" class="button hidden-sm is-style-trd is-size-small"><?php esc_html_e( 'Button', 'greyd_hub' ); ?></a>
							<!-- /wp:greyd/button -->
						</div>
						<!-- /wp:greyd/buttons -->

						<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny","padding":{"top":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
						<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium)">
							<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
							<p class="has-tiny-font-size"><strong><?php esc_html_e( 'Follow us:', 'greyd_hub' ); ?></strong></p>
							<!-- /wp:paragraph -->

							<!-- wp:social-links {"iconColor":"background","iconColorValue":"#f9f7ff","className":"is-style-logos-only","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|tiny"}}}} -->
							<ul class="wp-block-social-links has-icon-color is-style-logos-only">
								<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
								<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
								<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
							</ul>
							<!-- /wp:social-links -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:greyd/box -->
				<!-- /wp:greyd/popover-popup -->
			<!-- /wp:greyd/popover -->

			<!-- wp:group {"className":"hidden-sm hidden-xs","style":{"spacing":{"padding":{"right":"var:preset|spacing|small","left":"var:preset|spacing|small"}}},"layout":{"type":"constrained"},"hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
			<div class="wp-block-group hidden-sm hidden-xs" style="padding-right:var(--wp--preset--spacing--small);padding-left:var(--wp--preset--spacing--small)">
				<!-- wp:navigation {"className":"open-on-hover-click","style":{"layout":{"selfStretch":"fill","flexSize":null}},"hide":{"xs":false,"sm":false,"md":false,"lg":false}} -->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
				<!-- /wp:navigation -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:greyd/buttons {"className":"","style":{"layout":{"selfStretch":"fit","flexSize":null}}} -->
		<div class="wp-block-greyd-buttons">
			<!-- wp:greyd/button {"size":"is-size-small","content":"<?php esc_html_e( 'Button', 'greyd_hub' ); ?>","className":"hidden-xs","hide":{"xs":true,"sm":false,"md":false,"lg":false}} -->
				<a role="trigger" class="button hidden-xs is-size-small"><?php esc_html_e( 'Button', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
<?php
/**
 * Title: Header with custom dropdown navigation and button, custom mobile off canvas
 * Slug: greyd-plugin/header-dropdown-navigation-button-mobile-offcanvas
 * Description: 
 * Categories: greyd-headers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Header with custom dropdown navigation and button, custom mobile off canvas', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:site-logo {"width":40,"className":""} /-->

		<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"className":"hidden-sm hidden-xs open-on-hover-click","fontSize":"base","hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
				<!-- wp:greyd/popover {"hidden":{"xs":true,"sm":true,"md":false,"lg":false},"isNavpoint":true,"className":""} -->
					<!-- wp:greyd/popover-button {"content":"<?php esc_html_e( 'Dropdown', 'greyd_hub' ); ?>","buttonStyle":"link-prim","icon":{"content":"arrow_triangle-down","position":"after","size":"0.75em","margin":"4px"},"size":"small","custom":true,"customStyles":{"fontSize":null},"className":"hidden-sm hidden-xs","hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
					<span><?php esc_html_e( 'Dropdown', 'greyd_hub' ); ?></span><span class="arrow_triangle-down" style="vertical-align:middle;font-size:0.75em;margin-left:4px" aria-hidden="true"></span>
					<!-- /wp:greyd/popover-button -->

					<!-- wp:greyd/popover-popup {"variation":"dropdown","position":"bottom","closeButton":"is-hidden","greydStyles":{"--dialog-padding":"var:preset|spacing|small var:preset|spacing|small var:preset|spacing|small var:preset|spacing|small","--dialog-background":"var(--wp--preset--color--foreground)","--dialog-color":"var(--wp--preset--color--background)","--dialog-width":"320px","--tri-size":"8px"},"className":""} -->
						<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"constrained"}} -->
						<div class="wp-block-group">
							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
					<!-- /wp:greyd/popover-popup -->
				<!-- /wp:greyd/popover -->

				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
			<!-- /wp:navigation -->

			<!-- wp:greyd/buttons {"className":""} -->
			<div class="wp-block-greyd-buttons">
				<!-- wp:greyd/button {"size":"is-size-small","content":"<?php esc_html_e( 'Button', 'greyd_hub' ); ?>","className":"hidden-xs","hide":{"xs":true,"sm":false,"md":false,"lg":false}} -->
				<a role="trigger" class="button hidden-xs is-size-small"><?php esc_html_e( 'Button', 'greyd_hub' ); ?></a>
				<!-- /wp:greyd/button -->
			</div>
			<!-- /wp:greyd/buttons -->

			<!-- wp:greyd/popover {"hidden":{"xs":false,"sm":false,"md":true,"lg":true},"className":""} -->
				<!-- wp:greyd/popover-button {"variation":"burger","className":""} /-->

				<!-- wp:greyd/popover-popup {"variation":"offcanvas","greydStyles":{"--dialog-margin":"0px 0 0px 0","--dialog-background":"var(--wp--preset--color--primary)","--dialog-color":"var(--wp--preset--color--background)","--dialog-width":"480px","responsive":{"sm":{"--dialog-width":"100%"}}},"className":""} -->
					<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
						<!-- wp:paragraph {"className":""} -->
						<p><strong><?php esc_html_e( 'Dropdown Link Items', 'greyd_hub' ); ?></strong></p>
						<!-- /wp:paragraph -->

						<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"constrained"}} -->
						<div class="wp-block-group">
							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":""} -->
							<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->

						<!-- wp:paragraph {"className":""} -->
						<p><strong><?php esc_html_e( 'Navigation Block Menu Items', 'greyd_hub' ); ?></strong></p>
						<!-- /wp:paragraph -->

						<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","fontSize":"base","layout":{"type":"flex","orientation":"vertical"},"hide":{"xs":false,"sm":false,"md":false,"lg":false}} -->
						 	<!-- wp:greyd/popover {"hidden":{"xs":true,"sm":true,"md":false,"lg":false},"isNavpoint":true,"className":""} -->
								<!-- wp:greyd/popover-button {"content":"<?php esc_html_e( 'Dropdown', 'greyd_hub' ); ?>","buttonStyle":"link-prim","icon":{"content":"arrow_triangle-down","position":"after","size":"0.75em","margin":"4px"},"size":"small","custom":true,"customStyles":{"fontSize":null},"className":"hidden-sm hidden-xs","hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
								<span><?php esc_html_e( 'Dropdown', 'greyd_hub' ); ?></span><span class="arrow_triangle-down" style="vertical-align:middle;font-size:0.75em;margin-left:4px" aria-hidden="true"></span>
								<!-- /wp:greyd/popover-button -->

								<!-- wp:greyd/popover-popup {"variation":"dropdown","position":"bottom","closeButton":"is-hidden","greydStyles":{"--dialog-padding":"var:preset|spacing|small var:preset|spacing|small var:preset|spacing|small var:preset|spacing|small","--dialog-background":"var(--wp--preset--color--foreground)","--dialog-color":"var(--wp--preset--color--background)","--dialog-width":"320px","--tri-size":"8px"},"className":""} -->
									<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"constrained"}} -->
									<div class="wp-block-group">
										<!-- wp:paragraph {"className":""} -->
										<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
										<!-- /wp:paragraph -->

										<!-- wp:paragraph {"className":""} -->
										<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
										<!-- /wp:paragraph -->

										<!-- wp:paragraph {"className":""} -->
										<p><?php esc_html_e( 'Link Item', 'greyd_hub' ); ?></p>
										<!-- /wp:paragraph -->
									</div>
									<!-- /wp:group -->
								<!-- /wp:greyd/popover-popup -->
							<!-- /wp:greyd/popover -->

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
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
<?php
/**
 * Title: Header with left-aligned off-canvas menu and social icons
 * Slug: greyd-plugin/header-left-offcanvas-social-menu
 * Description: 
 * Categories: greyd-headers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Header with left-aligned off-canvas menu and social icons', 'greyd_hub' ); ?>"},"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"spacing":{"padding":{"top":"var:preset|spacing|small","bottom":"var:preset|spacing|small"}}},"textColor":"lightest","layout":{"inherit":"true","type":"constrained"}} -->
<div class="wp-block-group has-lightest-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--small);padding-bottom:var(--wp--preset--spacing--small)">
	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|small"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"greydStyles":{"responsive":{"sm":{"width":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/popover {"className":""} -->
				<!-- wp:greyd/popover-button {"variation":"burger","burgerStyles":{"--button-color":"var(--wp--preset--color--foreground)","--burger-stroke":"4px","--burger-width":"4px","--burger-gap":"4px","--burger-color":"var(--wp--preset--color--background)","--button-size":null,"--button-radius":"50%"},"className":""} /-->

				<!-- wp:greyd/popover-popup {"variation":"offcanvas","position":"left","greydStyles":{"--dialog-margin":"0 0 0 0","--dialog-background":"var(--wp--preset--color--foreground)","--dialog-color":"var(--wp--preset--color--lightest)","--dialog-width":"320px","responsive":{"md":{"--dialog-width":""},"sm":{"--dialog-width":"100%"}},"--backdrop-blur":null,"--backdrop-opacity":1,"--dialog-box-shadow":"0px+10px+15px+0px+color-31+0","--dialog-origin":"center left","--dialog-height":null,"--close-size":null,"--close-color":"var(--wp--preset--color--background)"},"className":""} -->
					<!-- wp:group {"className":"","style":{"dimensions":{"minHeight":""},"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
						<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"},"custom":false,"customStyles":{"__experimentalfontAppearance":{"fontStyle":"normal","fontWeight":"700"},"textTransform":"uppercase","fontSize":"3rem"}} -->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
							<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- /wp:navigation -->
					</div>
					<!-- /wp:group -->

					<!-- wp:greyd/box {"greydStyles":{"--offset-left":"var:preset|spacing|medium","--offset-bottom":"var:preset|spacing|medium"},"variation":"fixed","className":""} -->
					<div class="wp-block-greyd-box">
						<!-- wp:social-links {"iconColor":"foreground","iconColorValue":"#0e1111","iconBackgroundColor":"background","iconBackgroundColorValue":"#f9f7ff","size":"has-small-icon-size","className":"hidden-lg hidden-md hidden-sm","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|small","left":"var:preset|spacing|tiny"}},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","justifyContent":"left"},"hide":{"xs":false,"sm":true,"md":true,"lg":true}} -->
						<ul class="wp-block-social-links has-small-icon-size has-icon-color has-icon-background-color hidden-lg hidden-md hidden-sm">
							<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
							<!-- wp:social-link {"url":"#","service":"twitter","className":""} /-->
							<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
							<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
						</ul>
						<!-- /wp:social-links -->
					</div>
					<!-- /wp:greyd/box -->
				<!-- /wp:greyd/popover-popup -->
			<!-- /wp:greyd/popover -->

			<!-- wp:site-logo {"width":40,"className":""} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:social-links {"iconColor":"background","iconColorValue":"#f9f7ff","iconBackgroundColor":"foreground","iconBackgroundColorValue":"#0e1111","size":"has-small-icon-size","className":"hidden-xs","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|small"}},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","justifyContent":"right"},"hide":{"xs":true,"sm":false,"md":false,"lg":false}} -->
		<ul class="wp-block-social-links has-small-icon-size has-icon-color has-icon-background-color hidden-xs">
			<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
			<!-- wp:social-link {"url":"#","service":"twitter","className":""} /-->
			<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
			<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
		</ul>
		<!-- /wp:social-links -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
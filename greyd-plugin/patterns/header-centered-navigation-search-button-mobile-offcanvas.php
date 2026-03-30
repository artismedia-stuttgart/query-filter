<?php
/**
 * Title: Header with centered navigation and search button, mobile off-canvas
 * Slug: greyd-plugin/header-centered-navigation-search-button-mobile-offcanvas
 * Description: 
 * Categories: greyd-headers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Header with centered navigation and search button, mobile off-canvas', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:site-logo {"width":40,"className":""} /-->

		<!-- wp:navigation {"className":"hidden-sm hidden-xs open-on-hover-click","hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
			<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
		<!-- /wp:navigation -->

		<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/popover {"className":""} -->
				<!-- wp:greyd/popover-button {"icon":{"content":"icon_search","position":"after","size":"100%","margin":"0px"},"custom":true,"customStyles":{"padding":{"top":"8px","right":"8px","bottom":"8px","left":"8px"},"borderRadius":"50px"},"align":"right","className":""} -->
				<span></span><span class="icon_search" style="vertical-align:middle;font-size:100%;margin-left:0px" aria-hidden="true"></span>
				<!-- /wp:greyd/popover-button -->

				<!-- wp:greyd/popover-popup {"variation":"dropdown","position":"left","closeButton":"is-hidden","greydStyles":{"--dialog-width":"320px","--dialog-background":"var(--wp--preset--color--transparent)","--dialog-border":null,"--dialog-radius":"0px","--dialog-padding":"0px 0 0px 0","--dialog-margin":null,"responsive":{"md":{"--dialog-width":"320px"}},"--dialog-color":"var(--wp--preset--color--lightest)","--dialog-box-shadow":"0px+10px+15px+0px+color-31+0"},"className":""} -->
					<!-- wp:greyd/search {"greydStyles":{"gap":"0px","flex-wrap":"nowrap"},"className":"","greydAnim":{"action":"custom","preset":"fadeOut","from":"margin: 0px;","to":"","origin":"center center","event":"hover","parent":"","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease"}} -->
					<form class="wp-block-greyd-search greyd-search-form gs_<?php echo $_greyd_class; ?> row" method="get" role="search">
						<!-- wp:greyd/search-input {"labelStyles":{"fontSize":"0px"},"greydStyles":{"width":"100%"},"customStyles":{"borderRadius":"50px","color":"var(--wp--preset--color--foreground)","hover":{"color":"var(--wp--preset--color--foreground)","background":"var(--wp--preset--color--lightest)"},"background":"var(--wp--preset--color--lightest)"},"custom":true,"className":"is-style-sec"} /-->
						 <style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { gap: 0px; flex-wrap: nowrap; } </style>
					</form>
					<!-- /wp:greyd/search -->
				<!-- /wp:greyd/popover-popup -->
			<!-- /wp:greyd/popover -->

			<!-- wp:greyd/popover {"hidden":{"xs":false,"sm":false,"md":true,"lg":true},"className":""} -->
				<!-- wp:greyd/popover-button {"variation":"burger","className":""} /-->

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
							<!-- wp:greyd/button {"size":"is-size-small","content":"Button","className":"hidden-sm is-style-trd","hide":{"xs":false,"sm":true,"md":false,"lg":false}} -->
							<a role="trigger" class="button hidden-sm is-style-trd is-size-small">Button</a>
							<!-- /wp:greyd/button -->
						</div>
						<!-- /wp:greyd/buttons -->

						<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny","padding":{"top":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"}} -->
						<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium)">
							<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
							<p class="has-tiny-font-size"><strong>Follow us:</strong></p>
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
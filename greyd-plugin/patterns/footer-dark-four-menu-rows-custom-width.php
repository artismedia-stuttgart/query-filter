<?php
/**
 * Title: Dark footer built with four menu rows and custom width control
 * Slug: greyd-plugin/footer-dark-four-menu-rows-custom-width
 * Description:
 * Categories: greyd-footers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Dark footer built with four menu rows and custom width control', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"foreground","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large)">
	<!-- wp:group {"align":"wide","className":"","style":{"spacing":{"blockGap":"var:preset|spacing|large"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"stretch","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","verticalAlignment":"space-between"},"greydStyles":{"responsive":{"md":{"width":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"width":40,"className":"","style":{"color":{"duotone":"var:preset|duotone|background-foreground"}},"greydStyles":{"width":"40px"}} /-->

			<!-- wp:social-links {"iconColor":"foreground","iconColorValue":"#0e1111","iconBackgroundColor":"background","iconBackgroundColorValue":"#f9f7ff","size":"has-small-icon-size","className":"hidden-sm hidden-xs","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|small"}},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","justifyContent":"right"},"hide":{"xs":true,"sm":true,"md":false,"lg":false}} -->
			<ul class="wp-block-social-links has-small-icon-size has-icon-color has-icon-background-color hidden-sm hidden-xs">
				<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
				<!-- wp:social-link {"url":"#","service":"twitter","className":""} /-->
				<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
				<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
			</ul>
			<!-- /wp:social-links -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"top"},"greydStyles":{"width":null,"maxWidth":"80%","responsive":{"md":{"maxWidth":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical"},"greydStyles":{"responsive":{"md":{"width":"100%"}}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"className":""} -->
				<p><strong><?php esc_html_e( 'Title', 'greyd_hub' ); ?></strong></p>
				<!-- /wp:paragraph -->

				<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"}} -->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
				<!-- /wp:navigation -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"bottom","justifyContent":"space-between"},"greydStyles":{"maxWidth":"60%","responsive":{"md":{"maxWidth":""},"sm":{"maxWidth":"100%"}}}} -->
			<div class="wp-block-group">
				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p><strong><?php esc_html_e( 'Title', 'greyd_hub' ); ?></strong></p>
					<!-- /wp:paragraph -->

					<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"}} -->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- /wp:navigation -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group">
					<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"}} -->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- /wp:navigation -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p><strong><?php esc_html_e( 'Title', 'greyd_hub' ); ?></strong></p>
					<!-- /wp:paragraph -->

					<!-- wp:navigation {"overlayMenu":"never","className":"open-on-hover-click","layout":{"type":"flex","orientation":"vertical"}} -->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
						<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Menu Item', 'greyd_hub' ); ?>","url":"#","className":""} /-->
					<!-- /wp:navigation -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"tiny"} -->
		<p class="has-tiny-font-size" style="font-style:normal;font-weight:700">
			<span data-tag="symbol" data-params="{&quot;symbol&quot;:&quot;copyright&quot;}" class="is-tag">Symbol (copyright, arrow...)</span> 
			<span data-tag="now" data-params="{&quot;format&quot;:&quot;Y&quot;,&quot;customFormat&quot;:null}" class="is-tag">Current date</span> 
			<span data-tag="site-title" data-params="" class="is-tag">Site title</span>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:social-links {"iconColor":"foreground","iconColorValue":"#0e1111","iconBackgroundColor":"background","iconBackgroundColorValue":"#f9f7ff","size":"has-small-icon-size","className":"hidden-lg hidden-md","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|small"}},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","justifyContent":"right"},"hide":{"xs":false,"sm":false,"md":true,"lg":true}} -->
		<ul class="wp-block-social-links has-small-icon-size has-icon-color has-icon-background-color hidden-lg hidden-md">
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
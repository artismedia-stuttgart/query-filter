<?php
/**
 * Title: Popup content with newsletter form placeholder and animated background image
 * Slug: greyd-plugin/popup-contents-newsletter-form-animated-bg-image
 * Description: Form placeholder only available with Greyd Forms plugin.
 * Categories: greyd-popup-contents
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Popup content with newsletter form placeholder and animated background image', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:group {"align":"wide","className":"hover","style":{"spacing":{"blockGap":"0","padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"border":{"width":"2px"}},"backgroundColor":"primary","borderColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group alignwide hover has-border-color has-foreground-border-color has-primary-background-color has-background" style="border-width:2px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:cover {"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp'); ?>","dimRatio":40,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":50,"minHeightUnit":"vh","className":"is-style-no-background","layout":{"type":"constrained"},"greydBackgroundAnim":{"action":"scale","preset":"fadeOut","from":1,"to":1.1,"origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":3000,"delay":0,"timing":"ease-in-out"}} -->
		<div class="wp-block-cover is-style-no-background" style="min-height:50vh">
			<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-40 has-background-dim"></span>
			<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp'); ?>" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"large"} -->
					<h2 class="wp-block-heading has-text-align-center has-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'This pattern works great in popups', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"center","className":""} -->
					<p class="has-text-align-center"><?php esc_html_e( 'Please insert your newsletter form here', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:greyd/form {"className":""} /-->
				</div>
				<!-- /wp:group -->
			</div>
		</div>
		<!-- /wp:cover -->

		<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"dimensions":{"minHeight":"100%"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"foreground","textColor":"background","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"},"greydClass":"gs_POCAnn","greydStyles":{"width":"%","minWidth":null,"maxWidth":null,"responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
		<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="min-height:100%;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
			<!-- wp:paragraph {"align":"center","className":"","fontSize":"tiny"} -->
			<p class="has-text-align-center has-tiny-font-size">Lorem ipsum dolor sit <span style="text-decoration: underline;">amet</span></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
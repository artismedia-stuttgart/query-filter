<?php
/**
 * Title: Animated simple box, dark
 * Slug: greyd-plugin/dt-cards-animated-simple-box-dark
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"categories":["greyd-content"],"patternName":"greyd-theme/content-simple-box-dark","name":"<?php esc_html_e( 'Animated simple box, dark', 'greyd_hub' ); ?>"},"className":"hover","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"backgroundColor":"foreground","textColor":"lightest","layout":{"type":"default"}} -->
<div class="wp-block-group hover has-lightest-color has-foreground-background-color has-text-color has-background has-link-color" style="border-radius:8px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:cover {"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-placeholder-image-black-300x300.svg' ); ?>","dimRatio":30,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":240,"minHeightUnit":"px","contentPosition":"top right","className":"","style":{"color":{"duotone":"var:preset|duotone|foreground-background"},"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"border":{"radius":{"topLeft":"8px","topRight":"8px"}}},"greydBackgroundAnim":{"action":"scale","preset":"fadeOut","from":1,"to":1.3,"origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":500,"delay":0,"timing":"ease"}} -->
	<div class="wp-block-cover has-custom-content-position is-position-top-right" style="border-top-left-radius:8px;border-top-right-radius:8px;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium);min-height:240px">
		<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-30 has-background-dim"></span>
		<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-placeholder-image-black-300x300.svg' ); ?>" data-object-fit="cover"/>
		<div class="wp-block-cover__inner-container">
			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"padding":{"top":"4px","bottom":"4px","left":"8px","right":"8px"}},"border":{"radius":"4px"}},"backgroundColor":"background","textColor":"foreground","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-foreground-color has-background-background-color has-text-color has-background has-link-color" style="border-radius:4px;padding-top:4px;padding-right:8px;padding-bottom:4px;padding-left:8px">
				<!-- wp:paragraph {"className":""} -->
				<p>Innovate</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
	</div>
	<!-- /wp:cover -->

	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)"><!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'Your Success, Our Priority', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Optimize with Greyd', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading --></div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'Transform your online presence with Greyd.Suite. Elevate efficiency, streamline processes, and achieve unparalleled design excellence.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"","greydAnim":{"action":"show","preset":"fadeInRight","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"90%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-alternate"} -->
			<div class="wp-block-button is-style-alternate">
				<a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Get Started', 'greyd_hub' ); ?></a>
			</div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
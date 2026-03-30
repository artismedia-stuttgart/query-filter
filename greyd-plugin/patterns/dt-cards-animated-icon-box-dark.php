<?php
/**
 * Title: Animated icon box, dark
 * Slug: greyd-plugin/dt-cards-animated-icon-box-dark
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated icon box, dark', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large","right":"var:preset|spacing|large"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"backgroundColor":"dark","textColor":"lightest","layout":{"type":"default"}} -->
<div class="wp-block-group has-lightest-color has-dark-background-color has-text-color has-background has-link-color" style="border-radius:8px;padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large)">
	<!-- wp:image {"width":"60px","height":"auto","sizeSlug":"full","linkDestination":"none","align":"center","className":"","style":{"color":{"duotone":"var:preset|duotone|foreground-background"}}} -->
	<figure class="wp-block-image aligncenter size-full is-resized">
		<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-white.svg' ); ?>" alt="" style="width:60px;height:auto"/>
	</figure>
	<!-- /wp:image -->

	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"},"greydAnim":{"action":"show","preset":"fadeInUp","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"90%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":3,"className":""} -->
		<h3 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Elevate your WordPress business', 'greyd_hub' ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","className":""} -->
		<p class="has-text-align-center"><?php esc_html_e( 'The Greyd Theme is the perfect starting point to scale up your WordPress business.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"align":"center","className":"","greydAnim":{"action":"show","preset":"fadeInUp","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"90%","end":"50%","reverse":true,"duration":200,"delay":200,"timing":"ease"}} -->
	<p class="has-text-align-center"><a href="https://greyd.io/" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Learn more about the theme →', 'greyd_hub' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
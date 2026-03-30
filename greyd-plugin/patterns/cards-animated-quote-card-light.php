<?php
/**
 * Title: Animated quote card, light
 * Slug: greyd-plugin/cards-animated-quote-card-light
 * Description: 
 * Categories: greyd-cards
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated quote card, light', 'greyd_hub' ); ?>"},"className":"hover","style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group hover has-base-background-color has-background" style="border-radius:8px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|small","right":"var:preset|spacing|small"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--small);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--small)">
		<!-- wp:quote {"className":"is-style-plain"} -->
		<blockquote class="wp-block-quote is-style-plain">
			<!-- wp:paragraph {"className":"","style":{"typography":{"lineHeight":"0.3","fontStyle":"normal","fontWeight":"700","fontSize":"5rem"}},"greydAnim":{"action":"show","preset":"fadeInLeft","from":"","to":"","origin":"center center","event":"parentHover","parent":".hover","start":"70%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
			<p style="font-size:5rem;font-style:normal;font-weight:700;line-height:0.3">“</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Designing for the web is not just about pixels; it is about people, accessibility, and crafting sustainable digital experiences that stand the test of time.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"align":"right","className":"","style":{"typography":{"lineHeight":"0","fontStyle":"normal","fontWeight":"700","fontSize":"5rem"}},"greydAnim":{"action":"show","preset":"fadeInRight","from":"","to":"","origin":"center center","event":"parentHover","parent":".hover","start":"70%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
			<p class="has-text-align-right" style="font-size:5rem;font-style:normal;font-weight:700;line-height:0">”</p>
			<!-- /wp:paragraph -->

			<cite><?php esc_html_e( '15th February 2024', 'greyd_hub' ); ?></cite>
		</blockquote>
		<!-- /wp:quote --></div>
	<!-- /wp:group -->

	<!-- wp:cover {"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-placeholder-image-white-300x300.svg' ); ?>","dimRatio":50,"overlayColor":"lightest","isUserOverlayColor":true,"minHeight":313,"minHeightUnit":"px","contentPosition":"bottom left","isDark":false,"className":"","style":{"color":{"duotone":"var:preset|duotone|foreground-background"},"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"elements":{"link":{"color":{"text":"var:preset|color|darkest"}}},"border":{"radius":{"bottomLeft":"8px","bottomRight":"8px"}}},"textColor":"darkest"} -->
	<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-left has-darkest-color has-text-color has-link-color" style="border-bottom-left-radius:8px;border-bottom-right-radius:8px;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium);min-height:313px">
		<span aria-hidden="true" class="wp-block-cover__background has-lightest-background-color has-background-dim"></span>
		<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-placeholder-image-white-300x300.svg' ); ?>" data-object-fit="cover"/>
		<div class="wp-block-cover__inner-container">
			<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0","padding":{"right":"var:preset|spacing|small","left":"var:preset|spacing|small"}}},"layout":{"type":"flex","flexWrap":"nowrap","orientation":"vertical"},"greydAnim":{"action":"show","preset":"fadeInUp","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"90%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
			<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--small);padding-left:var(--wp--preset--spacing--small)">
				<!-- wp:heading {"className":""} -->
				<h2 class="wp-block-heading"><?php esc_html_e( 'Jane Doe', 'greyd_hub' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"","fontSize":"medium","fontFamily":"heading"} -->
				<p class="has-heading-font-family has-medium-font-size"><strong><?php esc_html_e( 'Web Advocate', 'greyd_hub' ); ?></strong></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
	</div>
	<!-- /wp:cover -->
</div>
<!-- /wp:group -->
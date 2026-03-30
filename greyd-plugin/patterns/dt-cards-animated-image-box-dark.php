<?php
/**
 * Title: Animated image box, dark
 * Slug: greyd-plugin/dt-cards-animated-image-box-dark
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:cover {"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-and-black-cubes-and-pyramids.webp' ); ?>","dimRatio":80,"customOverlayColor":"#adadad","isUserOverlayColor":true,"focalPoint":{"x":0.29,"y":1},"minHeight":287,"minHeightUnit":"px","gradient":"primary-to-foreground","contentPosition":"center center","isDark":false,"metadata":{"name":"<?php esc_html_e( 'Animated image box, dark', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"border":{"radius":"8px"},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"color":[]},"textColor":"lightest","greydBackgroundAnim":{"action":"scale","preset":"fadeOut","from":1,"to":1.2,"origin":"center center","event":"hover","parent":"","start":"50%","end":"50%","reverse":false,"duration":3000,"delay":0,"timing":"ease"}} -->
<div class="wp-block-cover is-light has-lightest-color has-text-color has-link-color" style="border-radius:8px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium);min-height:287px">
	<span aria-hidden="true" class="wp-block-cover__background has-background-dim-80 has-background-dim wp-block-cover__gradient-background has-background-gradient has-primary-to-foreground-gradient-background" style="background-color:#adadad"></span>
	<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-and-black-cubes-and-pyramids.webp' ); ?>" style="object-position:29% 100%" data-object-fit="cover" data-object-position="29% 100%"/>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group">
			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|darkest"}}},"spacing":{"padding":{"top":"4px","bottom":"4px","left":"8px","right":"8px"}},"border":{"radius":"4px"}},"backgroundColor":"lightest","textColor":"darkest","layout":{"type":"default"}} -->
			<div class="wp-block-group has-darkest-color has-lightest-background-color has-text-color has-background has-link-color" style="border-radius:4px;padding-top:4px;padding-right:8px;padding-bottom:4px;padding-left:8px">
				<!-- wp:paragraph {"className":""} -->
				<p><?php esc_html_e( 'Creative', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:spacer {"height":"100px","className":""} -->
		<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"className":""} -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Join the Excitement', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Be part of an extraordinary gathering. Immerse yourself in unforgettable experiences, connect with like-minded design enthusiasts, and create lasting memories.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->
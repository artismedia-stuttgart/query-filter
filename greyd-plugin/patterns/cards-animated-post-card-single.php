<?php
/**
 * Title: Animated post card, single
 * Slug: greyd-plugin/cards-animated-post-card-single
 * Description: 
 * Categories: greyd-cards
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated post card, single', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"blockGap":"0","padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"border":{"radius":"4px"}},"backgroundColor":"lightest","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
<div class="wp-block-group has-lightest-background-color has-background" style="border-radius:4px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"border":{"radius":{"topLeft":"4px","topRight":"4px","bottomLeft":"0px","bottomRight":"0px"}}},"gradient":"primary-to-background","layout":{"type":"default"}} -->
	<div class="wp-block-group has-primary-to-background-gradient-background has-background" style="border-top-left-radius:4px;border-top-right-radius:4px;border-bottom-left-radius:0px;border-bottom-right-radius:0px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:cover {"useFeaturedImage":true,"dimRatio":50,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":200,"minHeightUnit":"px","contentPosition":"top right","isDark":false,"className":"is-style-no-background","style":{"border":{"radius":{"topLeft":"4px","topRight":"4px","bottomLeft":"0px","bottomRight":"0px"}},"spacing":{"padding":{"top":"var:preset|spacing|small","bottom":"var:preset|spacing|small","left":"var:preset|spacing|small","right":"var:preset|spacing|small"}},"layout":{"selfStretch":"fit","flexSize":null}}} -->
		<div class="wp-block-cover is-light has-custom-content-position is-position-top-right is-style-no-background" style="border-top-left-radius:4px;border-top-right-radius:4px;border-bottom-left-radius:0px;border-bottom-right-radius:0px;padding-top:var(--wp--preset--spacing--small);padding-right:var(--wp--preset--spacing--small);padding-bottom:var(--wp--preset--spacing--small);padding-left:var(--wp--preset--spacing--small);min-height:200px">
			<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim"></span>
			<div class="wp-block-cover__inner-container">
				<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"spacing":{"padding":{"top":"1px","bottom":"1px","left":"6px","right":"6px"}},"border":{"radius":"4px"}},"backgroundColor":"foreground","textColor":"lightest","fontSize":"tiny","layout":{"type":"flex","flexWrap":"nowrap"},"greydAnim":{"action":"show","preset":"fadeInUp","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"90%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease"}} -->
				<div class="wp-block-group has-lightest-color has-foreground-background-color has-text-color has-background has-link-color has-tiny-font-size" style="border-radius:4px;padding-top:1px;padding-right:6px;padding-bottom:1px;padding-left:6px">
					<!-- wp:post-date {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","fontSize":"tiny"} /-->
				</div>
				<!-- /wp:group -->
			</div>
		</div>
		<!-- /wp:cover -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"margin":{"top":"0","bottom":"0"},"blockGap":"var:preset|spacing|small"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
		<!-- wp:post-title {"isLink":true,"className":"","fontSize":"base"} /-->

		<!-- wp:post-excerpt {"moreText":"","showMoreOnNewLine":false,"excerptLength":40,"className":""} /-->

		<!-- wp:group {"className":"","style":{"spacing":{"margin":{"top":"var:preset|spacing|small","bottom":"0"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--small);margin-bottom:0">
			<!-- wp:read-more {"content":"Read more","className":"","style":{"border":{"radius":"4px"},"spacing":{"padding":{"top":"var:preset|spacing|small","bottom":"var:preset|spacing|small","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"margin":{"bottom":"0"}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"backgroundColor":"base","fontSize":"small"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
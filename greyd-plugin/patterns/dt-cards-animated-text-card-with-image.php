<?php
/**
 * Title: Animated text card with image
 * Slug: greyd-plugin/dt-cards-animated-text-card-with-image
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated text card with image', 'greyd_hub' ); ?>"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"blockGap":"var:preset|spacing|medium","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"radius":"16px","width":"1px"}},"backgroundColor":"lightest","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"},"trigger":{"type":"event","params":{"name":"hide","hover":true}}} -->
<div class="wp-block-group hover has-foreground-color has-lightest-background-color has-text-color has-background has-link-color" style="border-width:1px;border-radius:16px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium","padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"default"},"trigger":{"type":"event","params":{"name":"show","hover":true}}} -->
	<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","tag":""},"greydStyles":{"height":"128px","objectFit":"cover","width":"100%"},"className":"is-style-rounded-corners","style":{"spacing":{"margin":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}}} /-->

		<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"},"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
			<!-- wp:heading {"className":"","fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"","layout":{"type":"default"},"trigger_event":{"onload":"hide","actions":[{"name":"show","action":"show"},{"name":"hide","action":"hide"}]}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
				<p class="has-tiny-font-size"><?php esc_html_e( 'Designing for the web is not just about pixels; it is about people, accessibility, and crafting sustainable digital experiences that stand the test of time.', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
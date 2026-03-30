<?php
/**
 * Title: Animated team card with image change
 * Slug: greyd-plugin/cards-animated-team-card-image-change
 * Description: 
 * Categories: greyd-cards
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated team card with image change', 'greyd_hub' ); ?>"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"blockGap":"var:preset|spacing|small","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"radius":"24px"},"dimensions":{"minHeight":"640px"}},"textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"bottom"},"trigger":{"type":"event","params":{"name":"hide","hover":true}}} -->
<div class="wp-block-group hover has-foreground-color has-text-color has-link-color" style="border-radius:24px;min-height:640px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"border":{"radius":"24px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/example-team-member-1.jpg' ); ?>","source":"file","title":""},"backgroundSize":"cover","backgroundPosition":"51% 30%"},"dimensions":{"minHeight":"100%"},"layout":{"selfStretch":"fill","flexSize":null},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"trigger":{"type":"event","params":{"name":"show","hover":true}}} -->
	<div class="wp-block-group" style="border-radius:24px;min-height:100%;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:group {"className":"","style":{"border":{"radius":"24px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/example-team-member-2.jpg' ); ?>","source":"file","title":""},"backgroundSize":"cover","backgroundPosition":"53% 57.99999999999999%"},"dimensions":{"minHeight":"100%"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical"},"trigger_event":{"onload":"","actions":[{"name":"show","action":"fadeIn"},{"name":"hide","action":"fadeOut"}]}} -->
		<div class="wp-block-group" style="border-radius:24px;min-height:100%"></div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
		<p class="has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Name', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
		<p class="has-tiny-font-size"><?php esc_html_e( 'Position', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
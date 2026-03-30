<?php
/**
 * Title: Animated team card with text reveal
 * Slug: greyd-plugin/dt-cards-animated-team-card-text-reveal
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"Animated Team Card, Text reveal"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"blockGap":"0","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"radius":"24px"},"dimensions":{"minHeight":"640px"}},"backgroundColor":"lightest","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"bottom"},"trigger":{"type":"event","params":{"name":"hide","hover":true}}} -->
<div class="wp-block-group hover has-foreground-color has-lightest-background-color has-text-color has-background has-link-color" style="border-radius:24px;min-height:640px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"border":{"radius":"24px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/example-team-member-2.jpg' ); ?>","source":"file","title":""},"backgroundSize":"cover","backgroundPosition":"53% 57.99999999999999%"},"dimensions":{"minHeight":"100%"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical"},"trigger":{"type":"event","params":{"name":"show","hover":true}}} -->
	<div class="wp-block-group" style="border-radius:24px;min-height:100%"></div>
	<!-- /wp:group -->

	<!-- wp:greyd/box {"variation":"absolute","className":""} -->
	<div class="wp-block-greyd-box">
		<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"blockGap":"var:preset|spacing|small"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
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

			<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical"},"trigger_event":{"onload":"hide","actions":[{"name":"show","action":"slideDown"},{"name":"hide","action":"slideUp"}]}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}}},"textColor":"foreground","fontSize":"tiny","greydStyles":{"-webkit-line-clamp":"3"}} -->
				<p class="has-foreground-color has-text-color has-link-color has-tiny-font-size">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:social-links {"iconColor":"background","iconColorValue":"#f9f7ff","iconBackgroundColor":"foreground","iconBackgroundColorValue":"#0e1111","className":"","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|tiny"}}}} -->
			<ul class="wp-block-social-links has-icon-color has-icon-background-color">
				<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
				<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
				<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
			</ul>
			<!-- /wp:social-links --></div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:greyd/box -->
	</div>
<!-- /wp:group -->
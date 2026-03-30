<?php
/**
 * Title: Team card
 * Slug: greyd-plugin/cards-team-card
 * Description: 
 * Categories: greyd-cards
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Team card', 'greyd_hub' ); ?>"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"blockGap":"0","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"radius":"24px"}},"backgroundColor":"lightest","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"hover","parent":"","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--secondary)"}} -->
<div class="wp-block-group hover has-foreground-color has-lightest-background-color has-text-color has-background has-link-color" style="border-radius:24px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"border":{"radius":{"topLeft":"24px","topRight":"24px","bottomLeft":"0px","bottomRight":"0px"}},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/example-team-member-2.jpg' ); ?>","source":"file","title":""},"backgroundSize":"cover","backgroundPosition":"53% 57.99999999999999%"},"dimensions":{"minHeight":"320px"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group" style="border-top-left-radius:24px;border-top-right-radius:24px;border-bottom-left-radius:0px;border-bottom-right-radius:0px;min-height:320px"></div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"blockGap":"var:preset|spacing|small"},"layout":{"selfStretch":"fill","flexSize":null},"border":{"radius":{"bottomLeft":"24px","bottomRight":"24px"}}},"layout":{"type":"flex","orientation":"vertical"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--secondary)"}} -->
	<div class="wp-block-group" style="border-bottom-left-radius:24px;border-bottom-right-radius:24px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)"><!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
		<p class="has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Name', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
		<p class="has-tiny-font-size"><?php esc_html_e( 'Position', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph --></div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}}},"textColor":"foreground","fontSize":"tiny"} -->
		<p class="has-foreground-color has-text-color has-link-color has-tiny-font-size">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
		<!-- /wp:paragraph -->

		<!-- wp:social-links {"iconColor":"background","iconColorValue":"#f9f7ff","iconBackgroundColor":"foreground","iconBackgroundColorValue":"#0e1111","className":"","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|tiny"}}}} -->
		<ul class="wp-block-social-links has-icon-color has-icon-background-color">
			<!-- wp:social-link {"url":"#","service":"instagram","className":""} /-->
			<!-- wp:social-link {"url":"#","service":"linkedin","className":""} /-->
			<!-- wp:social-link {"url":"#","service":"facebook","className":""} /-->
		</ul>
		<!-- /wp:social-links -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
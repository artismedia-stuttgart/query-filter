<?php
/**
 * Title: Sticky container with color change animation (sticky box)
 * Slug: greyd-plugin/header-sticky-container-color-change
 * Description: 
 * Categories: greyd-headers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:greyd/box {"variation":"sticky","className":"","metadata":{"name":"<?php esc_html_e( 'Sticky container with color change animation (sticky box)', 'greyd_hub' ); ?>"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"isSticky","parent":"","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"var(--wp--preset--color--background)","background":"var(--wp--preset--color--foreground)"}} -->
<div class="wp-block-greyd-box">
	<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Placeholder', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|tiny","bottom":"var:preset|spacing|tiny"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--tiny);padding-bottom:var(--wp--preset--spacing--tiny)">
		<!-- wp:paragraph {"align":"center","className":""} -->
		<p class="has-text-align-center"><?php esc_html_e( 'Put your header template part in the container, use the container in a page template and delete the placeholder', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:greyd/box -->
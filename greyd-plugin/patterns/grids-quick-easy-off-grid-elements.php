<?php
/**
 * Title: Quick and easy off-grid elements
 * Slug: greyd-plugin/grids-quick-easy-off-grid-elements
 * Description: 
 * Categories: greyd-grids
 * Keywords: 
 * Viewport Width: 1600
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"full","className":"break-md reverse-md","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"layout":{"type":"flex","flexWrap":"nowrap"},"greydStyles":{"margin":null}} -->
<div class="wp-block-group alignfull break-md reverse-md" style="padding-top:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large)">
	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium"}},"layout":{"selfStretch":"fixed","flexSize":"100%"}},"layout":{"type":"constrained","contentSize":"","justifyContent":"left"},"inline_css":"padding-left: max(var(--wp--style--root--padding-left), calc(50vw - calc(var(--wp--style--global--wide-size) / 2)));","greydStyles":{"width":null}} -->
	<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--medium)">
		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Off-Grid Image Right', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":""} -->
		<p>
			<?php esc_html_e( 'The content area is neatly aligned with the content on the left using the "wide width" value. The image on the right always falls off the edge.', 'greyd_hub' ); ?>
			<br>
			<?php printf( /* translators: The variables refer to the HTML tags for highlighting the "respect-grid-left" word of the sentence. */ 
				esc_html__( 'To do this, the class %1$s"respect-grid-left"%2$s is set in the group that includes the content area. The parent container is set to "full-width".', 'greyd_hub'), '<strong>', 
				'</strong>' 
			); ?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"constrained"},"greydStyles":{"width":null}} -->
	<div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-cubes-floating.webp' ); ?>","tag":""},"sizeSlug":"full","greydStyles":{"width":"100%"},"className":""} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"break-md","style":{"spacing":{"margin":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"layout":{"type":"flex","flexWrap":"nowrap"},"greydStyles":{"margin":null}} -->
<div class="wp-block-group alignfull break-md" style="margin-top:var(--wp--preset--spacing--large);margin-bottom:var(--wp--preset--spacing--large)">
	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"},"spacing":{"padding":{"right":"0","left":"0","top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-cubes-floating.webp' ); ?>","tag":""},"sizeSlug":"full","greydStyles":{"width":"100%"},"className":""} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"},"spacing":{"padding":{"left":"var:preset|spacing|medium"}}},"layout":{"type":"constrained"},"inline_css":"padding-right: max(var(--wp--style--root--padding-left), calc(50vw - calc(var(--wp--style--global--wide-size) / 2)));"} -->
	<div class="wp-block-group" style="padding-left:var(--wp--preset--spacing--medium)">
		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Off-Grid Image Left', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":""} -->
		<p>
			<?php esc_html_e( 'The content area is neatly aligned with the content on the left using the "wide width" value. The image on the right always falls off the edge.', 'greyd_hub' ); ?>
			<br>
			<?php printf( /* translators: The variables refer to the HTML tags for highlighting the "respect-grid-left" word of the sentence. */ 
				esc_html__( 'To do this, the class %1$s"respect-grid-right"%2$s is set in the group that includes the content area. The parent container is set to "full-width".', 'greyd_hub'), '<strong>', 
				'</strong>' 
			); ?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
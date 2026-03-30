<?php
/**
 * Title: Header simple with button
 * Slug: greyd-plugin/header-simple-button
 * Description: Header with nav and overlay menu
 * Categories: greyd-headers
 * Keywords: header, nav, links, button
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: 
 */
?>
<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"inherit":"true","type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"width":70,"shouldSyncIcon":false,"className":""} /-->
			<!-- wp:site-title {"className":""} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"overlayMenu":"never","__unstableLocation":"primary","className":"open-on-hover-click","style":{"spacing":{"margin":{"top":"0"}}},"layout":{"type":"flex","setCascadingProperties":true,"justifyContent":"right","orientation":"horizontal"}} -->
				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'anchor-one', 'greyd_hub' ); ?>","url":"#anchor-one","className":""} /-->
				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'anchor-two', 'greyd_hub' ); ?>","url":"#anchor-two","className":""} /-->
				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'anchor-three', 'greyd_hub' ); ?>","url":"#anchor-three","className":""} /-->
				<!-- wp:navigation-link {"label":"<?php esc_html_e( 'anchor-four', 'greyd_hub' ); ?>","url":"#anchor-four","className":""} /-->
			<!-- /wp:navigation -->

			<!-- wp:buttons {"className":""} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-alternate"} -->
				<div class="wp-block-button is-style-alternate"><a class="wp-block-button__link wp-element-button" href="https://greyd.io/"><?php esc_html_e( 'Download', 'greyd_hub' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:greyd/popover {"className":""} -->
				<!-- wp:greyd/popover-button {"variation":"burger","className":""} /-->

				<!-- wp:greyd/popover-popup {"variation":"overlay","greydStyles":{"--dialog-background":"var(--wp--preset--color--lightest)"},"className":""} -->
					<!-- wp:heading {"className":""} -->
					<h2 class="wp-block-heading"><?php esc_html_e( 'Overlay', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:navigation {"__unstableLocation":"primary","layout":{"type":"flex","orientation":"vertical"}} /-->
				<!-- /wp:greyd/popover-popup -->
			<!-- /wp:greyd/popover -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
<?php
/**
 * Title: Responsive grid with 4 elements
 * Slug: greyd-plugin/grids-responsive-4-elements
 * Description: 
 * Categories: greyd-grids
 * Keywords: 
 * Viewport Width: 1600
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"grid-max-2","layout":{"type":"grid","minimumColumnWidth":"24rem"}} -->
<div class="wp-block-group alignwide grid-max-2">
	<!-- wp:group {"className":"","backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group has-base-background-color has-background">
		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'First element 50%', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"grid-max-2","style":{"layout":{"rowSpan":2}},"layout":{"type":"grid","minimumColumnWidth":"20rem"}} -->
	<div class="wp-block-group grid-max-2">
		<!-- wp:group {"className":"","backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
		<div class="wp-block-group has-base-background-color has-background">
			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Second element 25%', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
		<div class="wp-block-group has-base-background-color has-background">
			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Third element 25%', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group has-base-background-color has-background">
		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'Fourth element 50%', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
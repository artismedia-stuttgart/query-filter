<?php
/**
 * Title: Bento Grid 4
 * Slug: greyd-plugin/grids-bento-grid-4
 * Description: 
 * Categories: greyd-grids
 * Keywords: 
 * Viewport Width: 1600
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"grid-max-3","style":{"dimensions":{"minHeight":"70vh"}},"layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide grid-max-3" style="min-height:70vh">
	<!-- wp:group {"className":"","style":{"layout":{"columnSpan":2,"rowSpan":1},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Create great websites fast', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":""} -->
		<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. </p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":1},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"top"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"onScroll","parent":"","start":"50%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease","color":"var(--wp--preset--color--base)","background":"var(--wp--preset--color--foreground)"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:heading {"textAlign":"left","className":"","style":{"typography":{"writingMode":"vertical-rl"}},"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-text-align-left has-medium-font-size" style="writing-mode:vertical-rl"><?php esc_html_e( 'What about a change', 'greyd_hub' ); ?><br><?php esc_html_e( 'of perspective?', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":3},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:group {"className":"","layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"className":""} -->
			<h2 class="wp-block-heading">Block-based &amp; accessible</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":""} -->
			<p>Bento / Masonry Grid 04</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:greyd/buttons {"className":""} -->
		<div class="wp-block-greyd-buttons">
			<!-- wp:greyd/button {"content":"<?php esc_html_e( 'Button', 'greyd_hub' ); ?>","className":"is-style-trd"} -->
			<a role="trigger" class="button is-style-trd"><?php esc_html_e( 'Button', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"columnSpan":2,"rowSpan":3},"dimensions":{"minHeight":"240px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px"></div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
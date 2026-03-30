<?php
/**
 * Title: Side by Side with Sticky Section
 * Slug: greyd-plugin/sections-side-by-side-sticky
 * Description:
 * Categories: greyd-sections
 * Keywords:
 * Viewport Width: 1600
 * Inserter: true
 */
?>
<!-- wp:group {"templateLock":false,"lock":{"move":false,"remove":false},"align":"wide","className":"break-md","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"stretch"}} -->
<div class="wp-block-group alignwide break-md" style="padding-right:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group">
		<!-- wp:greyd/box {"variation":"sticky","className":""} -->
		<div class="wp-block-greyd-box">
			<!-- wp:heading {"className":"","fontSize":"xx-large"} -->
			<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e( 'Sticky Sections Side by Side – built with a Row and two Stacks', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:greyd/box -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","tag":""},"className":""} /-->

		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Headline Lorem Ipsum', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":""} -->
		<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
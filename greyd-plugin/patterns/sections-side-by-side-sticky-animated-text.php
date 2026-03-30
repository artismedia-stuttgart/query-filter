<?php
/**
 * Title: Side by Side with Sticky Section with Animated Text
 * Slug: greyd-plugin/sections-side-by-side-sticky-animated-text
 * Description:
 * Categories: greyd-sections
 * Keywords:
 * Viewport Width: 1600
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"break-md","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"stretch"}} -->
<div class="wp-block-group alignwide break-md" style="padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large)">
	<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fixed","flexSize":"100%"},"dimensions":{"minHeight":"50vh"}},"gradient":"background-to-mediumlight","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
	<div class="wp-block-group has-background-to-mediumlight-gradient-background has-background" style="min-height:50vh">
		<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"var:preset|spacing|medium","right":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium"},"--sticky-offset":"var(--wp--preset--spacing--medium)"},"background":{"type":"image"},"variation":"sticky","className":"","greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","event":"onScroll","parent":"","start":"30%","end":"50%","reverse":true,"duration":200,"delay":0,"timing":"ease","color":"var(--wp--preset--color--lightest)","background":"var(--wp--preset--color--foreground)"}} -->
		<div class="wp-block-greyd-box">
			<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'Sticky Sections Side by Side – with scaling Background and a min-height 50vw + Animated Background in Headline', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:greyd/box -->

		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","tag":""},"className":""} /-->
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
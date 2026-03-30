<?php
/**
 * Title: Sticky Anchor Navigation
 * Slug: greyd-plugin/sections-sticky-anchor-navigation
 * Description:
 * Categories: greyd-sections
 * Keywords: anchor, navigation, sticky
 * Viewport Width: 1600
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large)">
	<!-- wp:greyd/box {"variation":"sticky","align":"full","className":""} -->
	<div class="wp-block-greyd-box alignfull">
		<!-- wp:group {"className":"break-md","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"backgroundColor":"foreground","textColor":"lightest","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group break-md has-lightest-color has-foreground-background-color has-text-color has-background has-link-color">
			<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"},"greydStyles":{"width":"33%","responsive":{"md":{"width":"100%"}}}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"className":""} -->
				<p><strong><?php esc_html_e( 'Sticky Anchors / Toc Navigation', 'greyd_hub' ); ?></strong></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"},"greydStyles":{"width":"66%","responsive":{"md":{"width":"100%"}}}} -->
			<div class="wp-block-group">
				<!-- wp:navigation {"icon":"menu","className":"","layout":{"type":"flex","justifyContent":"left"},"custom":true,"customStyles":{"active":{"color":"var(--wp--preset--color--secondary)"},"color":"var(--wp--preset--color--lightest)","hover":{"color":"var(--wp--preset--color--secondary)"},"textDecoration":"none"},"anchoractive":{"enable":true,"start":"0%","end":"100%","multiple":"closest","none":"closest"}} -->
					<!-- wp:navigation-link {"label":"anchor-one","url":"#anchor-one","kind":"custom","className":""} /-->
					<!-- wp:navigation-link {"label":"anchor-two","url":"#anchor-two","kind":"custom","className":""} /-->
					<!-- wp:navigation-link {"label":"anchor-three","url":"#anchor-three","kind":"custom","className":""} /-->
					<!-- wp:navigation-link {"label":"anchor-four","url":"#anchor-four","kind":"custom","className":""} /-->
				<!-- /wp:navigation -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:greyd/box -->

	<!-- wp:group {"className":"","layout":{"type":"constrained","contentSize":""}} -->
	<div class="wp-block-group">
		<!-- wp:group {"className":"","layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/anchor {"anchor":"anchor-one","greydStyles":{"--anchorcustommargin":null}} -->
			<div class="greyd-anchor-target--wrapper">
				<div id="anchor-one" class="greyd-anchor-target"></div>
			</div>
			<!-- /wp:greyd/anchor -->

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

		<!-- wp:group {"className":"","layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/anchor {"anchor":"anchor-two"} -->
			<div class="greyd-anchor-target--wrapper">
				<div id="anchor-two" class="greyd-anchor-target"></div>
			</div>
			<!-- /wp:greyd/anchor -->

			<!-- wp:heading {"level":3,"className":""} -->
			<h3 class="wp-block-heading"><?php esc_html_e( 'Headline Lorem Ipsum', 'greyd_hub' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/anchor {"anchor":"anchor-three"} -->
			<div class="greyd-anchor-target--wrapper">
				<div id="anchor-three" class="greyd-anchor-target"></div>
			</div>
			<!-- /wp:greyd/anchor -->

			<!-- wp:heading {"level":3,"className":""} -->
			<h3 class="wp-block-heading"><?php esc_html_e( 'Headline Lorem Ipsum', 'greyd_hub' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":""} -->
			<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:greyd/anchor {"anchor":"anchor-four"} -->
			<div class="greyd-anchor-target--wrapper">
				<div id="anchor-four" class="greyd-anchor-target"></div>
			</div>
			<!-- /wp:greyd/anchor -->

			<!-- wp:heading {"level":3,"className":""} -->
			<h3 class="wp-block-heading"><?php esc_html_e( 'Headline Lorem Ipsum', 'greyd_hub' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"break-md","layout":{"type":"flex","flexWrap":"nowrap"}} -->
			<div class="wp-block-group break-md">
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
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
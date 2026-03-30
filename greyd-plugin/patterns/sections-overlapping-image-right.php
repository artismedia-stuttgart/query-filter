<?php
/**
 * Title: Overlapping Image Right
 * Slug: greyd-plugin/sections-overlapping-image-right
 * Description:
 * Categories: greyd-sections
 * Keywords:
 * Viewport Width: 1100
 * Inserter: true
 */
?>
<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0">
	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"},"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"stretch"},"greydStyles":{"minWidth":"45%","responsive":{"md":{"minWidth":"100%"}},"padding":{"top":"32px","right":"","bottom":"","left":""}}} -->
	<div class="wp-block-group">
		<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"foreground","textColor":"lightest","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"},"inline_css":"","greydStyles":{"minWidth":null,"responsive":{"md":{"minWidth":null,"width":""},"sm":{"minWidth":null,"width":"100%","padding":{"top":"","right":"var:preset|spacing|medium","bottom":"","left":"var:preset|spacing|medium"}}},"margin":null,"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))","padding":{"top":"","right":"64px","bottom":"","left":"64px"}}} -->
		<div class="wp-block-group has-lightest-color has-foreground-background-color has-text-color has-background has-link-color">
			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:heading {"level":3,"className":""} -->
				<h3 class="wp-block-heading"><strong><?php esc_html_e( 'Our great service:', 'greyd_hub' ); ?></strong></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","className":""} -->
				<p class="has-text-align-center"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"layout":{"selfStretch":"fill","flexSize":null},"spacing":{"padding":{"top":"0","bottom":"0"}}},"textColor":"lightest","layout":{"type":"constrained"},"inline_css":"","greydStyles":{"minWidth":null,"responsive":{"md":{"minWidth":null,"width":""},"sm":{"minWidth":null,"margin":{"top":"0px","right":"","bottom":"","left":"0px"},"width":"100%"}},"margin":{"top":"-64px","right":"","bottom":"64px","left":"-64px"},"padding":null,"maxWidth":null,"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))"}} -->
		<div class="wp-block-group has-lightest-color has-text-color has-link-color" style="padding-top:0;padding-bottom:0">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":""} -->
			<figure class="wp-block-image size-full">
				<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-and-black-cubes-and-pyramids.webp' ); ?>" alt=""/>
			</figure>
			<!-- /wp:image --></div>
		<!-- /wp:group --></div>
	<!-- /wp:group --></div>
<!-- /wp:group -->
<?php
/**
 * Title: Hero banner with overlapping boxes
 * Slug: greyd-plugin/hero-banner-with-overlapping-boxes
 * Description: A hero banner with 3 overlapping boxes at the bottom
 * Categories: greyd-hero
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"full","className":"","style":{"spacing":{"padding":{"top":"100px","right":"80px","bottom":"100px","left":"80px"},"blockGap":"0px"},"color":{"gradient":"linear-gradient(360deg,#fff 42%,#000 42%)"}},"fontSize":"small","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background has-small-font-size" style="background:linear-gradient(360deg,#fff 42%,#000 42%);padding-top:100px;padding-right:80px;padding-bottom:100px;padding-left:80px">
	<!-- wp:paragraph {"textAlign":"center","className":"","style":{"typography":{"lineHeight":2},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"textColor":"lightest"} -->
	<p class="has-text-align-center has-lightest-color has-text-color has-link-color" style="line-height:2"><?php esc_html_e( 'Adventures with Lorem Ipsum', 'greyd_hub' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","align":"wide","className":"","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|x-large"}},"elements":{"link":{"color":{"text":"var:preset|color|tertiary"}}}},"textColor":"tertiary"} -->
	<h2 class="wp-block-heading alignwide has-text-align-center has-tertiary-color has-text-color has-link-color" style="margin-bottom:var(--wp--preset--spacing--x-large)"><?php esc_html_e( 'Looking Back on Love', 'greyd_hub' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","className":""} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"className":"col-12 col-sm-auto","style":{"spacing":[]}} -->
		<div class="wp-block-column col-12 col-sm-auto">
			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","right":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}},"border":{"radius":"4px","color":"#000000"}},"backgroundColor":"lightest"} -->
			<div class="wp-block-group has-border-color has-lightest-background-color has-background" style="border-color:#000000;border-radius:4px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-resized"} -->
				<figure class="wp-block-image size-full is-resized">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-black.svg' ); ?>" alt="" class="">
				</figure>
				<!-- /wp:image -->

				<!-- wp:heading {"textAlign":"left","level":3,"className":"","textColor":"color-31"} -->
				<h3 class="wp-block-heading has-text-align-left has-color-31-color has-text-color"><?php esc_html_e( 'Block Editor', 'greyd_hub' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"left","className":""} -->
				<p class="has-text-align-left"><?php esc_html_e( "With Greyd, you're using the WordPress Block Editor with additional features and optimizations. Creating professional websites has never been easier!", 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"col-12 col-sm-auto"} -->
		<div class="wp-block-column col-12 col-sm-auto">
			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","right":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}},"border":{"radius":"4px","color":"#000000"}},"backgroundColor":"lightest","textColor":""} -->
			<div class="wp-block-group has-border-color has-lightest-background-color has-background" style="border-color:#000000;border-radius:4px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-resized"} -->
				<figure class="wp-block-image size-full is-resized">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-black.svg' ); ?>" alt="" class=""></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"textAlign":"left","level":3,"className":"","textColor":"color-31"} -->
				<h3 class="wp-block-heading has-text-align-left has-color-31-color has-text-color"><?php esc_html_e( 'Global Styles', 'greyd_hub' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"left","className":""} -->
				<p class="has-text-align-left"><?php esc_html_e( 'Full control. Minimal effort. Specify colors, shapes, and fonts for your entire website centrally. From buttons to forms, all elements automatically adapt to your styles.', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"col-12 col-sm-auto"} -->
		<div class="wp-block-column col-12 col-sm-auto">
			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","right":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}},"border":{"radius":"4px"}},"backgroundColor":"lightest","textColor":"","borderColor":"darkest"} -->
			<div class="wp-block-group has-border-color has-darkest-border-color has-lightest-background-color has-background" style="border-radius:4px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-resized"} -->
				<figure class="wp-block-image size-full is-resized">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-black.svg' ); ?>" alt="" class="">
				</figure>
				<!-- /wp:image -->

				<!-- wp:heading {"textAlign":"left","level":3,"className":"","textColor":"color-31"} -->
				<h3 class="wp-block-heading has-text-align-left has-color-31-color has-text-color"><?php esc_html_e( 'Full Site Editing', 'greyd_hub' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"left","className":""} -->
				<p class="has-text-align-left"><?php esc_html_e( 'Headers, 404 templates, footers, or post templates – with Greyd.Suite you can customize any part of your website and use all blocks and functions.', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
<?php
/**
 * Title: Animated image card with link
 * Slug: greyd-plugin/dt-cards-animated-image-card-link
 * Description: 
 * Categories: greyd-dt-patterns
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: dynamic_template
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated image card with link', 'greyd_hub' ); ?>"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"blockGap":"var:preset|spacing|medium","padding":{"right":"0","left":"0"}},"border":{"radius":"16px"}},"backgroundColor":"base","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
<div class="wp-block-group hover has-foreground-color has-base-background-color has-text-color has-background has-link-color" style="border-radius:16px;padding-right:0;padding-left:0">
	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-text-logo-black.webp' ); ?>","tag":""},"greydStyles":{"width":"120px"},"className":"","style":{"spacing":{"margin":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}}} /-->

		<!-- wp:cover {"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","dimRatio":0,"overlayColor":"darkest","isUserOverlayColor":false,"minHeight":240,"minHeightUnit":"px","className":"is-style-no-background","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"constrained"},"greydBackgroundAnim":{"action":"scale","preset":"fadeOut","from":1,"to":1.3,"origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":3000,"delay":0,"timing":"ease-in-out"}} -->
		<div class="wp-block-cover is-style-no-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;min-height:240px">
			<span aria-hidden="true" class="wp-block-cover__background has-darkest-background-color has-background-dim-0 has-background-dim"></span>
			<img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph {"align":"center","className":"","fontSize":"large"} -->
				<p class="has-text-align-center has-large-font-size"></p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:cover -->

		<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
			<!-- wp:heading {"className":"","fontSize":"large"} -->
			<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
			<p class="has-tiny-font-size"><?php esc_html_e( 'Designing for the web is not just about pixels; it is about people, accessibility, and crafting sustainable digital experiences that stand the test of time.', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
		<!-- wp:separator {"className":"","backgroundColor":"foreground","greydStyles":{"height":"1px","width":"100%"}} -->
		<hr class="wp-block-separator has-text-color has-foreground-color has-alpha-channel-opacity has-foreground-background-color has-background"/>
		<!-- /wp:separator -->

		<!-- wp:greyd/buttons {"className":""} -->
		<div class="wp-block-greyd-buttons">
			<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class; ?>","customStyles":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"fontSize":null,"textDecoration":"underline"},"size":"is-size-small","content":"<?php esc_html_e( 'Link', 'greyd_hub' ); ?>","custom":true,"className":"is-style-clear"} -->
			<a role="trigger" class="button is-style-clear gs_<?php echo $_greyd_class; ?> is-size-small"><?php esc_html_e( 'Link', 'greyd_hub' ); ?></a>
			<style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { padding-top: 0px !important; padding-right: 0px !important; padding-bottom: 0px !important; padding-left: 0px !important; text-decoration: underline !important; } </style>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
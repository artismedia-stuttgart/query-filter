<?php
/**
 * Title: Animated price card with link, dark
 * Slug: greyd-plugin/cards-animated-price-card-link-dark
 * Description: 
 * Categories: greyd-cards
 * Keywords: 
 * Viewport Width: 800
 * Block Types: core/post-content
 * Post Types: 
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Animated price card with link, dark', 'greyd_hub' ); ?>"},"className":"hover","style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"spacing":{"blockGap":"var:preset|spacing|large"},"border":{"radius":"16px"}},"backgroundColor":"foreground","textColor":"background","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
<div class="wp-block-group hover has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="border-radius:16px">
	<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
		<p class="has-tiny-font-size"><?php esc_html_e( 'Overline', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"className":"","fontSize":"large"} -->
		<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
		<p class="has-tiny-font-size"><?php esc_html_e( 'Transform your online presence with our expert services.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:separator {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"background","greydStyles":{"height":"1px","width":"100%"}} -->
	<hr class="wp-block-separator has-text-color has-background-color has-alpha-channel-opacity has-background-background-color has-background" style="margin-top:0;margin-bottom:0"/>
	<!-- /wp:separator -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"","fontSize":"tiny"} -->
			<p class="has-tiny-font-size"><?php esc_html_e( 'Descriptor', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"large","fontFamily":"body"} -->
			<p class="has-body-font-family has-large-font-size" style="font-style:normal;font-weight:600"><?php esc_html_e( '499 $', 'greyd_hub' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:greyd/buttons {"className":"","greydAnim":{"action":"show","preset":"fadeInLeft","from":"","to":"","origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease"}} -->
		<div class="wp-block-greyd-buttons">
			<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class; ?>","customStyles":{"color":"var(--wp--preset--color--background)","border":{"top":"1px solid var(--wp--preset--color--background)","right":"1px solid var(--wp--preset--color--background)","bottom":"1px solid var(--wp--preset--color--background)","left":"1px solid var(--wp--preset--color--background)"},"borderRadius":"50px","padding":{"top":"0.5em","right":"0.5em","bottom":"0.5em","left":"0.5em"}},"content":"","icon":{"content":"arrow_carrot-right","position":"after","size":"2em","margin":"0px"},"custom":true,"className":"is-style-clear"} -->
			<a role="trigger" class="button is-style-clear gs_<?php echo $_greyd_class; ?>">
				<span style="flex:1"></span>
				<span class="arrow_carrot-right" style="vertical-align:middle;font-size:2em;margin-left:0px" aria-hidden="true"></span>
			</a>
			<style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { color: var(--wp--preset--color--background) !important; border-top: 1px solid var(--wp--preset--color--background) !important; border-right: 1px solid var(--wp--preset--color--background) !important; border-bottom: 1px solid var(--wp--preset--color--background) !important; border-left: 1px solid var(--wp--preset--color--background) !important; border-radius: 50px !important; padding-top: 0.5em !important; padding-right: 0.5em !important; padding-bottom: 0.5em !important; padding-left: 0.5em !important; } </style>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
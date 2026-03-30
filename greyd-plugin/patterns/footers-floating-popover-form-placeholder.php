<?php
/**
 * Title: Floating action popover with form placeholder
 * Slug: greyd-plugin/footers-floating-popover-form-placeholder
 * Description: Form placeholder only available with Greyd Forms plugin.
 * Categories: greyd-footers
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Floating action popover with form placeholder', 'greyd_hub' ); ?>"},"className":"","layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:paragraph {"align":"center","className":"","fontSize":"tiny"} -->
	<p class="has-text-align-center has-tiny-font-size"><?php esc_html_e( 'Place this section at the bottom of your footer template to enable a floating contact button', 'greyd_hub' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:greyd/box {"greydStyles":{"--offset-right":"var:preset|spacing|medium","--offset-bottom":"var:preset|spacing|x-large","maxWidth":"","align":"right"},"variation":"fixed","className":""} -->
	<div class="wp-block-greyd-box">
		<!-- wp:greyd/popover {"className":""} -->
			<!-- wp:greyd/popover-button {"buttonStyle":"link-prim","icon":{"content":"icon_comment_alt","position":"after","size":"24px","margin":"0px"},"custom":true,"customStyles":{"background":"var(--wp--preset--color--primary)","color":"var(--wp--preset--color--background)","hover":{"background":"var(--wp--preset--color--background)","color":"var(--wp--preset--color--foreground)"},"borderRadius":"50px","padding":{"top":"12px","right":"12px","bottom":"12px","left":"12px"},"boxShadow":"0px+10px+15px+0px+var(--wp--preset--color--foreground)+25"},"className":""} -->
			<span></span>
			<span class="icon_comment_alt" style="vertical-align:middle;font-size:24px;margin-left:0px" aria-hidden="true"></span>
			<!-- /wp:greyd/popover-button -->

			<!-- wp:greyd/popover-popup {"greydStyles":{"--dialog-background":"var(--wp--preset--color--foreground)","--dialog-color":"var(--wp--preset--color--background)"},"className":""} -->
				<!-- wp:group {"className":"","style":{"spacing":{"margin":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"},"blockGap":"0"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--medium);margin-bottom:var(--wp--preset--spacing--medium)">
					<!-- wp:paragraph {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
					<p class="has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Contact us', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":""} -->
					<p><?php esc_html_e( 'Please insert your contact form here', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:greyd/form {"className":""} /-->
			<!-- /wp:greyd/popover-popup -->
		<!-- /wp:greyd/popover -->
	</div>
	<!-- /wp:greyd/box -->
</div>
<!-- /wp:group -->
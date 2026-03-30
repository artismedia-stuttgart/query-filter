<?php
/**
 * Title: Popup content with multi drawer and triggers
 * Slug: greyd-plugin/popup-contents-multi-drawer-popup-with-triggers
 * Description: Popup content with a multi drawer and triggers
 * Categories: greyd-popup-contents
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<?php $_greyd_class_1 = substr( md5( uniqid() ), 0, 6 ); ?>
<?php $_greyd_class_2 = substr( md5( uniqid() ), 0, 6 ); ?>
<?php $_greyd_class_3 = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Popup content with multi drawer and triggers', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0">
	<!-- wp:group {"align":"wide","className":"hover","style":{"spacing":{"blockGap":"0","padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydStyles":{"width":"%","maxWidth":null,"minWidth":"%"}} -->
	<div class="wp-block-group alignwide hover has-base-background-color has-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"stretch"}} -->
		<div class="wp-block-group">
			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"backgroundColor":"foreground","textColor":"background","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"top"},"greydStyles":{"minWidth":"40%","maxWidth":"40%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
			<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"0","right":"var:preset|spacing|medium","bottom":"0","left":"var:preset|spacing|medium"},"border":{"top":"1px solid var(--wp--preset--color--foreground)","right":"1px solid var(--wp--preset--color--foreground)","bottom":"1px solid var(--wp--preset--color--base)","left":"1px solid var(--wp--preset--color--foreground)"},"hover":{"color":"var(--wp--preset--color--secondary)"}},"className":"","trigger":{"type":"event","params":{"name":"trigger_1","hover":true}}} -->
				<div class="wp-block-greyd-box">
					<!-- wp:paragraph {"className":""} -->
					<p><?php esc_html_e( 'Trigger 01', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:greyd/box -->

				<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"0","right":"var:preset|spacing|medium","bottom":"0","left":"var:preset|spacing|medium"},"border":{"top":"1px solid var(--wp--preset--color--foreground)","right":"1px solid var(--wp--preset--color--foreground)","bottom":"1px solid var(--wp--preset--color--base)","left":"1px solid var(--wp--preset--color--foreground)"},"hover":{"color":"var(--wp--preset--color--secondary)"}},"className":"","trigger":{"type":"event","params":{"name":"trigger_2","hover":true}}} -->
				<div class="wp-block-greyd-box">
					<!-- wp:paragraph {"className":""} -->
					<p><?php esc_html_e( 'Trigger 02', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:greyd/box -->

				<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"0","right":"var:preset|spacing|medium","bottom":"0","left":"var:preset|spacing|medium"},"hover":{"color":"var(--wp--preset--color--secondary)"}},"className":"","trigger":{"type":"event","params":{"name":"trigger_3","hover":true}}} -->
				<div class="wp-block-greyd-box">
					<!-- wp:paragraph {"className":""} -->
					<p><?php esc_html_e( 'Trigger 03', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:greyd/box -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|medium","left":"var:preset|spacing|medium","top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"},"greydStyles":{"minWidth":"60%","maxWidth":"60%","responsive":{"sm":{"minWidth":"100%","maxWidth":"100%"}}}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group">
					<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"constrained","contentSize":"680px"},"trigger_event":{"siblings":true,"actions":[{"name":"Trigger 1","action":"show"}]}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large"} -->
						<h2 class="wp-block-heading has-text-align-center has-x-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Trigger Element 01', 'greyd_hub' ); ?></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"center","className":""} -->
						<p class="has-text-align-center">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
						<!-- /wp:paragraph -->

						<!-- wp:greyd/buttons {"align":"center","className":""} -->
						<div class="wp-block-greyd-buttons aligncenter">
							<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class_1; ?>","customStyles":{"borderRadius":"50px"},"content":"<?php esc_html_e( 'Call to action', 'greyd_hub' ); ?>","custom":true,"className":"is-style-trd"} -->
							<a role="trigger" class="button is-style-trd gs_<?php echo $_greyd_class_1; ?>"><?php esc_html_e( 'Call to action', 'greyd_hub' ); ?></a>
							<style class="greyd-styles">.gs_<?php echo $_greyd_class_1; ?> { border-radius: 50px !important; } </style>
							<!-- /wp:greyd/button -->
						</div>
						<!-- /wp:greyd/buttons -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"constrained","contentSize":"680px"},"trigger_event":{"onload":"hide","actions":[{"name":"Trigger 2","action":"show"}],"siblings":true}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large"} -->
						<h2 class="wp-block-heading has-text-align-center has-x-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Trigger Element 02', 'greyd_hub' ); ?></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"center","className":""} -->
						<p class="has-text-align-center">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
						<!-- /wp:paragraph -->

						<!-- wp:greyd/buttons {"align":"center","className":""} -->
						<div class="wp-block-greyd-buttons aligncenter">
							<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class_2; ?>","customStyles":{"borderRadius":"50px"},"content":"<?php esc_html_e( 'Call to action', 'greyd_hub' ); ?>","custom":true,"className":"is-style-sec"} -->
							<a role="trigger" class="button is-style-sec gs_<?php echo $_greyd_class_2; ?>"><?php esc_html_e( 'Call to action', 'greyd_hub' ); ?></a>
							<style class="greyd-styles">.gs_<?php echo $_greyd_class_2; ?> { border-radius: 50px !important; } </style>
							<!-- /wp:greyd/button -->
						</div>
						<!-- /wp:greyd/buttons -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"constrained","contentSize":"680px"},"trigger_event":{"onload":"hide","actions":[{"name":"Trigger 3","action":"show"}],"siblings":true}} -->
					<div class="wp-block-group">
						<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large"} -->
						<h2 class="wp-block-heading has-text-align-center has-x-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Trigger Element 03', 'greyd_hub' ); ?></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"align":"center","className":""} -->
						<p class="has-text-align-center">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
						<!-- /wp:paragraph -->

						<!-- wp:greyd/buttons {"align":"center","className":""} -->
						<div class="wp-block-greyd-buttons aligncenter">
							<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class_3; ?>","customStyles":{"borderRadius":"50px"},"content":"<?php esc_html_e( 'Call to action', 'greyd_hub' ); ?>","custom":true,"className":"is-style-prim"} -->
							<a role="trigger" class="button is-style-prim gs_<?php echo $_greyd_class_3; ?>"><?php esc_html_e( 'Call to action', 'greyd_hub' ); ?></a>
							<style class="greyd-styles">.gs_<?php echo $_greyd_class_3; ?> { border-radius: 50px !important; } </style>
							<!-- /wp:greyd/button -->
						</div>
						<!-- /wp:greyd/buttons -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
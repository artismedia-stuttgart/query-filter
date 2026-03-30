<?php
/**
 * Title: Popup content for product with call to action and list items
 * Slug: greyd-plugin/popup-contents-product-cta-list-items
 * Description: 
 * Categories: greyd-popup-contents
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"metadata":{"name":"<?php esc_html_e( 'Popup content for product with call to action and list items', 'greyd_hub' ); ?>"},"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-bottom:0">
	<!-- wp:group {"align":"wide","className":"hover","style":{"spacing":{"blockGap":"0","padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"border":{"radius":"16px","width":"2px","style":"dotted"}},"backgroundColor":"base","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
	<div class="wp-block-group alignwide hover has-foreground-color has-base-background-color has-text-color has-background has-link-color" style="border-style:dotted;border-width:2px;border-radius:16px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:cover {"dimRatio":0,"isUserOverlayColor":true,"minHeight":50,"minHeightUnit":"vh","isDark":false,"className":"is-style-no-background","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"textColor":"foreground","layout":{"type":"constrained"},"greydBackgroundAnim":{"action":"scale","preset":"fadeOut","from":1,"to":1.1,"origin":"center center","event":"parentHover","parent":".hover","start":"50%","end":"50%","reverse":false,"duration":3000,"delay":0,"timing":"ease-in-out"}} -->
		<div class="wp-block-cover is-light is-style-no-background has-foreground-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large);min-height:50vh">
			<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
			<div class="wp-block-cover__inner-container">
				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"constrained","contentSize":"680px"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"textAlign":"center","className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large"} -->
					<h2 class="wp-block-heading has-text-align-center has-x-large-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'This pattern works great as a popup', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"center","className":""} -->
					<p class="has-text-align-center">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>
					<!-- /wp:paragraph -->

					<!-- wp:greyd/buttons {"align":"center","className":""} -->
					<div class="wp-block-greyd-buttons aligncenter">
						<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class; ?>","customStyles":{"borderRadius":"50px"},"content":"<?php esc_html_e( 'Call to action', 'greyd_hub' ); ?>","custom":true,"className":"is-style-trd"} -->
						<a role="trigger" class="button is-style-trd gs_<?php echo $_greyd_class; ?>"><?php esc_html_e( 'Call to action', 'greyd_hub' ); ?></a>
						<style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { border-radius: 50px !important; } </style>
						<!-- /wp:greyd/button -->
					</div>
					<!-- /wp:greyd/buttons -->

					<!-- wp:group {"className":"","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
					<div class="wp-block-group" style="font-style:normal;font-weight:700">
						<!-- wp:greyd/list {"type":"icon","icon":{"0":"i","1":"c","2":"o","3":"n","4":"_","5":"p","6":"e","7":"n","8":"c","9":"i","10":"l","11":"_","12":"a","13":"l","14":"t","icon":"arrow_triangle-right_alt","position":"left","align_y":"center"},"className":""} -->
						<ul class="wp-block-greyd-list">
							<!-- wp:greyd/list-item {"type":"icon","icon":"arrow_triangle-right_alt"} -->
							<li><span class="list_icon arrow_triangle-right_alt"></span><span class="list_content"><p><?php esc_html_e( 'List Item', 'greyd_hub' ); ?></p></span></li>
							<!-- /wp:greyd/list-item -->
						</ul>
						<!-- /wp:greyd/list -->

						<!-- wp:greyd/list {"type":"icon","icon":{"0":"i","1":"c","2":"o","3":"n","4":"_","5":"p","6":"e","7":"n","8":"c","9":"i","10":"l","11":"_","12":"a","13":"l","14":"t","icon":"arrow_triangle-up_alt","position":"left","align_y":"center"},"className":""} -->
						<ul class="wp-block-greyd-list">
							<!-- wp:greyd/list-item {"type":"icon","icon":"arrow_triangle-up_alt"} -->
							<li><span class="list_icon arrow_triangle-up_alt"></span><span class="list_content"><p><?php esc_html_e( 'List Item', 'greyd_hub' ); ?></p></span></li>
							<!-- /wp:greyd/list-item -->
						</ul>
						<!-- /wp:greyd/list -->

						<!-- wp:greyd/list {"type":"icon","icon":{"0":"i","1":"c","2":"o","3":"n","4":"_","5":"p","6":"e","7":"n","8":"c","9":"i","10":"l","11":"_","12":"a","13":"l","14":"t","icon":"arrow_triangle-down_alt","position":"left","align_y":"center"},"className":""} -->
						<ul class="wp-block-greyd-list">
							<!-- wp:greyd/list-item {"type":"icon","icon":"arrow_triangle-down_alt"} -->
							<li><span class="list_icon arrow_triangle-down_alt"></span><span class="list_content"><p><?php esc_html_e( 'List Item', 'greyd_hub' ); ?></p></span></li>
							<!-- /wp:greyd/list-item -->
						</ul>
						<!-- /wp:greyd/list -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
		</div>
		<!-- /wp:cover -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
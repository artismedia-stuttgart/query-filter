<?php
/**
 * Title: Sticky Side by Side Bento
 * Slug: greyd-plugin/sections-sticky-side-by-side-bento
 * Description:
 * Categories: greyd-sections
 * Keywords:
 * Viewport Width: 1600
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:group {"align":"full","className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"backgroundColor":"darkest","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-darkest-background-color has-background" style="padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large)">
	<!-- wp:group {"align":"wide","className":"","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"top"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null},"position":{"type":""}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydStyles":{"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))","responsive":{"sm":{"width":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"blockGap":"var:preset|spacing|medium"},"border":{"radius":"0px"},"dimensions":{"minHeight":"360px"},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"backgroundColor":"dark","textColor":"lightest","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
			<div class="wp-block-group has-lightest-color has-dark-background-color has-text-color has-background has-link-color" style="border-radius:0px;min-height:360px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-white.svg' ); ?>","tag":""},"greydStyles":{"width":"64px","height":"64px","objectFit":"contain"},"className":"","greydAnim":{"action":"filter","preset":"invert","from":100,"to":100,"event":"hover","parent":"","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":""}} /-->

				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"},"greydStyles":{"width":"","maxWidth":"480px"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"className":"","fontSize":"medium"} -->
					<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"center","className":""} -->
					<p class="has-text-align-center"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"blockGap":"var:preset|spacing|medium"},"border":{"radius":"0px"},"dimensions":{"minHeight":"60vh"},"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}}},"backgroundColor":"dark","textColor":"lightest","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
			<div class="wp-block-group has-lightest-color has-dark-background-color has-text-color has-background has-link-color" style="border-radius:0px;min-height:60vh;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/greyd-logo-white.svg' ); ?>","tag":""},"greydStyles":{"width":"64px","height":"64px","objectFit":"contain"},"className":"","greydAnim":{"action":"filter","preset":"invert","from":100,"to":100,"event":"hover","parent":"","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":""}} /-->

				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"},"greydStyles":{"width":"","maxWidth":"480px"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"className":"","fontSize":"medium"} -->
					<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"center","className":""} -->
					<p class="has-text-align-center"><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"layout":{"selfStretch":"fill","flexSize":null},"dimensions":{"minHeight":"60vh"},"border":{"radius":"0px","width":"8px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-and-black-cubes-and-pyramids.webp' ); ?>","source":"file","title":"white-and-black-cubes-and-pyramids"},"backgroundPosition":"50% 0"}},"backgroundColor":"foreground","textColor":"lightest","borderColor":"secondary","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydStyles":{"width":"","responsive":{"lg":{"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))"},"sm":{"width":"100%"}}}} -->
			<div class="wp-block-group has-border-color has-secondary-border-color has-lightest-color has-foreground-background-color has-text-color has-background has-link-color" style="border-width:8px;border-radius:0px;min-height:60vh;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)"></div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"},"border":{"radius":"0px"},"dimensions":{"minHeight":"360px"}},"backgroundColor":"secondary","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"center"}} -->
			<div class="wp-block-group has-secondary-background-color has-background" style="border-radius:0px;min-height:360px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:group {"className":"","style":{"spacing":{"blockGap":"var:preset|spacing|tiny","padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydStyles":{"width":"","maxWidth":null}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--x-large);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--x-large);padding-left:var(--wp--preset--spacing--medium)">
					<!-- wp:heading {"textAlign":"center","className":"","fontSize":"medium"} -->
					<h2 class="wp-block-heading has-text-align-center has-medium-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"align":"center","className":""} -->
					<p class="has-text-align-center">
						<?php
							printf( /* translators: The variables refer to the HTML tags for highlighting the second part of the sentence. */
								esc_html__( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore sed diam %1$snonumy eirmod tempor invidunt ut labore et dolore%2$s', 'greyd-theme' ),
								'<strong><mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-secondary-color">',
								'</mark></strong>'
							);
						?>
					</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:greyd/image {"image":{"type":"file","url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","tag":""},"className":""} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null},"position":{"type":"sticky","top":"0px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydStyles":{"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))","responsive":{"sm":{"width":"100%"}}}} -->
		<div class="wp-block-group">
			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|lightest"}}},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"layout":{"selfStretch":"fill","flexSize":null},"dimensions":{"minHeight":"60vh"},"border":{"radius":"0px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/white-interlaced-blocks-on-dark.webp' ); ?>","source":"file","title":"white-interlaced-blocks-on-dark"},"backgroundSize":"cover","backgroundPosition":"51% 36%"}},"backgroundColor":"darkest","textColor":"lightest","layout":{"type":"flex","orientation":"vertical","justifyContent":"left","verticalAlignment":"bottom"},"greydStyles":{"width":"","responsive":{"lg":{"width":"calc(50% - (var(--wp--preset--spacing--medium) / 1))"},"sm":{"width":"100%"}}}} -->
			<div class="wp-block-group has-lightest-color has-darkest-background-color has-text-color has-background has-link-color" style="border-radius:0px;min-height:60vh;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
				<!-- wp:group {"className":"","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"color":{"gradient":"linear-gradient(0deg,rgb(1,1,1) 0%,rgba(0,0,0,0) 98%)"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group has-background" style="background:linear-gradient(0deg,rgb(1,1,1) 0%,rgba(0,0,0,0) 98%);padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
					<!-- wp:heading {"className":"","fontSize":"medium"} -->
					<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"","style":{"layout":{"selfStretch":"fit","flexSize":null}}} -->
					<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'greyd_hub' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"","style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}},"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"}},"layout":{"selfStretch":"fill","flexSize":null},"dimensions":{"minHeight":"1px"},"border":{"radius":"0px"}},"backgroundColor":"tertiary","textColor":"foreground","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"},"greydStyles":{"width":"","responsive":{"sm":{"width":"100%"}}}} -->
			<div class="wp-block-group has-foreground-color has-tertiary-background-color has-text-color has-background has-link-color" style="border-radius:0px;min-height:1px;padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
				<!-- wp:heading {"className":"","fontSize":"medium"} -->
				<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Headline', 'greyd_hub' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"","style":{"layout":{"selfStretch":"fill","flexSize":null}}} -->
				<p>
					<?php
						printf( /* translators: The variables refer to the HTML tags for highlighting the second part of the sentence. */
							esc_html__( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, %1$ssed diam nonumy eirmod tempor invidunt%2$s', 'greyd-theme' ),
							'<strong>',
							'</strong>'
						);
					?>
				</p>
				<!-- /wp:paragraph -->

				<!-- wp:greyd/buttons {"className":""} -->
				<div class="wp-block-greyd-buttons">
					<!-- wp:greyd/button {"greydClass":"gs_<?php echo $_greyd_class; ?>","customStyles":{"borderRadius":"50px"},"content":"<?php esc_html_e( 'CTA Button', 'greyd_hub' ); ?>","custom":true,"className":"is-style-sec"} -->
						<a role="trigger" class="button is-style-sec gs_<?php echo $_greyd_class; ?>"><?php esc_html_e( 'CTA Button', 'greyd_hub' ); ?></a>
						<style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { border-radius: 50px !important; } </style>
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
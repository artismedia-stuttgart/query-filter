<?php
/**
 * Title: Three Pricing columns, in different colors, with group below
 * Slug: greyd-plugin/pricing-three-columns-different-colours
 * Description: Three columns with different pricing options and a call to action
 * Categories: greyd-pricing
 * Keywords: 
 * Viewport Width: 1400
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"className":"","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"stretch","justifyContent":"space-between"}} -->
<div class="wp-block-group">
	<!-- wp:group {"className":"","style":{"border":{"radius":"4px"},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"base","greydStyles":{"width":"calc(33% - (var(--wp--preset--spacing--medium) / 1))","responsive":{"md":{"width":"100%"}}}} -->
	<div class="wp-block-group has-base-background-color has-background" style="border-radius:4px">
		<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"top":"0"}}},"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size" style="margin-top:0"><?php esc_html_e( 'Monthly', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"","style":{"typography":{"lineHeight":"1.5"}},"fontSize":"normal"} -->
		<p class="has-normal-font-size" style="line-height:1.5"><?php esc_html_e( 'Most flexible option. Perfect if you want to try us out.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"","fontSize":"x-large"} -->
		<p class="has-x-large-font-size"><?php esc_html_e( '$6,99 /mo.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:greyd/buttons {"align":"full","className":"","layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"}} -->
		<div class="wp-block-greyd-buttons alignfull">
			<!-- wp:greyd/button {"greydStyles":{"width":"100%"},"content":"<?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?>","icon":{"content":"","position":"after","margin":"10px","size":"100%"},"className":"is-style-outline"} -->
			<a role="trigger" class="button is-style-outline "><?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->

		<!-- wp:paragraph {"className":"","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|tiny"}}}} -->
		<p style="margin-bottom:var(--wp--preset--spacing--tiny)"><strong><?php esc_html_e( "What's included", 'greyd_hub' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:list {"className":"","style":{"spacing":{"margin":{"top":"0","left":"0","right":"0","bottom":"0"}}}} -->
		<ul style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0" class="wp-block-list">
			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Unlimited updates', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Unlimited users', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Pause or cancel anytime', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"full","className":"","style":{"border":{"radius":"4px"},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"mediumlight","layout":{"type":"default"},"greydStyles":{"width":"calc(33% - (var(--wp--preset--spacing--medium) / 1))","responsive":{"md":{"width":"100%"}}}} -->
	<div class="wp-block-group alignfull has-mediumlight-background-color has-background" style="border-radius:4px">
		<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"top":"0"}}},"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size" style="margin-top:0"><?php esc_html_e( 'Quarterly', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"","style":{"typography":{"lineHeight":"1.5"}},"fontSize":"normal"} -->
		<p class="has-normal-font-size" style="line-height:1.5"><?php esc_html_e( 'For companies of all sizes, who know what they need.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"","fontSize":"x-large"} -->
		<p class="has-x-large-font-size"><?php esc_html_e( '$6,49 /mo.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:greyd/buttons {"align":"full","className":"","layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"}} -->
		<div class="wp-block-greyd-buttons alignfull">
			<!-- wp:greyd/button {"greydStyles":{"width":"100%"},"content":"<?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?>","icon":{"content":"","position":"after","margin":"10px","size":"100%"},"className":""} -->
			<a role="trigger" class="button"><?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->

		<!-- wp:paragraph {"className":"","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|tiny"}}}} -->
		<p style="margin-bottom:var(--wp--preset--spacing--tiny)"><strong><?php esc_html_e( "What's included", 'greyd_hub' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:list {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<ul style="margin-top:0;margin-bottom:0" class="wp-block-list">
			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Unlimited updates', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Unlimited users', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"className":""} -->
			<li><?php esc_html_e( 'Pause or cancel anytime', 'greyd_hub' ); ?></li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"full","className":"","style":{"border":{"radius":"4px"},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"foreground","textColor":"lightest","layout":{"type":"default"},"greydStyles":{"width":"calc(33% - (var(--wp--preset--spacing--medium) / 1))","responsive":{"md":{"width":"100%"}}}} -->
	<div class="wp-block-group alignfull has-lightest-color has-foreground-background-color has-text-color has-background" style="border-radius:4px">
		<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"top":"0"}}},"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-medium-font-size" style="margin-top:0"><?php esc_html_e( 'Yearly', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"","fontSize":"normal"} -->
		<p class="has-normal-font-size"><?php esc_html_e( 'The most cost-effective option for those who want a long-term relationship.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"","fontSize":"x-large"} -->
		<p class="has-x-large-font-size"><?php esc_html_e( '$5,99 /mo.', 'greyd_hub' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:greyd/buttons {"align":"full","className":"","layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"}} -->
		<div class="wp-block-greyd-buttons alignfull">
			<!-- wp:greyd/button {"greydStyles":{"width":"100%"},"content":"<?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?>","icon":{"content":"","position":"after","margin":"10px","size":"100%"},"className":"is-style-alternate"} -->
			<a role="trigger" class="button is-style-alternate"><?php esc_html_e( 'Buy now →', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->

		<!-- wp:paragraph {"className":"","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|tiny"}}}} -->
		<p style="margin-bottom:var(--wp--preset--spacing--tiny)"><strong><?php esc_html_e( "What's included", 'greyd_hub' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:list {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<ul style="margin-top:0;margin-bottom:0" class="wp-block-list"><!-- wp:list-item {"className":""} -->
		<li><?php esc_html_e( 'Unlimited updates', 'greyd_hub' ); ?></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item {"className":""} -->
		<li><?php esc_html_e( 'Unlimited users', 'greyd_hub' ); ?></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item {"className":""} -->
		<li><?php esc_html_e( 'Pause or cancel anytime', 'greyd_hub' ); ?></li>
		<!-- /wp:list-item --></ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"full","className":"","style":{"border":{"radius":"4px"},"layout":{"selfStretch":"fill","flexSize":null}},"backgroundColor":"lightest","layout":{"type":"default"}} -->
	<div class="wp-block-group alignfull has-lightest-background-color has-background" style="border-radius:4px">
		<!-- wp:group {"align":"full","className":"","style":{"spacing":{"blockGap":"var:preset|spacing|large"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
		<div class="wp-block-group alignfull">
			<!-- wp:group {"className":"","layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"className":"","style":{"spacing":{"margin":{"top":"0"}}},"fontSize":"medium"} -->
				<h2 class="wp-block-heading has-medium-font-size" style="margin-top:0"><?php esc_html_e( 'Are you interested in a custom price plan?', 'greyd_hub' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"","fontSize":"normal"} -->
				<p class="has-normal-font-size"><?php esc_html_e( "If your project doesn't fit in the above plans, or if you'd like to discuss before making up your mind, book a call with us.", 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:greyd/buttons {"align":"full","className":"","layout":{"type":"flex","justifyContent":"center","orientation":"horizontal"}} -->
			<div class="wp-block-greyd-buttons alignfull">
				<!-- wp:greyd/button {"content":"<?php esc_html_e( 'Call us', 'greyd_hub' ); ?>","icon":{"content":"","position":"after","margin":"10px","size":"100%"},"className":"is-style-outline"} -->
				<a role="trigger" class="button is-style-outline"><?php esc_html_e( 'Call us', 'greyd_hub' ); ?></a>
				<!-- /wp:greyd/button -->
			</div>
			<!-- /wp:greyd/buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
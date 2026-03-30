<?php
/**
 * Title: Bento Grid 3
 * Slug: greyd-plugin/grids-bento-grid-3
 * Description: 
 * Categories: greyd-grids
 * Keywords: 
 * Viewport Width: 1600
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:group {"align":"wide","className":"grid-max-5","style":{"dimensions":{"minHeight":"70vh"},"spacing":{"blockGap":"0"}},"layout":{"type":"grid","minimumColumnWidth":"24rem"}} -->
<div class="wp-block-group alignwide grid-max-5" style="min-height:70vh">
	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":3},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:heading {"className":""} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Your Layout.', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"","fontSize":"base"} -->
		<p class="has-base-font-size">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":2},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"top"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:heading {"textAlign":"left","className":"","style":{"typography":{"writingMode":"vertical-rl"}},"fontSize":"medium"} -->
		<h2 class="wp-block-heading has-text-align-left has-medium-font-size" style="writing-mode:vertical-rl"><?php esc_html_e( 'What about a change', 'greyd_hub' ); ?><br><?php esc_html_e( 'of perspective?', 'greyd_hub' ); ?></h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":3},"dimensions":{"minHeight":"240px"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
	<div class="wp-block-group has-base-background-color has-background" style="min-height:240px">
		<!-- wp:greyd/box {"greydStyles":{"padding":{"top":"0px","right":"0px","bottom":"var:preset|spacing|x-large","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"--sticky-offset":"32px"},"variation":"sticky","className":""} -->
		<div class="wp-block-greyd-box">
			<!-- wp:group {"className":"","style":{"position":{"type":""}},"layout":{"type":"default"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"className":""} -->
				<h2 class="wp-block-heading"><?php esc_html_e( 'Your Design.', 'greyd_hub' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":""} -->
				<p><?php esc_html_e( 'Bento / Masonry Grid 03', 'greyd_hub' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:greyd/box -->

		<!-- wp:greyd/buttons {"className":""} -->
		<div class="wp-block-greyd-buttons">
			<!-- wp:greyd/button {"content":"<?php esc_html_e( 'Button', 'greyd_hub' ); ?>","className":"is-style-sec"} -->
			<a role="trigger" class="button is-style-sec"><?php esc_html_e( 'Button', 'greyd_hub' ); ?></a>
			<!-- /wp:greyd/button -->
		</div>
		<!-- /wp:greyd/buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":1},"dimensions":{"minHeight":"240px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"secondary","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"parentHover","parent":".grid-max-5","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--primary)"}} -->
	<div class="wp-block-group has-secondary-background-color has-background" style="min-height:240px"></div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"","style":{"layout":{"rowSpan":1},"dimensions":{"minHeight":"240px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"mediumdark","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"parentHover","parent":".grid-max-5","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--base)"}} -->
	<div class="wp-block-group has-mediumdark-background-color has-background" style="min-height:240px"></div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"hidden-md hidden-sm hidden-xs","style":{"layout":{"rowSpan":2,"columnSpan":2},"dimensions":{"minHeight":"240px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"base","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"hide":{"xs":true,"sm":true,"md":true,"lg":false}} -->
	<div class="wp-block-group hidden-md hidden-sm hidden-xs has-base-background-color has-background" style="min-height:240px"></div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"hidden-md hidden-sm hidden-xs","style":{"layout":{"rowSpan":1},"dimensions":{"minHeight":"240px"},"background":{"backgroundImage":{"url":"<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'assets/img/dark-transparent-background-pattern.webp' ); ?>","source":"file","title":"dark-transparent-background-pattern"},"backgroundSize":"cover"}},"backgroundColor":"primary","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"hide":{"xs":true,"sm":true,"md":true,"lg":false},"greydAnim":{"action":"changeColor","preset":"fadeOut","from":"","to":"","origin":"center center","event":"parentHover","parent":".grid-max-5","start":"50%","end":"50%","reverse":false,"duration":200,"delay":0,"timing":"ease","color":"","background":"var(--wp--preset--color--secondary)"}} -->
	<div class="wp-block-group hidden-md hidden-sm hidden-xs has-primary-background-color has-background" style="min-height:240px"></div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
<?php
/**
 * Title: Responsive nested columns
 * Slug: greyd-plugin/grids-responsive-nested-columns
 * Description: 
 * Categories: greyd-grids
 * Keywords: 
 * Viewport Width: 1600
 * Block Types: 
 * Post Types: 
 * Inserter: true
 */
?>
<!-- wp:columns {"align":"wide","className":"is-style-default"} -->
<div class="wp-block-columns alignwide is-style-default">
	<!-- wp:column {"verticalAlignment":"stretch","className":"col-12 col-sm-auto","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"0"}},"responsive":{}} -->
	<div class="wp-block-column is-vertically-aligned-stretch col-12 col-sm-auto" style="padding-top:0;padding-bottom:0">
		<!-- wp:columns {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:0;margin-bottom:0">
			<!-- wp:column {"verticalAlignment":"stretch","className":"col-auto","backgroundColor":"base","responsive":{"width":{"xs":"col-auto","sm":""}}} -->
			<div class="wp-block-column is-vertically-aligned-stretch col-auto has-base-background-color has-background">
				<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p>Column 50%</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"stretch","className":"col-auto","backgroundColor":"base","responsive":{"width":{"xs":"col-auto","sm":""}}} -->
			<div class="wp-block-column is-vertically-aligned-stretch col-auto has-base-background-color has-background">
				<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p>Column 50%</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"stretch","className":"col-12 col-sm-auto","style":{"spacing":{"padding":{"top":"0","bottom":"0"},"blockGap":"0"}},"responsive":{}} -->
	<div class="wp-block-column is-vertically-aligned-stretch col-12 col-sm-auto" style="padding-top:0;padding-bottom:0">
		<!-- wp:columns {"className":"","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="margin-top:0;margin-bottom:0">
			<!-- wp:column {"verticalAlignment":"stretch","className":"","backgroundColor":"base","responsive":{"width":{"xs":"col-auto","sm":""}}} -->
			<div class="wp-block-column is-vertically-aligned-stretch has-base-background-color has-background">
				<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p>Column 50%</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"stretch","className":"","backgroundColor":"base","responsive":{"width":{"xs":"col-auto","sm":""}}} -->
			<div class="wp-block-column is-vertically-aligned-stretch has-base-background-color has-background">
				<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"className":""} -->
					<p>Column 50%</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<?php
/**
 * Title: Responsive nested columns 2/3 - 1/1
 * Slug: greyd-plugin/grids-responsive-nested-columns-two-thirds-one-column
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
	<!-- wp:column {"verticalAlignment":"stretch","className":"col-12 col-md-8","backgroundColor":"base","responsive":{"width":{"sm":"","md":"col-md-8"}}} -->
	<div class="wp-block-column is-vertically-aligned-stretch col-12 col-md-8 has-base-background-color has-background">
		<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":""} -->
			<p>Column 2/3</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"verticalAlignment":"stretch","className":"col-12 col-sm-auto"} -->
	<div class="wp-block-column is-vertically-aligned-stretch col-12 col-sm-auto">
		<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
		<div class="wp-block-group">
			<!-- wp:columns {"verticalAlignment":"top","className":""} -->
			<div class="wp-block-columns are-vertically-aligned-top">
				<!-- wp:column {"verticalAlignment":"top","className":"col-12","backgroundColor":"base","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column is-vertically-aligned-top col-12 has-base-background-color has-background">
					<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
					<div class="wp-block-group">
						<!-- wp:paragraph {"className":""} -->
						<p>Column 100%</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"verticalAlignment":"top","className":"col-12","backgroundColor":"base","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column is-vertically-aligned-top col-12 has-base-background-color has-background">
					<!-- wp:group {"className":"","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
					<div class="wp-block-group">
						<!-- wp:paragraph {"className":""} -->
						<p>Column 100%</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
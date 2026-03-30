<?php
/**
 * Title: Latest Posts with Live Search
 * Slug: greyd-plugin/query-live-search
 * Description: 
 * Categories: greyd-posts
 * Keywords: 
 * Viewport Width: 1200
 * Block Types: core/query
 * Post Types: 
 * Inserter: true
 */
?>
<?php $_greyd_class = substr( md5( uniqid() ), 0, 6 ); ?>
<!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"metadata":{"categories":["greyd-posts"],"patternName":"greyd-plugin/query-live-search","name":"Query with Live Search"},"className":""} -->
<div class="wp-block-query">
	<!-- wp:greyd/search {"posttype":"post","greydClass":"gs_<?php echo $_greyd_class; ?>","greydStyles":{"align":"flex-end","flexWrap":"nowrap","responsive":{"sm":{"wrap":"wrap"}}},"className":""} -->
	<form class="wp-block-greyd-search greyd-search-form gs_<?php echo $_greyd_class; ?> row" method="get" role="search">
		<input type="hidden" name="post_type" value="post">
		<!-- wp:greyd/search-input {"label":"Search","greydStyles":{"width":"100%"},"className":""} /-->
		<!-- wp:greyd/search-filter {"inherit":false,"parentPosttype":"post","filterBy":"category","label":"Filter","placeholder":"select","greydStyles":{"width":"max(200px, 50%)"},"className":""} /-->
		<style class="greyd-styles">.gs_<?php echo $_greyd_class; ?> { align: flex-end; flex-wrap: nowrap; } @media (max-width: 575.98px) { .gs_<?php echo $_greyd_class; ?> { wrap: wrap; } } </style>
	</form>
	<!-- /wp:greyd/search -->

	<!-- wp:spacer {"className":""} -->
	<div style="height:var(--wp--preset--spacing--large)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:post-template {"className":"","layout":{"type":"grid","columnCount":3,"items":3,"responsive":{"md":{"items":8,"columnCount":2},"sm":{"items":4,"columnCount":1}}},"variation":"slider"} -->
		<!-- wp:group {"className":"","style":{"spacing":{"margin":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
		<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--medium);margin-bottom:var(--wp--preset--spacing--medium)">
			<!-- wp:post-featured-image {"isLink":true,"className":"","style":{"color":[]}} /-->

			<!-- wp:post-date {"className":"","style":{"typography":{"fontSize":"16px","fontWeight":"500"}}} /-->

			<!-- wp:post-title {"level":3,"isLink":true,"className":"","style":{"spacing":{"margin":{"top":"var:preset|spacing|small","bottom":"var:preset|spacing|small"}}},"fontSize":"medium"} /-->

			<!-- wp:post-excerpt {"className":""} /--></div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
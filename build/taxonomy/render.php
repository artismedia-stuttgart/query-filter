<?php
if ( empty( $attributes['taxonomy'] ) ) {
	return;
}

$id = 'query-filter-' . wp_generate_uuid4();

$taxonomy = get_taxonomy( $attributes['taxonomy'] );

if ( empty( $block->context['query']['inherit'] ) ) {
	$query_id = $block->context['queryId'] ?? 0;
	$query_var = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
	$page_var = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
	$base_url = remove_query_arg( [ $query_var, $page_var ] );
} else {
	$query_var = sprintf( 'query-%s', $attributes['taxonomy'] );
	$page_var = 'page';
	$base_url = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
}

$terms = get_terms( [
	'hide_empty' => true,
	'taxonomy' => $attributes['taxonomy'],
	'number' => 100,
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

$term_icons = get_option( 'query_filter_term_icons', [] );

$current_value = $_GET[ $query_var ] ?? null;
// If no value is set in URL, check for default attribute.
if ( is_null( $current_value ) && ! empty( $attributes['defaultTerm'] ) ) {
	$current_value = $attributes['defaultTerm'];
}

$all_is_active = empty( $current_value );

ob_start();
?>
<li class="wp-block-query-filter-taxonomy__item wp-block-query-filter__item <?php echo $all_is_active ? 'is-active' : '' ?>">
	<a href="<?php echo esc_url( $base_url ) ?>" data-wp-on--click="actions.navigate">
		<span class="wp-block-query-filter__icon" <?php echo ! empty( $attributes['showIcons'] ) ? 'style="width:' . esc_attr( $attributes['iconSize'] ) . 'px; height:' . esc_attr( $attributes['iconSize'] ) . 'px;"' : ''; ?>></span>
		<span class="wp-block-query-filter__label-text"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'All', 'query-filter' ) ); ?></span>
	</a>
</li>
<?php
$all_item_html = ob_get_clean();

$term_items_html = '';
foreach ( $terms as $term ) {
	$is_active = ( $term->slug === wp_unslash( $current_value ?? '' ) );
	$url = add_query_arg( [ $query_var => $term->slug, $page_var => false ], $base_url );
	$icon_id = $term_icons[ $term->term_id ] ?? null;
	$icon_url = $icon_id ? wp_get_attachment_image_url( $icon_id, 'full' ) : null;
	
	ob_start();
	?>
	<li class="wp-block-query-filter-taxonomy__item wp-block-query-filter__item <?php echo $is_active ? 'is-active' : '' ?>">
		<a href="<?php echo esc_url( $url ) ?>" data-wp-on--click="actions.navigate">
			<span class="wp-block-query-filter__icon icon-<?php echo esc_attr( $attributes['taxonomy'] ); ?>-<?php echo esc_attr( $term->slug ); ?>" <?php echo ! empty( $attributes['showIcons'] ) ? 'style="width:' . esc_attr( $attributes['iconSize'] ) . 'px; height:' . esc_attr( $attributes['iconSize'] ) . 'px;"' : ''; ?>>
				<?php if ( ! empty( $attributes['showIcons'] ) && $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="" style="width: <?php echo esc_attr( $attributes['iconSize'] ); ?>px; height: <?php echo esc_attr( $attributes['iconSize'] ); ?>px; object-fit: contain;" />
				<?php endif; ?>
			</span>
			<span class="wp-block-query-filter__label-text">
				<?php echo esc_html( $term->name ); ?>
				<?php if ( ! empty( $attributes['showCount'] ) ) : ?>
					<span class="wp-block-query-filter__count">(<?php echo esc_html( $term->count ); ?>)</span>
				<?php endif; ?>
			</span>
		</a>
	</li>
	<?php
	$term_items_html .= ob_get_clean();
}
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?> data-wp-interactive="query-filter" data-wp-context="{}">
	<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text' ?>" for="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
	</label>
	<ul class="wp-block-query-filter-taxonomy__list wp-block-query-filter__list" id="<?php echo esc_attr( $id ); ?>">
		<?php if ( empty( $attributes['allLast'] ) ) echo $all_item_html; ?>
		<?php echo $term_items_html; ?>
		<?php if ( ! empty( $attributes['allLast'] ) ) echo $all_item_html; ?>
	</ul>
</div>

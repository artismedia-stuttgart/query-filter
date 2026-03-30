<?php
/**
 * Renders the `query-filter/taxonomy` block on the server.
 *
 * This block is rendered dynamically, respecting the state passed via block context.
 * It is architected to be stateless and compatible with the WordPress Interactivity API.
 */

if ( empty( $attributes['taxonomy'] ) ) {
	return;
}

// Basic block setup.
$taxonomy = get_taxonomy( $attributes['taxonomy'] );
if ( ! $taxonomy ) {
	return;
}
$id = 'query-filter-' . wp_generate_uuid4();
$query_id = $block->context['queryId'] ?? 0;
$query_context = $block->context['query'] ?? [];

// Determine the query variable and base URL based on whether the query is inherited.
$is_inherited = ! empty( $query_context['inherit'] );
if ( ! $is_inherited ) {
	$query_var = sprintf( 'query-%d-%s', $query_id, $attributes['taxonomy'] );
	$page_var  = 'query-' . $query_id . '-page';
	$base_url  = remove_query_arg( [ $query_var, $page_var ] );
} else {
	$query_var = sprintf( 'query-%s', $attributes['taxonomy'] );
	$page_var  = 'page';
	$base_url  = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
}

// Fetch terms to display.
$terms = get_terms( [
	'hide_empty' => true,
	'taxonomy'   => $attributes['taxonomy'],
	'number'     => 100,
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

// Determine the currently active term from the block context, not from `$_GET`.
$current_value = null;
$tax_query = $query_context['tax_query'] ?? null;
if ( $tax_query ) {
	foreach ( $tax_query as $query_part ) {
		if ( isset( $query_part['taxonomy'] ) && $query_part['taxonomy'] === $attributes['taxonomy'] && isset( $query_part['terms'][0] ) ) {
			// This assumes terms are stored by slug, which is how the pre_get_posts handler works.
			$current_value = $query_part['terms'][0];
			break;
		}
	}
}

// If no value is active, fall back to the default term attribute.
if ( is_null( $current_value ) && ! empty( $attributes['defaultTerm'] ) ) {
	$current_value = $attributes['defaultTerm'];
}

$all_is_active = empty( $current_value );

// Prepare the "All" item HTML.
ob_start();
?>
<li class="wp-block-query-filter-taxonomy__item wp-block-query-filter__item <?php echo $all_is_active ? 'is-active' : ''; ?>">
	<a href="<?php echo esc_url( $base_url ); ?>" data-wp-on--click="actions.navigate">
		<span class="wp-block-query-filter__icon" <?php echo ! empty( $attributes['showIcons'] ) ? 'style="width:' . esc_attr( $attributes['iconSize'] ) . 'px; height:' . esc_attr( $attributes['iconSize'] ) . 'px;"' : ''; ?>></span>
		<span class="wp-block-query-filter__label-text"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'Alle', 'query-filter' ) ); ?></span>
	</a>
</li>
<?php
$all_item_html = ob_get_clean();

// Reconstruct other active filters from the context to build correct links.
$other_filters = [];
$prefix = $is_inherited ? 'query-' : "query-{$query_id}-";

// Reconstruct taxonomy filters from context.
if ( isset( $query_context['tax_query'] ) ) {
	foreach ( $query_context['tax_query'] as $tax_rule ) {
		if ( isset( $tax_rule['taxonomy'], $tax_rule['terms'][0] ) ) {
			$key = $prefix . $tax_rule['taxonomy'];
			// Exclude the filter for the current block, as we are building its links.
			if ( $key !== $query_var ) {
				$other_filters[ $key ] = $tax_rule['terms'][0];
			}
		}
	}
}

// Reconstruct other filters like search and sort from context.
$other_keys = [ 's', 'orderby', 'order' ];
foreach ( $other_keys as $key ) {
	if ( ! empty( $query_context[ $key ] ) ) {
		$param_key = $prefix . $key;
		// Exclude the current block's query var (unlikely to match but safe).
		if ( $param_key !== $query_var ) {
			$other_filters[ $param_key ] = $query_context[ $key ];
		}
	}
}


// Prepare each term item HTML.
$term_items_html = '';
$term_icons      = get_option( 'query_filter_term_icons', [] );
foreach ( $terms as $term ) {
	$is_active = ( $term->slug === $current_value );
	$url = add_query_arg( [ $query_var => $term->slug ], $base_url );

	// Preserve other active filters by adding them to the URL.
	foreach ( $other_filters as $key => $value ) {
		$url = add_query_arg( $key, $value, $url );
	}

	$icon_id  = $term_icons[ $term->term_id ] ?? null;
	$icon_url = $icon_id ? wp_get_attachment_image_url( $icon_id, 'full' ) : null;

	ob_start();
	?>
	<li class="wp-block-query-filter-taxonomy__item wp-block-query-filter__item <?php echo $is_active ? 'is-active' : ''; ?>">
		<a href="<?php echo esc_url( $url ); ?>" data-wp-on--click="actions.navigate">
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

// Final block output.
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?> data-wp-interactive="query-filter" data-wp-router-region="query-filter-taxonomy-<?php echo esc_attr( $query_id . '-' . $attributes['taxonomy'] ); ?>" data-wp-context="{}">
	<label class="wp-block-query-filter-taxonomy__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text'; ?>" for="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $attributes['label'] ?? $taxonomy->label ); ?>
	</label>
	<ul class="wp-block-query-filter-taxonomy__list wp-block-query-filter__list" id="<?php echo esc_attr( $id ); ?>">
		<?php if ( empty( $attributes['allLast'] ) ) : ?>
			<?php echo $all_item_html; ?>
		<?php endif; ?>
		<?php echo $term_items_html; ?>
		<?php if ( ! empty( $attributes['allLast'] ) ) : ?>
			<?php echo $all_item_html; ?>
		<?php endif; ?>
	</ul>
</div>

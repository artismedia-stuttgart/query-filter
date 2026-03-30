<?php
/**
 * Renders the `query-filter/post-type` block on the server.
 *
 * This block is rendered dynamically, respecting the state passed via block context.
 * It is architected to be stateless and compatible with the WordPress Interactivity API.
 */

$id = 'query-filter-' . wp_generate_uuid4();

// Get state from the block's context, which is the single source of truth.
$query_context = $block->context['query'] ?? [];
$query_id      = $block->context['queryId'] ?? 0;
$is_inherited  = ! empty( $query_context['inherit'] );

// Determine the query variable and base URL.
if ( $is_inherited ) {
	$query_var = 'query-post_type';
	$page_var  = 'page';
	$base_url  = str_replace( '/page/' . get_query_var( 'paged' ), '', remove_query_arg( [ $query_var, $page_var ] ) );
} else {
	$query_var = sprintf( 'query-%d-post_type', $query_id );
	$page_var  = 'query-' . $query_id . '-page';
	$base_url  = remove_query_arg( [ $query_var, $page_var ] );
}

// Determine the list of post types to display from the context.
$post_types_from_context = array_map( 'trim', explode( ',', $query_context['postType'] ?? 'post' ) );
if ( isset( $query_context['multiple_posts'] ) && is_array( $query_context['multiple_posts'] ) ) {
	$post_types_from_context = array_merge( $post_types_from_context, $query_context['multiple_posts'] );
}
if ( $is_inherited ) {
	$inherited_post_types = ( $query_context['query-filter-post_type'] ?? 'any' ) === 'any'
		? get_post_types( [ 'public' => true, 'exclude_from_search' => false ] )
		: (array) ( $query_context['query-filter-post_type'] ?? [] );

	$post_types_from_context = array_merge( $post_types_from_context, $inherited_post_types );
	if ( ! get_option( 'wp_attachment_pages_enabled' ) ) {
		$post_types_from_context = array_diff( $post_types_from_context, [ 'attachment' ] );
	}
}

$post_types = array_unique( $post_types_from_context );
$post_types = array_map( 'get_post_type_object', $post_types );
$post_types = array_filter( $post_types ); // Remove any null results from get_post_type_object.

if ( empty( $post_types ) ) {
	return;
}

// Determine the currently active post type from the context.
$current_value = $query_context['post_type'] ?? null;
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?> data-wp-interactive="query-filter" data-wp-router-region="query-filter-post-type-<?php echo esc_attr( $query_id ); ?>" data-wp-context="{}">
	<label class="wp-block-query-filter-post-type__label wp-block-query-filter__label<?php echo $attributes['showLabel'] ? '' : ' screen-reader-text'; ?>" for="<?php echo esc_attr( $id ); ?>">
		<?php echo esc_html( $attributes['label'] ?? __( 'Content Type', 'query-filter' ) ); ?>
	</label>
	<ul class="wp-block-query-filter-post-type__list wp-block-query-filter__list" id="<?php echo esc_attr( $id ); ?>">
		<?php
		// The "Alle" (All) link is active if the current post type is not in our list,
		// or if multiple post types are active (e.g., 'any').
		$all_is_active = ! in_array( $current_value, wp_list_pluck( $post_types, 'name' ), true );
		?>
		<li class="wp-block-query-filter-post-type__item wp-block-query-filter__item <?php echo $all_is_active ? 'is-active' : ''; ?>">
			<a href="<?php echo esc_url( $base_url ); ?>" data-wp-on--click="actions.navigate">
				<span class="wp-block-query-filter__icon"></span>
				<span class="wp-block-query-filter__label-text"><?php echo esc_html( $attributes['emptyLabel'] ?: __( 'Alle', 'query-filter' ) ); ?></span>
			</a>
		</li>
		<?php foreach ( $post_types as $post_type ) : ?>
			<?php
				$is_active = ( $post_type->name === $current_value );
				$url       = add_query_arg( [ $query_var => $post_type->name, $page_var => false ], $base_url );
			?>
			<li class="wp-block-query-filter-post-type__item wp-block-query-filter__item <?php echo $is_active ? 'is-active' : ''; ?>">
				<a href="<?php echo esc_url( $url ); ?>" data-wp-on--click="actions.navigate">
					<span class="wp-block-query-filter__icon"></span>
					<span class="wp-block-query-filter__label-text"><?php echo esc_html( $post_type->label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

<?php
/**
 * Renders the `query-filter/sort` block on the server.
 *
 * This block is rendered dynamically, respecting the state passed via block context.
 * It is architected to be stateless and compatible with the WordPress Interactivity API.
 */

$query_context = $block->context['query'] ?? [];
$query_id      = $block->context['queryId'] ?? 0;
$is_inherited  = ! empty( $query_context['inherit'] );

// Determine the query variable and base URL.
if ( $is_inherited ) {
	$orderby_var = 'query-orderby';
	$order_var   = 'query-order';
	$page_var    = 'page';
} else {
	$orderby_var = sprintf( 'query-%d-orderby', $query_id );
	$order_var   = sprintf( 'query-%d-order', $query_id );
	$page_var    = 'query-' . $query_id . '-page';
}

// Determine the current sort order from the block context, with fallbacks.
$current_orderby = $query_context['orderby'] ?? 'date';
$current_order   = $query_context['order'] ?? 'DESC';
$current_sort    = trim( $current_orderby . ' ' . $current_order );

$sort_options = [
	'date DESC'  => __( 'Chronologisch (neueste zuerst)', 'query-filter' ),
	'date ASC'   => __( 'Chronologisch (älteste zuerst)', 'query-filter' ),
	'title ASC'  => __( 'Alphabetisch (aufsteigend)', 'query-filter' ),
	'title DESC' => __( 'Alphabetisch (absteigend)', 'query-filter' ),
];

$base_url       = remove_query_arg( [ $orderby_var, $order_var, $page_var ] );
$selected_label = $sort_options[ $current_sort ] ?? reset( $sort_options );
$layout         = $attributes['layout'] ?? 'links';
?>

<div <?php echo get_block_wrapper_attributes( [ 'class' => 'wp-block-query-filter' ] ); ?>
	data-wp-interactive="query-filter"
	data-wp-router-region="query-filter-sort-<?php echo esc_attr( $query_id ); ?>"
	data-wp-context='{ "isOpen": false }'
	data-wp-on-document--click="actions.closeSorting"
>
	<?php if ( ! empty( $attributes['label'] ) ) : ?>
		<span class="wp-block-query-filter__label"><?php echo esc_html( $attributes['label'] ); ?></span>
	<?php endif; ?>

	<?php if ( $layout === 'links' ) : ?>
		<ul class="sorting-links">
				<?php foreach ( $sort_options as $value => $label ) : ?>
					<?php
						list($opt_orderby, $opt_order) = explode( ' ', $value );
						$is_active = ( $current_sort === $value );
						$sort_url  = add_query_arg( [ $orderby_var => $opt_orderby, $order_var => $opt_order ], $base_url );
					?>
					<li class="sorting-item <?php echo $is_active ? 'is-active' : ''; ?>">
						<a href="<?php echo esc_url( $sort_url ); ?>" data-wp-on--click="actions.navigate">
							<?php echo esc_html( $label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="qf-dropdown">
				<!-- Hidden elements for compatibility -->
				<input type="hidden" name="<?php echo esc_attr( $orderby_var ); ?>" value="<?php echo esc_attr( $current_orderby ); ?>" autocomplete="off">
				<input type="hidden" name="<?php echo esc_attr( $order_var ); ?>" value="<?php echo esc_attr( $current_order ); ?>" autocomplete="off">
				<select style="display:none !important;" aria-hidden="true" tabindex="-1">
					<?php foreach ( $sort_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<button
					type="button"
					class="qf-dropdown-trigger"
					data-wp-on--click="actions.toggleSorting"
					data-wp-bind--aria-expanded="context.isOpen"
				>
					<span class="qf-dropdown-label"><?php echo esc_html( $selected_label ); ?></span>
					<span class="qf-dropdown-arrow" aria-hidden="true"></span>
				</button>
				
				<div class="qf-dropdown-content" data-wp-class--qf-show="context.isOpen">
					<ul class="qf-dropdown-list">
						<?php foreach ( $sort_options as $value => $label ) : ?>
							<?php
								list($opt_orderby, $opt_order) = explode( ' ', $value );
								$sort_url  = add_query_arg( [ $orderby_var => $opt_orderby, $order_var => $opt_order ], $base_url );
								$is_active = ( $current_sort === $value );
							?>
							<li class="qf-dropdown-item <?php echo $is_active ? 'is-active' : ''; ?>">
								<button
									type="button"
									data-wp-on--click="actions.navigate"
									data-href="<?php echo esc_url( $sort_url ); ?>"
								>
									<?php echo esc_html( $label ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>
</div>

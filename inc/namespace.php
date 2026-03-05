<?php
/**
 * Query filter main file.
 *
 * @package query-filter
 */

namespace HM\Query_Loop_Filter;

use WP_HTML_Tag_Processor;
use WP_Query;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap() : void {
	// General hooks.
	add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\\filter_query_loop_block_query_vars', 10, 3 );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\pre_get_posts_transpose_query_vars' );
	add_filter( 'block_type_metadata', __NAMESPACE__ . '\\filter_block_type_metadata', 10 );
	add_action( 'init', __NAMESPACE__ . '\\register_blocks' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\action_wp_enqueue_scripts' );

	// Settings.
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_settings_page' );
	add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
	add_action( 'admin_init', __NAMESPACE__ . '\\admin_handle_save' );

	// Search.
	add_filter( 'render_block_core/search', __NAMESPACE__ . '\\render_block_search', 10, 3 );

	// Query.
	add_filter( 'render_block_core/query', __NAMESPACE__ . '\\render_block_query', 10, 3 );
}

/**
 * Fires when scripts and styles are enqueued.
 *
 * @TODO work out why this doesn't work but building interactivity via the blocks does.
 */
function action_wp_enqueue_scripts() : void {
	$asset = include ROOT_DIR . '/build/taxonomy/index.asset.php';
	wp_register_style(
		'query-filter-view',
		plugins_url( '/build/taxonomy/index.css', PLUGIN_FILE ),
		[],
		$asset['version']
	);
}

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 */
function register_blocks() : void {
	register_block_type( ROOT_DIR . '/build/taxonomy' );
	register_block_type( ROOT_DIR . '/build/post-type' );
}

/**
 * Register the settings page.
 *
 * @return void
 */
function register_settings_page() : void {
	add_options_page(
		__( 'Query Loop Filter Settings', 'query-filter' ),
		__( 'Query Loop Filter', 'query-filter' ),
		'manage_options',
		'query-filter-settings',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * Register the settings.
 *
 * @return void
 */
function register_settings() : void {
	register_setting(
		'query-filter-settings',
		'query_filter_term_icons',
		[
			'type' => 'object',
			'show_in_rest' => [
				'schema' => [
					'type' => 'object',
					'additionalProperties' => [
						'type' => 'integer',
					],
				],
			],
			'default' => [],
		]
	);
}

/**
 * Handle form submission for the settings page.
 *
 * @return void
 */
function admin_handle_save() : void {
	if ( ! is_admin() || ! isset( $_POST['query_filter_settings_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['query_filter_settings_nonce'], 'query_filter_save_settings' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$icons = isset( $_POST['term_icons'] ) ? (array) $_POST['term_icons'] : [];
	$icons = array_map( 'absint', array_filter( $icons ) );

	update_option( 'query_filter_term_icons', $icons );

	wp_safe_redirect( add_query_arg( 'updated', 'true', wp_get_referer() ) );
	exit;
}

/**
 * Render the settings page.
 *
 * @return void
 */
function render_settings_page() : void {
	wp_enqueue_media();
	$term_icons = get_option( 'query_filter_term_icons', [] );
	$taxonomies = get_taxonomies( [ 'publicly_queryable' => true ], 'objects' );
	?>
	<style>
		.query-filter-settings-table {
			width: auto;
			min-width: 600px;
			margin-bottom: 2em;
		}
		.query-filter-settings-table th, 
		.query-filter-settings-table td {
			vertical-align: middle;
		}
		.col-name {
			min-width: 200px;
		}
		.col-icon {
			width: 100px;
			min-width: 100px;
			text-align: center;
		}
		.col-actions {
			width: 250px;
			min-width: 250px;
		}
		.term-icon-preview {
			margin: 0 auto;
		}
	</style>
	<div class="wrap">
		<h1><?php esc_html_e( 'Query Loop Filter Settings', 'query-filter' ); ?></h1>
		
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Settings saved.', 'query-filter' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'query_filter_save_settings', 'query_filter_settings_nonce' ); ?>
			
			<?php foreach ( $taxonomies as $taxonomy ) : ?>
				<?php 
				$terms = get_terms( [
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				] );
				if ( empty( $terms ) || is_wp_error( $terms ) ) continue;
				?>
				<h2><?php echo esc_html( $taxonomy->label ); ?></h2>
				<table class="widefat fixed striped query-filter-settings-table">
					<thead>
						<tr>
							<th class="col-name"><?php esc_html_e( 'Term Name', 'query-filter' ); ?></th>
							<th class="col-icon"><?php esc_html_e( 'Icon', 'query-filter' ); ?></th>
							<th class="col-actions"><?php esc_html_e( 'Actions', 'query-filter' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $terms as $term ) : ?>
							<?php $icon_id = $term_icons[ $term->term_id ] ?? 0; ?>
							<tr>
								<td class="col-name"><?php echo esc_html( $term->name ); ?></td>
								<td class="col-icon">
									<div class="term-icon-preview" id="preview-<?php echo esc_attr( $term->term_id ); ?>" style="width: 50px; height: 50px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff;">
										<?php if ( $icon_id ) : ?>
											<?php echo wp_get_attachment_image( $icon_id, [ 50, 50 ], false, [ 'style' => 'max-width:100%; height:auto; object-fit:contain;' ] ); ?>
										<?php endif; ?>
									</div>
									<input type="hidden" name="term_icons[<?php echo esc_attr( $term->term_id ); ?>]" id="input-<?php echo esc_attr( $term->term_id ); ?>" value="<?php echo esc_attr( $icon_id ); ?>">
								</td>
								<td class="col-actions">
									<button type="button" class="button select-term-icon" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
										<?php esc_html_e( 'Select Icon', 'query-filter' ); ?>
									</button>
									<button type="button" class="button remove-term-icon" data-term-id="<?php echo esc_attr( $term->term_id ); ?>" <?php echo $icon_id ? '' : 'style="display:none;"'; ?>>
										<?php esc_html_e( 'Remove', 'query-filter' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'query-filter' ); ?>">
			</p>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var frame;
		$('.select-term-icon').on('click', function(e) {
			e.preventDefault();
			var $button = $(this);
			var termId = $button.data('term-id');
			
			frame = wp.media({
				title: '<?php esc_attr_e( 'Select Icon', 'query-filter' ); ?>',
				button: { text: '<?php esc_attr_e( 'Use Icon', 'query-filter' ); ?>' },
				multiple: false
			});

			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#input-' + termId).val(attachment.id);
				var img = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				$('#preview-' + termId).html('<img src="' + img + '" style="max-width:100%; height:auto;">');
				$button.next('.remove-term-icon').show();
			});

			frame.open();
		});

		$('.remove-term-icon').on('click', function(e) {
			e.preventDefault();
			var termId = $(this).data('term-id');
			$('#input-' + termId).val('');
			$('#preview-' + termId).empty();
			$(this).hide();
		});
	});
	</script>
	<?php
}

/**
 * Filters the arguments which will be passed to `WP_Query` for the Query Loop Block.
 *
 * @param array     $query Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 * @param \WP_Block $block Block instance.
 * @param int       $page  Current query's page.
 * @return array Array containing parameters for <code>WP_Query</code> as parsed by the block context.
 */
function filter_query_loop_block_query_vars( array $query, \WP_Block $block, int $page ) : array {
	if ( isset( $block->context['queryId'] ) ) {
		$query['query_id'] = $block->context['queryId'];
	}

	return $query;
}

/**
 * Fires after the query variable object is created, but before the actual query is run.
 *
 * @param  WP_Query $query The WP_Query instance (passed by reference).
 */
function pre_get_posts_transpose_query_vars( WP_Query $query ) : void {
	$query_id = $query->get( 'query_id', null );

	if ( ! $query->is_main_query() && is_null( $query_id ) ) {
		return;
	}

	$prefix = $query->is_main_query() ? 'query-' : "query-{$query_id}-";
	$tax_query = [];
	$valid_keys = [
		'post_type' => $query->is_search() ? 'any' : 'post',
		's' => '',
	];

	// Preserve valid params for later retrieval.
	foreach ( $valid_keys as $key => $default ) {
		$query->set(
			"query-filter-$key",
			$query->get( $key, $default )
		);
	}

	// Map get params to this query.
	foreach ( $_GET as $key => $value ) {
		if ( strpos( $key, $prefix ) === 0 ) {
			$key = str_replace( $prefix, '', $key );
			$value = sanitize_text_field( urldecode( wp_unslash( $value ) ) );

			// Handle taxonomies specifically.
			if ( get_taxonomy( $key ) ) {
				$tax_query['relation'] = 'AND';
				$tax_query[] = [
					'taxonomy' => $key,
					'terms' => [ $value ],
					'field' => 'slug',
				];
			} else {
				// Other options should map directly to query vars.
				$key = sanitize_key( $key );

				if ( ! in_array( $key, array_keys( $valid_keys ), true ) ) {
					continue;
				}

				$query->set(
					$key,
					$value
				);
			}
		}
	}

	if ( ! empty( $tax_query ) ) {
		$existing_query = $query->get( 'tax_query', [] );

		if ( ! empty( $existing_query ) ) {
			$tax_query = [
				'relation' => 'AND',
				[ $existing_query ],
				$tax_query,
			];
		}

		$query->set( 'tax_query', $tax_query );
	}
}

/**
 * Filters the settings determined from the block type metadata.
 *
 * @param array $metadata Metadata provided for registering a block type.
 * @return array Array of metadata for registering a block type.
 */
function filter_block_type_metadata( array $metadata ) : array {
	// Add query context to search block.
	if ( $metadata['name'] === 'core/search' ) {
		$metadata['usesContext'] = array_merge( $metadata['usesContext'] ?? [], [ 'queryId', 'query' ] );
	}

	return $metadata;
}

/**
 * Filters the content of a single block.
 *
 * @param string    $block_content The block content.
 * @param array     $block         The full block, including name and attributes.
 * @param \WP_Block $instance      The block instance.
 * @return string The block content.
 */
function render_block_search( string $block_content, array $block, \WP_Block $instance ) : string {
	if ( empty( $instance->context['query'] ) ) {
		return $block_content;
	}

	wp_enqueue_script_module( 'query-filter-taxonomy-view-script-module' );

	$query_var = empty( $instance->context['query']['inherit'] )
		? sprintf( 'query-%d-s', $instance->context['queryId'] ?? 0 )
		: 'query-s';

	$action = str_replace( '/page/'. get_query_var( 'paged', 1 ), '', add_query_arg( [ $query_var => '' ] ) );

	// Note sanitize_text_field trims whitespace from start/end of string causing unexpected behaviour.
	$value = wp_unslash( $_GET[ $query_var ] ?? '' );
	$value = urldecode( $value );
	$value = wp_check_invalid_utf8( $value );
	$value = wp_pre_kses_less_than( $value );
	$value = strip_tags( $value );

	wp_interactivity_state( 'query-filter', [
		'searchValue' => $value,
	] );

	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag( [ 'tag_name' => 'form' ] );
	$block_content->set_attribute( 'action', $action );
	$block_content->set_attribute( 'data-wp-interactive', 'query-filter' );
	$block_content->set_attribute( 'data-wp-on--submit', 'actions.search' );
	$block_content->set_attribute( 'data-wp-context', '{searchValue:""}' );
	$block_content->next_tag( [ 'tag_name' => 'input', 'class_name' => 'wp-block-search__input' ] );
	$block_content->set_attribute( 'name', $query_var );
	$block_content->set_attribute( 'inputmode', 'search' );
	$block_content->set_attribute( 'value', $value );
	$block_content->set_attribute( 'data-wp-bind--value', 'state.searchValue' );
	$block_content->set_attribute( 'data-wp-on--input', 'actions.search' );

	return (string) $block_content;
}

/**
 * Add data attributes to the query block to describe the block query.
 *
 * @param string    $block_content Default query content.
 * @param array     $block         Parsed block.
 * @return string
 */
function render_block_query( $block_content, $block ) {
	$block_content = new WP_HTML_Tag_Processor( $block_content );
	$block_content->next_tag();

	// Always allow region updates on interactivity, use standard core region naming.
	$block_content->set_attribute( 'data-wp-interactive', 'query-filter' );
	$block_content->set_attribute( 'data-wp-router-region', 'query-' . ( $block['attrs']['queryId'] ?? 0 ) );

	return (string) $block_content;
}

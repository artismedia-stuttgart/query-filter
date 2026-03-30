<?php
/**
 * Exclude posts & terms from search
 *
 * @since 1.2.7
 */
namespace Greyd\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Exclude();
class Exclude {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// filter search
		add_filter( 'pre_get_posts', array( $this, 'filter_the_query' ), 20 );

		// post metabox
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'post_updated', array( $this, 'save_post_field' ) );
		add_action( 'edit_attachment', array( $this, 'save_post_field' ) );

		// term fields
		// keep priority higher than Posttypes::add_dynamic_posttypes()
		add_action( 'init', array( $this, 'add_term_fields' ), 20 );

		// general settings
		add_filter( 'greyd_advanced_search_settings_default', array( $this, 'add_default_settings' ) );
		add_filter( 'greyd_advanced_search_settings_more', array( $this, 'render_settings' ) );
		add_filter( 'greyd_advanced_search_settings_save', array( $this, 'filter_settings_on_save' ), 10, 3 );
	}



	/**
	 * =================================================================
	 *                          Search
	 * =================================================================
	 */

	/**
	 * Filter the search query.
	 */
	public function filter_the_query( $query ) {

		if (
			( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			&& ( !isset($_REQUEST["context"]) || $_REQUEST["context"] != "edit" )
			&& $query->is_search
		) {
			// posts
			$query->set(
				'post__not_in',
				array_merge(
					$query->get( 'post__not_in' ),
					$this->get_excluded_posts()
				)
			);

			// terms
			$tax_query = array();
			$term_ids  = $this->get_excluded_terms();
			foreach ( $term_ids as $term_id ) {

				$term = get_term( $term_id );
				if ( ! $term || is_wp_error($term) ) {
					continue;
				}

				if ( ! isset( $tax_query[ $term->taxonomy ] ) ) {
					$tax_query[ $term->taxonomy ] = array(
						'taxonomy' => $term->taxonomy,
						'field'    => 'id',
						'terms'    => array(),
						'operator' => 'NOT IN',
					);
				}

				$tax_query[ $term->taxonomy ]['terms'][] = $term->term_id;
			}
			$query->set(
				'tax_query',
				array_merge(
					empty( $query->get( 'tax_query' ) ) ? array() : (array) $query->get( 'tax_query' ),
					array_values( $tax_query )
				)
			);
		}

		return $query;
	}



	/**
	 * =================================================================
	 *                          Posts
	 * =================================================================
	 */

	/**
	 * Add the metabox
	 */
	public function add_metabox() {

		$current_screen = get_current_screen();

		if ( ! empty( $current_screen->post_type ) &&
			array_search( $current_screen->post_type, $this->get_supported_posttypes() ) !== false
		) {
			add_meta_box(
				'search_exclude_metabox',
				__( 'Advanced Search', 'greyd_hub' ),
				array( $this, 'render_metabox' ),
				null,
				'side'
			);
		}
	}

	/**
	 * Render the post metabox
	 *
	 * @param WP_Post $post
	 */
	public function render_metabox( $post ) {
		wp_nonce_field( 'search_exclude_nonce_action', '_search_exclude_nonce' );
		?>
		<div class="misc-pub-section">
			<label for="search_exclude_post">
				<input type="hidden" name="search_exclude_post[hidden]" id="search_exclude_post_hidden" value="1" />
				<input type="checkbox" name="search_exclude_post[exclude]" id="search_exclude_post" value="1" <?php echo $this->is_post_excluded( $post ) ? 'checked' : ''; ?> />
				<?php echo __( "Exclude this post from search", 'greyd_hub' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * When a post is saved.
	 *
	 * @param int $post_id
	 */
	public function save_post_field( $post_id ) {

		if (
			! isset( $_POST['search_exclude_post'] )
			|| ! isset( $_POST['_search_exclude_nonce'] )
			|| ! wp_verify_nonce( $_POST['_search_exclude_nonce'], 'search_exclude_nonce_action' )
		) {
			return $post_id;
		}

		$array_value = $_POST['search_exclude_post'];
		$is_checked  = isset( $array_value['exclude'] ) ? filter_var( $array_value['exclude'], FILTER_VALIDATE_BOOLEAN ) : false;

		$this->update_single_post( $post_id, $is_checked );

		return $post_id;
	}

	/**
	 * Update the exclusion of a single post.
	 *
	 * @param int  $post_id  WP_Post ID.
	 * @param bool $exclude  Whether this post should be excluded from search.
	 */
	public function update_single_post( $post_id, $exclude ) {

		$option = $this->get_excluded_posts();

		if ( $exclude ) {
			$option = array_unique( array_merge( $option, array( intval( $post_id ) ) ) );
		} else {
			$option = array_diff( $option, array( intval( $post_id ) ) );
		}

		$this->save_excluded_posts( $option );
	}



	/**
	 * =================================================================
	 *                          Terms
	 * =================================================================
	 */

	/**
	 * Add the hooks
	 */
	public function add_term_fields() {

		foreach ( $this->get_supported_posttypes() as $post_type ) {

			$taxonomies = get_object_taxonomies( $post_type );

			if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					add_action( "{$taxonomy}_add_form_fields", array( $this, 'render_create_term_field' ) );
					add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render_edit_term_field' ) );
					add_action( "edited_{$taxonomy}", array( $this, 'save_term_field' ), 10, 2 );
					add_action( "create_{$taxonomy}", array( $this, 'save_term_field' ), 10, 2 );
				}
			}
		}
	}


	/**
	 * Add a field when a term is added.
	 */
	public function render_create_term_field() {
		?>
		<div class="form-field term-meta-wrap">
			<label for="search_exclude_term">
				<input type="hidden" name="search_exclude_term[hidden]" id="sep_hidden" value="1" />
				<input type="checkbox" name="search_exclude_term[exclude]" id="search_exclude_term" value="1" />
				<?php _e( "Exclude from search", 'greyd_hub' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Add a field when a term is edited.
	 *
	 * @param WP_Term $term
	 */
	public function render_edit_term_field( $term ) {
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="cat_Image_url"><?php _e( 'Advanced Search', 'greyd_hub' ); ?></label>
			</th>
			<td>
				<label for="search_exclude_term">
					<input type="hidden" name="search_exclude_term[hidden]" id="sep_hidden" value="1" />
					<input type="checkbox" name="search_exclude_term[exclude]" id="search_exclude_term" value="1" <?php echo $this->is_term_excluded( $term ) ? 'checked' : ''; ?> />
					<?php _e( "Exclude from search", 'greyd_hub' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * When a term is saved.
	 *
	 * @param int $term_id  Term ID.
	 * @param int $tt_id    Term taxonomy ID.
	 */
	public function save_term_field( $term_id, $tt_id ) {

		$exclude = isset( $_POST['search_exclude_term'] ) && isset( $_POST['search_exclude_term']['exclude'] ) && $_POST['search_exclude_term']['exclude'] == '1';

		$this->update_single_term( $term_id, $exclude );
	}

	/**
	 * Update the exclusion of a single term.
	 *
	 * @param int  $term_id   Term ID.
	 * @param bool $exclude  Whether this term should be excluded from search.
	 */
	public function update_single_term( $term_id, $exclude ) {

		$option = $this->get_excluded_terms();

		if ( $exclude ) {
			$option = array_unique( array_merge( $option, array( intval( $term_id ) ) ) );
		} else {
			$option = array_diff( $option, array( intval( $term_id ) ) );
		}

		$this->save_excluded_terms( $option );
	}



	/**
	 * =================================================================
	 *                          Settings
	 * =================================================================
	 */

	/**
	 * Add default settings.
	 *
	 * @see filter 'greyd_advanced_search_settings_default'
	 *
	 * @param array $defaults       All current default settings.
	 *
	 * @return array $defaults
	 */
	public function add_default_settings( $defaults ) {

		$defaults['advanced_search']['exclude_posts_from_search'] = '';
		$defaults['advanced_search']['exclude_terms_from_search'] = '';
		// debug($defaults);

		return $defaults;
	}

	/**
	 * Render the settings
	 *
	 * @see filter 'greyd_advanced_search_settings_more'
	 */
	public function render_settings() {

		/**
		 * Posts
		 */
		$post_data = array();
		$post_ids  = $this->get_excluded_posts();

		if ( ! empty( $post_ids ) ) {
			$posts = get_posts(
				array(
					'post_type'   => 'any',
					'post_status' => 'any',
					'nopaging'    => true,
					'post__in'    => $post_ids,
				)
			);
			if ( ! empty( $posts ) ) {
				$post_data = array_map(
					function( $post ) {
						return (object) array(
							'ID'    => $post->ID,
							'title' => $post->post_title,
							'type'  => $post->post_type,
							'link'  => get_edit_post_link( $post ),
						);
					},
					$posts
				);
			}
		}

		$this->render_settings_table(
			__( "Posts", 'greyd_hub' ),
			'posts',
			$post_data
		);

		/**
		 * Terms
		 */
		$term_data = array();
		$term_ids  = $this->get_excluded_terms();

		if ( ! empty( $term_ids ) ) {
			$term_data = array_filter(
				array_map(
					function( $term_id ) {
						$term = get_term( $term_id );
						if ( ! $term || is_wp_error($term) ) {
							return null;
						}

						return (object) array(
							'ID'    => $term->term_id,
							'title' => $term->name,
							'type'  => $term->taxonomy,
							'link'  => get_edit_term_link( $term ),
						);
					},
					$term_ids
				)
			);
		}

		$this->render_settings_table(
			__( 'Terms', 'greyd_hub' ),
			'terms',
			$term_data
		);
	}

	/**
	 * Render the settings table
	 *
	 * @param string   $title     Title of the table, eg. 'Posts'.
	 * @param string   $slug      Slug of the table, eg. 'posts'.
	 * @param object[] $data    Objects:
	 *      @property int ID
	 *      @property string title
	 *      @property string type
	 *      @property string link
	 */
	public function render_settings_table( $title, $slug, $data ) {
		?>
		<tr>
			<th>
				<?php echo sprintf( __( "Exclude %s from search", 'greyd_hub' ), $title ); ?>
			</th>
			<td>
				<?php if ( empty( $data ) ) { ?>

				<p>
					<?php echo sprintf( __( "So far no %s are excluded from search.", 'greyd_hub' ), $title ); ?>
				</p>
				<small class="color_light">
					<?php echo sprintf( __( "You can exclude %s from search by enabling the checkbox \"Exclude from search\".", 'greyd_hub' ), $title ); ?>
				</small>

				<?php } else { ?>

				<table cellspacing="0" class="wp-list-table widefat fixed striped table-view-list pages">
					<thead>
						<tr>
							<th style="" class="check-column" id="cb" scope="col"></th>
							<th style="" class="column-title manage-column" id="title" scope="col">
								<span><?php echo __( "Title", 'greyd_hub' ); ?></span>
							</th>
							<th style="" class="manage-column column-type" id="type" scope="col">
								<span><?php echo __( "Type", 'greyd_hub' ); ?></span>
							</th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php foreach ( $data as $element ) { ?>
						<tr valign="top" class="post-<?php echo $element->ID; ?> page type-page status-draft author-self" >
							<th class="check-column" scope="row">
								<input type="checkbox" value="<?php echo $element->ID; ?>" name="search_exclude_<?php echo $slug; ?>[]" checked="checked">
							</th>
							<td class="post-title page-title column-title">
								<strong>
									<a class="row-title"  title="<?php echo sprintf( __( "Edit %s", 'greyd_hub' ), $element->title ); ?>" href="<?php echo $element->link; ?>">
										<?php echo $element->title; ?>
									</a></strong>
								</td>
							<td class="author column-author">
								<?php echo $element->type; ?>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Filter the greyd advanced search settings before saving.
	 *
	 * @see filter 'greyd_advanced_search_settings_save'
	 *
	 * @param array $new_settings   Current site settings.
	 * @param array $defaults       Default values.
	 * @param array $data           Raw $_POST data.
	 *
	 * @return array $new_settings
	 */
	public function filter_settings_on_save( $new_settings, $defaults, $data ) {

		$post_ids = isset( $data['search_exclude_posts'] ) ? (array) $data['search_exclude_posts'] : array();
		$new_settings['advanced_search']['exclude_posts_from_search'] = implode( ',', $post_ids );

		$term_ids = isset( $data['search_exclude_terms'] ) ? (array) $data['search_exclude_terms'] : array();
		$new_settings['advanced_search']['exclude_terms_from_search'] = implode( ',', $term_ids );

		return $new_settings;
	}



	/**
	 * =================================================================
	 *                          Helper
	 * =================================================================
	 */

	/**
	 * Get all supported post types.
	 *
	 * @return string[] Array of post type names.
	 */
	public function get_supported_posttypes() {
		return get_post_types( array( 'exclude_from_search' => false ), 'names' );
	}

	/**
	 * Get all excluded post IDs.
	 *
	 * @return int[]
	 */
	public function get_excluded_posts() {

		$search_settings = Settings::get_setting( array( 'site', 'advanced_search' ) );
		$option          = isset( $search_settings['exclude_posts_from_search'] ) ? $search_settings['exclude_posts_from_search'] : array();
		if ( ! empty( $option ) && ! is_array( $option ) ) {
			$option = explode( ',', $option );
		}

		return is_array( $option ) ? $option : array();
	}

	/**
	 * Update the excluded post IDs.
	 *
	 * @param array $new_value
	 */
	public function save_excluded_posts( $new_value ) {

		$success = Settings::update_setting( 'site', array( 'site', 'advanced_search', 'exclude_posts_from_search' ), implode( ',', $new_value ) );

	}

	/**
	 * Whether a post is excluded from search.
	 *
	 * @param WP_Post|int $post
	 *
	 * @return bool
	 */
	public function is_post_excluded( $post ) {
		$post_id = is_object( $post ) ? $post->ID : intval( $post );

		return array_search( $post_id, $this->get_excluded_posts() ) !== false;
	}

	/**
	 * Get all excluded term IDs.
	 *
	 * @return int[]
	 */
	public function get_excluded_terms() {

		$search_settings = Settings::get_setting( array( 'site', 'advanced_search' ) );
		$option          = isset( $search_settings['exclude_terms_from_search'] ) ? $search_settings['exclude_terms_from_search'] : array();
		if ( ! empty( $option ) && ! is_array( $option ) ) {
			$option = explode( ',', $option );
		}

		return is_array( $option ) ? $option : array();
	}

	/**
	 * Update the excluded term IDs.
	 *
	 * @param array $new_value
	 */
	public function save_excluded_terms( $new_value ) {

		$success = Settings::update_setting( 'site', array( 'site', 'advanced_search', 'exclude_terms_from_search' ), implode( ',', $new_value ) );

	}

	/**
	 * Whether a term is excluded from search.
	 *
	 * @param WP_Term|int $term
	 *
	 * @return bool
	 */
	public function is_term_excluded( $term ) {
		$term_id = is_object( $term ) ? $term->term_id : intval( $term );

		return array_search( $term_id, $this->get_excluded_terms() ) !== false;
	}
}

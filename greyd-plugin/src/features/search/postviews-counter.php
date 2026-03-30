<?php
/**
 * Post view counter for 'most viewed' meta info
 *
 * @since 0.8.8
 */
namespace Greyd\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Postviews_Counter();
class Postviews_Counter {


	public function __construct() {
		add_action( 'wp_head', array( $this, 'track_postviews' ) );

		// To keep the count accurate, lets get rid of prefetching
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

		// add dynamic tag
		add_filter( 'greyd_default_dynamic_tags', array( $this, 'add_dynamic_tag' ) );

		// add meta box
		add_action( 'add_meta_boxes', array( $this, 'add_postviews_meta_box' ) );

		// save meta box
		add_action( 'save_post', array( $this, 'postviews_save_meta_box_data' ) );

		$settings = Settings::get_setting( array( 'site', 'advanced_search' ) );
		if ( isset( $settings['postviews_counter_editable'] ) && $settings['postviews_counter_editable'] === 'true' ) {
			// add column to the post list table that is sortable by post views
			add_filter( 'manage_edit-post_columns',  array( $this, 'add_new_columns' ) );
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'register_sortable_columns' ) );
			add_filter( 'request', array( $this, 'postviews_column_orderby' ) );
			add_action( 'manage_posts_custom_column' , array( $this, 'custom_columns' ) );
		}
	}

	public static function track_postviews() {

		$settings = Settings::get_setting( array( 'site', 'advanced_search' ) );

		// return if not enabled or not on single
		if ( ! is_single() || ! $settings || $settings['postviews_counter'] !== 'true' ) {
			return;
		}

		/**
		 * Filter to change if logged in users should be counted
		 * 
		 * Example use: add_filter( 'greyd_postviews_count_logged_in_users', '__return_true' );
		 *
		 * @param bool $count_logged_in_users Whether to count logged in users
		 */
		$count_logged_in_users = apply_filters( 'greyd_postviews_count_logged_in_users', false );

		if ( ! $count_logged_in_users && is_user_logged_in() ) {
			return;
		}

		self::set_postviews( get_the_ID() );
	}

	public static function set_postviews( $post_id ) {
		$count_key = 'postviews_count';
		$count     = get_post_meta( $post_id, $count_key, true );

		/**
		 * Filter to block all counts on any post
		 * 
		 * Example use: add_filter( 'greyd_postviews_block_count', '__return_true' );
		 *
		 * @filter greyd_postviews_block_count
		 *
		 * @param bool $block_count
		 */
		$block_count = apply_filters( 'greyd_postviews_block_count', false );

		if ( $block_count ) {
			return;
		}

		/**
		* Filter to block the count for a specific post
		 * 
		 * Example use: add_filter( 'greyd_postviews_block_count_post_12345', '__return_true' );
		 *
		 * @filter greyd_postviews_block_count_post_{$post_id}
		 *
		 * @param bool $block_count_post
		 * @param int $post_id
		 */
		$block_count_post = apply_filters( 'greyd_postviews_block_count_post_' . $post_id, false );

		if ( $block_count_post ) {
			return;
		}

		if ( empty( $count ) ) {
			$count = 1;
		} else {
			$count = intval( $count ) + 1;
		}

		update_post_meta( $post_id, $count_key, $count );
	}

	/**
	 * Add the dynamic tag '_post-views_'
	 *
	 * @filter greyd_default_dynamic_tags
	 */
	public function add_dynamic_tag( $tags ) {
		$tags[] = 'post-views';
		return $tags;
	}

	/**
	 * Add the post views meta box
	 */
	public function add_postviews_meta_box() {

		// check if option to manually edit post views is enabled and if user is administrator
		$settings = Settings::get_setting( array( 'site', 'advanced_search' ) );
		if ( $settings['postviews_counter_editable'] !== 'true' || ! current_user_can( 'administrator' ) ) {
			return;
		}

		add_meta_box(
			'postviews-meta-box-id',
			__( 'Post Views', 'greyd_hub' ),
			array( $this, 'postviews_meta_box_callback' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Render the post views meta box
	 */
	public function postviews_meta_box_callback($post) {
		wp_nonce_field( 'postviews_save_meta_box_data', 'postviews_meta_box_nonce' );
		$count = get_post_meta( $post->ID, 'postviews_count', true );
		?>
		<p><strong><?php _e( 'Change post views', 'greyd_hub' ); ?></strong></p>
		<p><input type="text" id="postviews_count" name="postviews_count" value="<?php echo esc_attr( $count ); ?>" /></p>
		<div class="components-notice is-warning">
			<div class="components-notice__content">
				<?php _e( 'When changing this value, the post views will be updated immediately.', 'greyd_hub' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the post views meta box data
	 */
	public function postviews_save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['postviews_meta_box_nonce'] ) )
			return;
		if ( ! wp_verify_nonce( $_POST['postviews_meta_box_nonce'], 'postviews_save_meta_box_data' ) )
			return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		if ( ! isset( $_POST['postviews_count'] ) )
			return;

		$count = sanitize_text_field( $_POST['postviews_count'] );

		update_post_meta( $post_id, 'postviews_count', $count );
	}

	/**
	* Add new columns to the post table
	*
	* @param Array $columns - Current columns on the list post
	*/
	public function add_new_columns($columns){
		$column_meta = array( 'postviews' => 'Post Views' );
		$columns = array_slice( $columns, 0, 6, true ) + $column_meta + array_slice( $columns, 6, NULL, true );
		return $columns;
	}

	// Register the columns as sortable
	public function register_sortable_columns( $columns ) {
		$columns['postviews'] = 'postviews';
		return $columns;
	}

	//Add filter to the request to make the hits sorting process numeric, not string
	public function postviews_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'postviews' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'postviews_count',
				'orderby' => 'meta_value_num'
			) );
		}
		return $vars;
	}

	/**
	* Display data in new columns
	*
	* @param  $column Current column
	*
	* @return Data for the column
	*/
	public function custom_columns($column) {

		global $post;

		switch ( $column ) {
			case 'postviews':
				$postviews = get_post_meta( $post->ID, 'postviews_count', true );
				echo (int)$postviews;
			break;
		}
	}
}

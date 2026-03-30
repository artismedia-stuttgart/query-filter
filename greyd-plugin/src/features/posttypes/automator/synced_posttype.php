<?php
/**
 * This is a reusable class to sync a posttype with specififc settings.
 * 
 * Example:
 * *    $posttype = new \Greyd\Synced_Posttype( __DIR__."/synced_posttype_example.json" );
 */
namespace Greyd;

use Greyd\Posttypes\Posttype_Helper;

if (!defined( 'ABSPATH' )) exit;

class Synced_Posttype {

	/**
	 * Construct the Synced Posttype class object.
	 * 
	 * @param array $setup Array or filepath of json containing an array.
	 * @see synced_posttype_example.json for details.
	 */
	public function __construct( $setup ) {

		if ( is_string($setup) ) {
			$setup = self::import_json( $setup );
		}

		if ( !$setup || empty($setup) || !is_array($setup) || !isset($setup['post_name']) ) return;

		// force posttype & post status
		$setup['post_type'] = 'tp_posttypes';
		$setup['post_status'] = 'publish';

		// setup class vars
		$this->setup = $setup;
		$this->setup_incomplete = false;

		// add posttype
		add_action( 'admin_init', array($this, 'setup_and_sync_posttype'), 99 );

		// mark posttype
		add_filter( 'display_post_states', array($this, 'mark_posttype'), 10, 2 );
	}

	/**
	 * Setup configuration of the posttype.
	 * 
	 * @var array
	 */
	public $setup = false;

	/**
	 * Whether the setup is incomplete. This prevents errors.
	 * 
	 * @var bool
	 */
	public $setup_incomplete = true;

	/**
	 * Insert posttype and sync it's settings according to the setup.
	 */
	public function setup_and_sync_posttype() {

		if ( $this->setup_incomplete || !class_exists("Greyd\Posttypes\Admin") ) return;

		/**
		 * @since 1.7.6 supports for global taxonomies
		 */
		$is_taxonomy = (
			isset($this->setup['meta_input'])
			&& isset($this->setup['meta_input']['posttype_settings'])
			&& isset($this->setup['meta_input']['posttype_settings']['is_taxonomy'])
			&& $this->setup['meta_input']['posttype_settings']['is_taxonomy']
		);

		$post_type = array();

		// get posttype by name
		if ( $is_taxonomy ) {
			$global_taxonomies = Posttype_Helper::get_global_taxonomies();
			foreach ( $global_taxonomies as $taxonomy ) {
				if ( $taxonomy['slug'] === $this->setup['post_name'] ) {
					$post_type = $taxonomy;
					break;
				}
			}
		} else {
			$post_type = Posttype_Helper::get_dynamic_posttype_by_slug( $this->setup['post_name'] );
		}

		if ( empty( $post_type ) ) {
			// create posttype
			$post_id = wp_insert_post( $this->setup );
		}
		else if ( isset($post_type['id']) && isset($this->setup['meta_input']) && isset($this->setup['meta_input']['posttype_settings']) ) {
			// update posttype settings
			$this->update_posttype_settings( $post_type['id'], $this->setup['meta_input']['posttype_settings'] );
		}

		// register meta for this posttype
		if ( ! registered_meta_key_exists( $this->setup['post_name'], 'is_imported_post' ) ) {
			register_meta(
				$this->setup['post_name'],
				'is_imported_post',
				array(
					'type' => 'boolean',
					'single' => true,
					'show_in_rest' => false,
				)
			);
		}
	}


	/**
	 * Update posttype settings
	 * 
	 * @param int $post_id      WP_Post ID.
	 * @param array $settings   Meta settings.
	 */
	public function update_posttype_settings( $post_id, $settings ) {

		$current = Posttype_Helper::get_posttype_settings( $post_id );

		// collect all names to see if we have to update the posttype...
		$names = [];

		// we combine all imported fields with all custom fields
		$fields = [];
		if ( isset($settings['fields']) ) {
			foreach ( (array) $settings['fields'] as $field ) {
				// check if the user is allowed to edit this field
				if ( isset($field['edit']) && $field['edit'] ) continue;
				// add field to array
				if ( isset($field['name']) && !isset($fields[$field['name']]) ) {
					$fields[$field['name']] = $field;
					$names[$field['name']] = $field['name'];
				}
			}
		}
		if ( isset($current['fields']) ) {
			foreach ( (array) $current['fields'] as $field ) {
				if ( isset($field['edit']) ) unset( $field['edit'] );
				if ( isset($field['fill']) ) unset( $field['fill'] );
				if ( isset($field['name']) && !isset($fields[$field['name']]) ) $fields[$field['name']] = $field;

				if ( isset($names[$field['name']]) ) unset($names[$field['name']]);
			}
		}

		// check if we need to update...
		if (
			!empty($names) || 
			$current['title'] !== $settings['title'] || 
			$current['slug'] !== $settings['slug']
		) {

			$settings["fields"] = array_values($fields);
			$settings = array_merge( $current, $settings );

			Posttype_Helper::update_posttype_settings($post_id, $settings);
		}
	}

	/**
	 * Sets the state in the backend overview
	 */
	public function mark_posttype( $states, $post ) {

		// mark synced posttypes
		if ( get_post_type($post->ID) === $this->setup['post_type'] && $post->post_name === $this->setup["post_name"] ) {
			$mark = isset($this->setup['mark']) ? $this->setup['mark'] : __("Synced", "greyd_hub");
			if ( !in_array($mark, $states) ) $states[] = $mark;
		}

		// mark imported posts
		if ( get_post_type($post->ID) === $this->setup['post_name'] ) {
			
			// mark imported when meta 'is_imported_post' is set to true
			$is_imported = get_post_meta( $post->ID, 'is_imported_post', true );
			if ( $is_imported ) {
				$mark = isset($this->setup['mark_imported']) ? $this->setup['mark_imported'] : __("imported", "greyd_hub");
				if ( !in_array($mark, $states) ) $states[] = $mark;
			}
		}

		return $states;
	}

	/**
	 * Import json from file.
	 * 
	 * @param string $file  Absolut path of file.
	 * @return array $json  Contents of the file on success, false or null on failure.
	 */
	public static function import_json( $file="" ) {
		if ( empty($file) ) return false;

		$json = Helper::get_file_contents($file);
		if ( $json === false ) {
			// error getting contents from file
		} else {
			$json = json_decode( $json, true );
			if ( $json === null ) {
				// error decoding json
			}
		}
		return $json;
	}
}
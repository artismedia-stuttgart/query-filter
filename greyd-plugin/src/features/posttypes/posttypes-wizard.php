<?php
/**
 * Dynamic Posttypes Feature
 * posttype Wizard
 */

namespace Greyd\Posttypes;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Wizard($config);
class Wizard {

	/**
	 * Holds global config args.
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// set config
		$this->config = (object)$config;

		// add wizard
		add_action( 'admin_enqueue_scripts', array($this, 'add_script'), 300 );
		add_action( 'admin_footer', array( $this, 'add_wizard' ), 999 );

		// ajax callback
		add_action( 'greyd_ajax_mode_create_posttype',  array( $this, 'create_posttype' ) );

	}


	/*
	====================================================================================
		Ajax
	====================================================================================
	*/

	/**
	 * Ajax function to create posttype post.
	 * @see action 'greyd_ajax_mode_'
	 * 
	 * @param array $post_data   $_POST data.
	 */
	public function create_posttype($post_data) {

		if ($post_data['slug'] == "") $post_data['slug'] = sanitize_title($post_data['name']);

		echo "\r\n\r\n";
		debug($post_data);
		echo "\r\n\r\n";

		$args = array(
			'slug' => Posttype_Helper::get_nice_slug( $post_data['slug'] ),
			'title' => $post_data['name'],
			'type' => Admin::POST_TYPE,
			'state' => 'publish'
		);
		if ($post_data['mode'] == 'new-taxonomy') $args['is_taxonomy'] = true;
		$result = $this->create_posttype_post($args);

		if (!$result) 
			wp_die("\r\nerror::unable to create post type: ".$post_data['slug']);
		else if (strpos($result, 'exists::') === 0) 
			wp_die("\r\ninfo::Posttype <strong>".$post_data['slug']."</strong> (id ".str_replace('exists::', '', $result).") already exists!");
		else 
			wp_die("\r\nsuccess::Posttype <strong>".$post_data['slug']."</strong> (id ".$result.") created!");

	}

	/**
	 * Create posttype post.
	 * 
	 * @return $post_id | false
	 */
	public function create_posttype_post($args) {
		$post_id = null;
		$slug    = isset($args['slug'])     ? $args['slug']     : '';
		$title   = isset($args['title'])    ? $args['title']    : '';
		$type    = isset($args['type'])     ? $args['type']     : Admin::POST_TYPE;
		$state   = isset($args['state'])    ? $args['state']    : 'publish';
		$meta    = isset($args['meta'])     ? $args['meta']     : array();
		$is_taxonomy = isset($args['is_taxonomy']) ? $args['is_taxonomy'] : false;

		// check if posttype already exists
		$query = array(
			'name'        => $slug,
			'post_type'   => $type,
			'post_status' => $state,
			'numberposts' => 1
		);
		$exists = get_posts($query);
		
		// posttype already exists
		if ($exists) {
			$post_id = 'exists::'.$exists[0]->ID;
			echo sprintf("\r\n"."* posttype \"%s\" already exists", $slug);
		}
		// posttype doesn't exist
		else {
			echo sprintf("\r\n"."* posttype \"%s\" doesn't exist.", $slug);
			$post = [
				"post_name"     => $slug,
				"post_title"    => $title,
				"post_type"     => $type,
				"post_status"   => $state,
			];
			
			$post_id = wp_insert_post( $post );
			// $post_id = 123;
			// check if there was an error in the post insertion
			if (is_wp_error($post_id)) {
				echo sprintf("\r\n"."* error creating posttype \"%s\":", $slug);
				echo "\r\n".$post_id->get_error_message();
				return false;
			}
			else {
				if ($is_taxonomy) {
					Posttypes::update_dynamic_posttype($post_id, array( 'is_taxonomy' => true ));
				}
				echo sprintf("\r\n"."* posttype \"%s\" successfully created", $slug);
			}
		}
		
		return $post_id;
	}


	/*
	====================================================================================
		Post Type Wizard
	====================================================================================
	*/

	/**
	 * Check if Post Type edit screen.
	 */
	public function is_posttypes_screen() {

		$screen = get_current_screen();
		return $screen->post_type == Admin::POST_TYPE && $screen->base == 'edit';

	}

	/**
	 * add Wizard script vars.
	 */
	public function add_script() {

		if (!$this->is_posttypes_screen()) return;

		// get dynamic post types
		$plist = array();
		$posts = Helper::get_all_posts(Admin::POST_TYPE);
		foreach($posts as $post) {
			$plist[] = array( 'id' => $post->id, 'slug' => $post->slug, 'title' => $post->title );
		}

		// get editable post types
		$editable_posttypes = array();
		$posts = Posttype_Helper::get_editable_posttypes();
		foreach($posts as $name => $title) {
			$editable_posttypes[] = array( 'slug' => $name, 'title' => $title );
		}

		// get all pages
		$pages = array();
		$posts = get_pages();
		foreach($posts as $post) {
			$pages[] = array( 'id' => $post->ID, 'slug' => $post->post_name, 'title' => $post->post_title );
		}

		// init script
		wp_add_inline_script( $this->config->plugin_name.'_posttypes_js', '
			var post_types = '.json_encode($plist).';
			var editable_post_types = '.json_encode($editable_posttypes).';
			var pages = '.json_encode($pages).';
			jQuery(function() {
				// greyd.backend.addPageTitleAction( "'.sprintf( __("Edit %s", 'greyd_hub'), __("Core Post Type", 'greyd_hub') ).'", { css: "edit-core-posttype" } );
				// greyd.backend.addPageTitleAction( "'.sprintf( __("Create new %s", 'greyd_hub'), __("Taxonomy", 'greyd_hub') ).'", { css: "create-new-txonomy" } );
				greyd.posttypes.initWizard();
			});
		' );

	}

	/**
	 * add Post Type Wizard.
	 */
	public function add_wizard() {

		if (!$this->is_posttypes_screen()) return;

		// render the wizard
		$this->render_wizard();

	}

	/**
	 * render Post Type Wizard.
	 */
	public function render_wizard() {

		$asset_url = plugin_dir_url( $this->config->plugin_file )."inc/deprecated/install-wizard/assets/img";

		$existing_posttypes = Posttype_Helper::get_editable_posttypes();

		// $options = "";
		$options = "<option value=''>"._x("Please select", 'small', 'greyd_hub')."</option>";
		foreach ($existing_posttypes as $posttype_slug => $posttype_name) {
			$options .= "<option value='".$posttype_slug."'>".$posttype_name."</option>";
		}

		echo '
		<div id="greyd-wizard" class="wizard posttype_wizard">
			<div class="wizard_box">
				<div class="wizard_head">
					<img class="wizard_icon" src="'.$asset_url.'/logo_light.svg">
					<span class="close_wizard dashicons dashicons-no-alt"></span>
				</div>
				<div class="wizard_content">

				
					<div data-slide="0-error">
						<h2>'.__('Ooooops!', 'greyd_hub').'</h2>
						<div class="greyd_info_box orange" style="width: 100%; box-sizing: border-box;">
							<span class="dashicons dashicons-warning"></span>
							<div>
								<p>'.__("There was a problem:", 'greyd_hub').'</p><br>
								<p class="error_msg"></p>
							</div>
						</div>
					</div>

					<div data-slide="1">

						<!--<h2>'.__("What do you want to do?", 'greyd_hub').'</h2>-->
						<br><br>
					
						<div class="content_options">
							<span class="content_option posttype" data-trigger="new-posttype" data-id="name">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 11.2h-3.2V8h-1.6v3.2H8v1.6h3.2V16h1.6v-3.2H16z"></path></svg>
								<p>'.__("New Post Type", 'greyd_hub').'</p>
							</span>
							<span class="content_option posttype" data-trigger="edit-posttype" data-id="existing_posttype">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m19 7-3-3-8.5 8.5-1 4 4-1L19 7Zm-7 11.5H5V20h7v-1.5Z"></path></svg>
								<p>'.__('Core Post Type', 'greyd_hub').'</p>
							</span>
							<span class="content_option posttype" data-trigger="new-taxonomy" data-id="tax-name">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.1 11.2l-6.7-6.7c-.1-.1-.3-.2-.5-.2H5c-.4-.1-.8.3-.8.7v7.8c0 .2.1.4.2.5l6.7 6.7c.2.2.5.4.7.5s.6.2.9.2c.3 0 .6-.1.9-.2.3-.1.5-.3.8-.5l5.6-5.6c.4-.4.7-1 .7-1.6.1-.6-.2-1.2-.6-1.6zM19 13.4L13.4 19c-.1.1-.2.1-.3.2-.2.1-.4.1-.6 0-.1 0-.2-.1-.3-.2l-6.5-6.5V5.8h6.8l6.5 6.5c.2.2.2.4.2.6 0 .1 0 .3-.2.5zM9 8c-.6 0-1 .4-1 1s.4 1 1 1 1-.4 1-1-.4-1-1-1z"></path></svg>
								<p>'.__("Taxonomy", 'greyd_hub').'</p>
							</span>
						</div>

						<div class="" data-show="new-posttype">
							<h3>'.__("Create a new post type.", 'greyd_hub').'</h3>
							<div class="row flex">
								<div class="element grow">
									<div class="label">'.__('Name', 'greyd_hub').'</div>
									<input id="name" name="name" type="text" value="" placeholder="'.__("Enter here", 'greyd_hub').'" autocomplete="off" >
								</div>
							</div>
						</div>

						<div class="" data-show="edit-posttype">
							<h3>'.__("Which post type do you want to edit?", 'greyd_hub').'</h3>
							<div class="row flex">
								<div class="element grow">
									<div class="label">'.__("Select post type", 'greyd_hub').'</div>
									<select id="existing_posttype" name="existing_posttype" autocomplete="off">'.$options.'</select>
								</div>
							</div>
						</div>

						<div class="" data-show="new-taxonomy">
							<h3>'.__("Create a new taxonomy.", 'greyd_hub').'</h3>
							<div class="row flex">
								<div class="element grow">
									<div class="label">'.__("Title", 'greyd_hub').'</div>
									<input id="tax-name" name="tax-name" type="text" value="" placeholder="'.__("Enter here", 'greyd_hub').'" autocomplete="off" >
								</div>
							</div>
						</div>

						<div class="row flex">
							<div class="element grow" data-warning="name_double" style="display:none">
								<div class="greyd_info_box warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<div style="margin-bottom:10px"><strong>'.__("Attention:", 'greyd_hub').'</strong></div>
										<div style="margin-bottom:10px">'.sprintf(__("A post type with the unique name \"%s\" is already created as Greyd Dynamic Post Type. To recreate the post type, you must first put the existing one in the trash. But you can also easily edit it.", 'greyd_hub'), '<span class="double_name">Post</span>').'</div>
										<div style="margin-bottom:10px"><a class="double_edit" style="cursor:pointer;text-decoration:underline">'.sprintf(__("Edit %s", 'greyd_hub'), __("Post Type", 'greyd_hub') ).'</a></div>
									</div>
								</div>
							</div>
							<div class="element grow" data-warning="existing_editable_post" style="display:none">
								<div class="greyd_info_box warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<div style="margin-bottom:10px"><strong>'.__("Attention:", 'greyd_hub').'</strong></div>
										<div style="margin-bottom:10px">'.sprintf(__("A post type with the unique name \"%s\" already exists. Do you want to edit this post type instead?", 'greyd_hub'), '<span class="double_name">Post</span>').'</div>
										<div style="margin-bottom:10px"><a class="edit_existing_editable" style="cursor:pointer;text-decoration:underline">'.sprintf(__("Edit %s", 'greyd_hub'), __('Core Post Type', 'greyd_hub') ).'</a></div>
									</div>
								</div>
							</div>
							<div class="element grow" data-warning="clashing_page" style="display:none">
								<div class="greyd_info_box warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<div style="margin-bottom:10px"><strong>'.__("Attention:", 'greyd_hub').'</strong></div>
										<div style="margin-bottom:10px">'.sprintf(__("There is a page with the unique name \"%s\". If this page has child pages and the post type you are creating is an archive, these pages will be overwritten and no longer displayed in the frontend", 'greyd_hub'), '<span class="double_name">Post</span>').'</div>
									</div>
								</div>
							</div>
							<div class="element grow" data-warning="existing_posttype" style="display:none">
								<div class="greyd_info_box warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<div style="margin-bottom:10px"><strong>'.__("Attention:", 'greyd_hub').'</strong></div>
										<div style="margin-bottom:10px">'.sprintf(__("The selected post type has already been created. To recreate the post type, you must first put the existing one in the trash.", 'greyd_hub'), '<span class="double_name">Post</span>').'</div>
										<div style="margin-bottom:10px"><a class="double_edit" style="cursor:pointer;text-decoration:underline">'.sprintf(__("Edit %s", 'greyd_hub'), __("Post Type", 'greyd_hub') ).'</a></div>
									</div>
								</div>
							</div>
						</div>

					</div>

					<div data-slide="11">
						<h2>'.__("Congratulations!", 'greyd_hub').'</h2>
						<div class="install_txt">'.__("You can now edit the post type.", 'greyd_hub').'</div>
						<div class="success_mark"><div class="checkmark"></div></div>
					</div>
				</div>


				<div class="wizard_foot">
					<div data-slide="0-error">
						<div class="flex">
							<span class="back button button-secondary">'.__("Back", 'greyd_hub').'</span>
							<span class="close_wizard reload button button-primary">'.__("Close", 'greyd_hub').'</span>
						</div>
					</div>
					<div data-slide="1">
						<div class="flex">
							<span class="close_wizard button button-secondary">'.__("Cancel", 'greyd_hub').'</span>
							<span class="create button button-primary" data-show="new-posttype">'.sprintf(__("Create %s", 'greyd_hub'), __("Post Type", 'greyd_hub') ).'</span>
							<span class="edit button button-primary" data-show="edit-posttype">'.sprintf(__("Edit %s", 'greyd_hub'), __("Post Type", 'greyd_hub') ).'</span>
							<span class="create-tax button button-primary" data-show="new-taxonomy">'.sprintf(__("Create %s", 'greyd_hub'), __("Taxonomy", 'greyd_hub') ).'</span>
						</div>
					</div>
					<div data-slide="11">
						<div class="flex">
							<span class="close_wizard reload button button-secondary">'.__("Close", 'greyd_hub').'</span>
							<a class="finish_wizard button button-primary" href="">'.__("Open", 'greyd_hub').'</a>
						</div>
					</div>
				</div>
			</div>
		</div>';

	}

}
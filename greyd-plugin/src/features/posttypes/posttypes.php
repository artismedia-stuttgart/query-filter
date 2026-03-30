<?php
/**
 * Dynamic Posttypes Feature
 * Main posttype
 */

namespace Greyd\Posttypes;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Posttypes($config);
class Posttypes {

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

		/**
		 * Add custom post type for registering dynamic post types.
		 * We do this as early as possible 
		 */
		add_action( 'init', array($this, 'add_posttype'), 1 );

		// edit screen for posttypes
		add_action( 'add_meta_boxes', array($this, 'add_posttype_boxes') );
		add_action( 'save_post', array($this, 'save_posttype'), 20, 2 );

		// when posttype is deleted
		add_action( 'before_delete_post', array($this, 'on_delete_posttype') );

		// remove quick edit
		add_filter( 'post_row_actions', array($this, 'remove_quick_edit'), 10, 2 );

		// change backend ui for edited posttypes
		add_filter( 'admin_body_class', array($this, 'edit_body_class'), 99 );
		add_action( 'admin_head-edit.php', array($this, 'edit_posttype_admin_list') );
		add_filter( 'display_post_states', array($this, 'edit_posttype_admin_state'), 10, 2 );

		// add notice to 404 page
		add_action( 'popup', array($this, 'add_flush_rewrite_notice') );

	}


	/*
	====================================================================================
		Register post type
	====================================================================================
	*/

	/**
	 * add custom post type
	 */
	public function add_posttype() {

		$name = __("Post Types", 'greyd_hub');
		$singular = __("Post Type", 'greyd_hub');

		// define new post type
		$post_type_labels = array(
			'name'               => $name,
			'singular_name'      => $singular,
			'menu_name'          => $name,
			'name_admin_bar'     => $singular,
			'add_new'            => '+ ' . sprintf(__( 'Post Type Wizard', 'greyd_hub' ), $singular),
			'add_new_item'       => sprintf(__( "Create %s", 'greyd_hub' ), $singular),
			'new_item'           => sprintf(__( "New %s", 'greyd_hub' ), $singular),
			'edit_item'          => sprintf(__( "Edit %s", 'greyd_hub' ), $singular),
			'view_item'          => sprintf(__( "Show %s", 'greyd_hub'), $singular),
			// 'all_items'          => sprintf(__( "All %s", 'greyd_hub'), $name),
			'all_items'          => $name,
			'search_items'       => sprintf(__( "Search %s", 'greyd_hub'), $singular),
			'parent_item_colon'  => sprintf(__( "Parent %s", 'greyd_hub'), $singular),
			'not_found'          => sprintf(__( "No %s found", 'greyd_hub'), $name),
			'not_found_in_trash' => sprintf(__( "No %s found in the trash", 'greyd_hub'), $singular),
		);
		$post_type_arguments = array(
			'labels'             => $post_type_labels,
			'description'        => __( "Description", 'greyd_hub' ),
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => Admin::$is_standalone ? true : 'greyd_dashboard',
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'supports'           => array( 'title', 'revisions' ),
			'admin_column_sortable' => true,
			'admin_column_filter'   => true
		);

		// register post_type and taxonony
		register_post_type(Admin::POST_TYPE, $post_type_arguments);
	}


	/*
	====================================================================================
		Edit post type
	====================================================================================
	*/

	/**
	 * Edit screen
	 */
	public function add_posttype_boxes() {

		// remove name/slug edit box
		remove_meta_box('slugdiv', Admin::POST_TYPE, 'normal');

		// add settings box
		add_meta_box(
			'greyd_posttype_settings', // ID
			__("Settings", 'greyd_hub'), // Title
			array($this, 'render_settings_box'), // Callback
			Admin::POST_TYPE, // CPT name
			'normal', // advanced = at bottom of page
			'high' // Priority
		);

		// add fields box
		if ( isset( $_GET['post'] ) ) {
			$options = Posttype_Helper::get_dynamic_posttype_by_id($_GET['post']);
			if ( !isset($options['is_taxonomy']) || !$options['is_taxonomy'] ) {
				add_meta_box(
					'greyd_posttype_fields', // ID
					__("Fields", 'greyd_hub'), // Title
					array($this, 'render_fields_box'), // Callback
					Admin::POST_TYPE, // CPT name
					'normal', // advanced = at bottom of page
					'low' // Priority
				);
			}
		}
	}

	/**
	 * Metabox: Einstellungen
	 */
	public function render_settings_box($post) {

		$rows       = [];
		$post_id    = $post->ID;
		$name       = Admin::OPTION;
		$options    = Posttype_Helper::get_dynamic_posttype_by_id($post_id);
		// debug($options);

		$is_edited_posttype = Posttype_Helper::is_edited_posttype($post);
		$is_taxonomy = isset($options['is_taxonomy']) && $options['is_taxonomy'];

		// edited posttype
		if ( $is_edited_posttype ) {
			$slug       = $post->post_title;
			$posttype   = Posttype_Helper::get_editable_posttypes()[$slug];
			echo "<div style='padding: 6px 20px; max-width: 60em;'>".
				"<p>".sprintf(__("You are editing the post type \"%s\" with the unique name \"%s\".", "greyd_hub"), "<strong>".$posttype."</strong>", "<strong>".$slug."</strong>")."</p>".
				"<p><i>".__("This post type is either a standard WordPress post type or it has been added by another plugin. Some basic settings cannot be changed, otherwise this could lead to serious errors.", "greyd_hub")."</i></p>".
			"</div>";
		}

		// $postmeta = get_post_meta($post->ID);
		// $postmeta = get_post_meta($post->ID, Admin::OPTION);
		// debug($postmeta);

		// -------------------- Row: Namings
		if ( !$is_edited_posttype ) {
			$rows[] = [
				"id" => "settings_wording",
				"title" => __("Title", 'greyd_hub'),
				"desc" => __("For the correct designation of commands and states", 'greyd_hub'),
				"content" =>
					'<div class="flex input_section">
							<div>
								<label for="'.$name.'[singular]">'.__('Singular', 'greyd_hub').'</label><br>
								<input type="text" name="'.$name.'[singular]" value="'.( isset($options["singular"]) ? $options["singular"] : "" ).'">
							</div>
							<div>
								<label for="'.$name.'[plural]">'.__('Plural', 'greyd_hub').'</label><br>
								<input type="text" name="'.$name.'[plural]" value="'.( isset($options["plural"]) ? $options["plural"] : "" ).'">
							</div>
					</div>'
			];
		}

		// -------------------- Row: Menu Options
		if ( !$is_edited_posttype && !$is_taxonomy ) {
			$positions = [
				"_group_1"  => __("Top", 'greyd_hub'),
				//1 => _x("ganz oben", 'small', 'greyd_hub'),
				//3 => _x("unter Dashboard", 'small', 'greyd_hub'),
				6           => _x("After posts", 'small', 'greyd_hub'),
				11          => _x("After media", 'small', 'greyd_hub'),
				20          => _x("After pages", 'small', 'greyd_hub'),
				22          => _x("After templates", 'small', 'greyd_hub'),
				"_group_2"  => __("Middle", 'greyd_hub'),
				50          => _x("50", 'small', 'greyd_hub'),
				51          => _x("51", 'small', 'greyd_hub'),
				52          => _x("52", 'small', 'greyd_hub'),
				53          => _x("53", 'small', 'greyd_hub'),
				54          => _x("54", 'small', 'greyd_hub')." "._x("(Default)", 'small', 'greyd_hub'),
				55          => _x("55", 'small', 'greyd_hub'),
				56          => _x("56", 'small', 'greyd_hub'),
				57          => _x("57", 'small', 'greyd_hub'),
				58          => _x("58", 'small', 'greyd_hub'),
				//59 => _x("über Design", 'small', 'greyd_hub'),
				"_group_3"  => __("Bottom", 'greyd_hub'),
				61          => _x("After design", 'small', 'greyd_hub'),
				66          => _x("After plugins", 'small', 'greyd_hub'),
				71          => _x("After users", 'small', 'greyd_hub'),
				76          => _x("After tools", 'small', 'greyd_hub'),
				81          => _x("After settings", 'small', 'greyd_hub')
			];
			$dashicons = "<option value=''>"._x("No icon", 'small', 'greyd_hub')."</option>";
				foreach (Posttype_Helper::get_dashicons() as $icon_slug) {
					$sel = ($options["icon"] == $icon_slug) ? "selected" : "";
					$dashicons .= "<option value='".$icon_slug."'".$sel.">".$icon_slug."</option>";
				}

			$rows[] = [
				"id" => "settings_menu",
				"title" => __("Menu appearance", 'greyd_hub'),
				"desc" => __("Display in the admin menu bar (left)", 'greyd_hub'),
				"content" =>
					'<div class="flex input_section">
							<div>
								<label for="'.$name.'[icon]">'.__('Icon', 'greyd_hub').'</label><br>
								<span class="vc-icons-selector fip-vc-theme-grey">
									<select  class="iconselect widefat" name="'.$name.'[icon]" value="'.( isset($value) ? $value : "" ).'" >
										'.$dashicons.'
									</select>
								</span>
							</div>
							<div>
								<label for="'.$name.'[position]">'.__('Position', 'greyd_hub').'</label><br>
								<select name="'.$name.'[position]">'.Posttype_Helper::render_list_as_options($positions, $options["position"], false).'</select>
							</div>
					</div>'
			];
		}

		// -------------------- Row: Supports
		if ( !$is_taxonomy ) {
			$pre = $name."[supports][";
			$after = "]";
			$checkboxes_supports = $is_edited_posttype ? array() : array(
				//"title"       => __("Title", 'greyd_hub'),
				"editor"      => __("Content editor", 'greyd_hub'),
				"thumbnail"   => __("Featured image", 'greyd_hub'),
				"excerpt"     => __("Excerpt", 'greyd_hub'),
				"author"      => __("Author", 'greyd_hub'),
				"revisions"   => array(
					"label" => __("Revisions", 'greyd_hub'),
					"description" => __("Only works if 'Content editor' is enabled", 'greyd_hub')
				)
			);

			$checkboxes_taxonomies = array(
				array(
					'name'      => $name."[supports][custom_taxonomies]",
					'value'     => "custom_taxonomies",
					'label'     => __("Custom taxonomies", 'greyd_hub'),
					'checked'   => isset($options["supports"]["custom_taxonomies"]) && $options["supports"]["custom_taxonomies"] === "custom_taxonomies" ? true : false,
					'onclick'   => "greyd.backend.toggleElemByClass('custom_taxonomies')"
				)
			);
			if ( ! $is_edited_posttype || $post->post_title !== 'post' ) {
				array_unshift( $checkboxes_taxonomies, array(
					'name'      => $name."[categories]",
					'value'     => "categories",
					'label'     => __("Categories", 'greyd_hub'),
					'checked'   => isset($options["categories"]) && $options["categories"] === "categories" ? true : false
				), array(
					'name'      => $name."[tags]",
					'value'     => "tags",
					'label'     => __("Tags", 'greyd_hub'),
					'checked'   => isset($options["tags"]) && $options["tags"] === "tags" ? true : false
				) );
			}
			
			// Get the option 'greyd_features' and check if 'comments' is in it, if so, comments are deactivated globally
			$greyd_features = get_option('greyd_features');
			// also check if comments are enabled by filter 'greyd_comments_enabled'
			// moved the comments checkbox to the other column for better UI
			if ( ! isset( $greyd_features['comments'] ) || has_filter( "greyd_comments_enabled", "__return_true" ) ) {
				array_unshift( $checkboxes_taxonomies, array(
					'name'      => $name."[supports][comments]",
					'value'     => "comments",
					'label'     => __("Comments", 'greyd_hub'),
					'checked'   => isset($options["supports"]["comments"]) && $options["supports"]["comments"] === "comments" ? true : false
				) );
			}

			$checkboxes_taxonomies = implode("", array_map( function( $args ) {
				return Posttype_Helper::render_checkbox($args);
			}, $checkboxes_taxonomies ) );

			$rows[] = [
				"id" => "settings_features",
				"title" => $is_edited_posttype ? __("Taxonomies", 'greyd_hub') : __("Standard features", 'greyd_hub'),
				"desc" => $is_edited_posttype ? __("Which taxonomies should the post type support?", 'greyd_hub') : __("Which elements should the post type support?", 'greyd_hub'),
				"content" =>
					'<div class="flex input_section">
						<div>
							'.Posttype_Helper::render_list_as_checkboxes($checkboxes_supports, ( isset($options["supports"]) ? $options["supports"] : array() ), false, $pre, $after).'
						</div>
						<div>
							'.$checkboxes_taxonomies.'
						</div>
					</div>'
			];

			// -------------------- Row: Custom Taxonomies
			$option_name = $name."[custom_taxonomies][%s][]";
			$taxonomies = isset($options["custom_taxonomies"]) ? (array) $options["custom_taxonomies"] : array();
			$taxonomies[] = [];
			$content = "";
			$content .= "<table class='greyd_table small taxonomy_table'>
				<thead>
					<tr>
						<th>".__("Name", 'greyd_hub')."</th>
						<th>".__("Singular", 'greyd_hub')."</th>
						<th>".__("Plural", 'greyd_hub')."</th>
						<th>".__("Hierarchical", 'greyd_hub')."</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr class='empty'>
						<td colspan='5'>".__("No custom taxonomies found", "greyd_hub")."</td>
					</tr>";
			$i = 1;
			foreach ( (array) $taxonomies as $tax ) {

				if ( !is_array($tax) ) {
					$tax = (array) $tax;
				}

				$hierarchical = isset($tax['hierarchical']) && $tax['hierarchical'] === "hierarchical";
				$content .= "<tr class='".( $i === count($taxonomies) ? "hidden" : "" )."'>
						<td>
							<input type='text' name='".sprintf($option_name, 'slug')."' value='".( isset($tax['slug']) ? $tax['slug'] : "" )."' placeholder='".__("e.g. country", 'greyd_hub')."' />
						</td>
						<td>
							<input type='text' name='".sprintf($option_name, 'singular')."' value='".( isset($tax['singular']) ? $tax['singular'] : "" )."' placeholder='".__("e.g. country", 'greyd_hub')."' />
						</td>
						<td>
							<input type='text' name='".sprintf($option_name, 'plural')."' value='".( isset($tax['plural']) ? $tax['plural'] : "" )."' placeholder='".__("e.g. countries", 'greyd_hub')."' />
						</td>
						<td>
							<label for='".sprintf($option_name, 'hierarchical')."' style='white-space:nowrap;'>
								"./* We need a hidden field, because checkboxes are only passed to $_POST if checked */"
								<input type='hidden' name='".sprintf($option_name, 'hierarchical')."' value='".( $hierarchical ? "" : "false" )."'/>
								<input type='checkbox' name='".sprintf($option_name, 'hierarchical')."' value='hierarchical' ".( $hierarchical ? "checked='checked'": "" )."/>
								".__("Yes", 'greyd_hub')."</label>
						</td>
						<td>
							<span class='button small button-danger' onclick='greyd.posttypes.deleteTaxonomy(this);' title='".__("Delete taxonomy", 'greyd_hub')."'><span class='dashicons dashicons-trash'></span></span>
						</td>
					</tr>";
				$i++;
			}
			$content .= "</tbody>
				<tfoot>
					<tr>
						<td colspan='5'>
							<span class='button' onclick='greyd.posttypes.addTaxonomy(this);'><span class='dashicons dashicons-plus'></span>&nbsp;&nbsp;".__("Add taxonomy", 'greyd_hub')."</span>
						</td>
					</tr>
				</tfoot>
			</table>";
			$rows[] = [
				"id" => "settings_taxonomies",
				"classes" => "toggle_custom_taxonomies ".(!isset($options["supports"]["custom_taxonomies"]) || empty($options["supports"]["custom_taxonomies"]) ? "hidden" : ""),
				"title" => __("Custom taxonomies", 'greyd_hub'),
				"desc" => __("Create individual categories or tags", 'greyd_hub'),
				"content" => $content
			];
			
			// -------------------- Row: dynamic/global Taxonomies
			$content = "<table class='greyd_table small taxonomy_table' style='width:100%'>
				<thead>
					<tr>
						<th>".__("Singular", 'greyd_hub')."</th>
						<th>".__("Plural", 'greyd_hub')."</th>
						<th>".__("Hierarchical", 'greyd_hub')."</th>
						<th>".__("Public", 'greyd_hub')."</th>
						<th>".__("Post Types", 'greyd_hub')."</th>
						<th></th>
					</tr>
				</thead>
				<tbody>";
			$global_taxonomies = Posttype_Helper::get_global_taxonomies();
			// $global_taxonomies = array();
			if (count($global_taxonomies) == 0) {
				$content .= "<tr>
						<td colspan='6'>".__("No global taxonomies available", "greyd_hub")."</td>
					</tr>";
			}
			else foreach ( $global_taxonomies as $dynamic_tax ) {
				$checked = isset($dynamic_tax["posttypes"][$post->post_name]) && $dynamic_tax["posttypes"][$post->post_name];
				$public = isset($dynamic_tax["arguments"]["public"]) && $dynamic_tax["arguments"]["public"];
				$hierarchical = isset($dynamic_tax["arguments"]["hierarchical"]) && $dynamic_tax["arguments"]["hierarchical"];
				$posttypes = isset($dynamic_tax["posttypes"]) ? implode('<br>', $dynamic_tax["posttypes"]) : "--";
				$content .= "<tr>
						<td>
							".Posttype_Helper::render_checkbox( array(
								'name'      => "global_taxonomies[".$dynamic_tax['id']."]",
								'value'     => $dynamic_tax['slug'],
								'label'     => !empty($dynamic_tax['singular']) ? $dynamic_tax['singular'] : $dynamic_tax['title'],
								'checked'   => $checked
							) )."
						</td>
						<td>".( !empty($dynamic_tax['plural']) ? $dynamic_tax['plural'] : $dynamic_tax['title'] )."</td>
						<td>".( $hierarchical ? __("Yes", 'greyd_hub') : __("No", 'greyd_hub') )."</td>
						<td>".( $public ? __("Yes", 'greyd_hub') : __("No", 'greyd_hub') )."</td>
						<td><small>".$posttypes."</small></td>
						<td style='width:0'>
							<a class='button small button-ghost' href='".admin_url("post.php?post=".$dynamic_tax["id"]."&action=edit")."' target='_blank' title='".__("Edit taxonomy", 'greyd_hub')."'><span class='dashicons dashicons-edit'></span></a>
						</td>
					</tr>";
			}
			$content .= "</tbody>
				<tfoot>
					<tr>
						<td colspan='6'>
							<a class='button' href='".admin_url("edit.php?post_type=tp_posttypes&wizzard=tax")."' target='_blank' ><span class='dashicons dashicons-plus'></span>&nbsp;&nbsp;".__("Create global taxonomy", 'greyd_hub')."</a>
						</td>
					</tr>
				</tfoot>
			</table>";
			$rows[] = [
				"id" => "settings_dynanic_taxonomies",
				"title" => __("Site-wide taxonomies", 'greyd_hub'),
				"desc" => __("Link global taxonomies to this post type", 'greyd_hub'),
				"content" => $content
			];
		}

		// -------------------- Row: Arguments
		if ( !$is_edited_posttype && !$is_taxonomy ) {
			$pre = $name."[arguments][";
			$after = "]";

			/**
			 * Posttype arhchitecture settings.
			 * 
			 * @filter greyd_posttypes_settings_architecture
			 * @since @future
			 */
			$checkboxes = (array) apply_filters( 'greyd_posttypes_settings_architecture_checkboxes', array(
				//"title"       => __("Title", 'greyd_hub'),
				"search"    => __("Show in frontend search", 'greyd_hub'),
				"archive"   => __("This post type has an archive", 'greyd_hub'),
				"menus"     => __("The individual posts can be added to menus", 'greyd_hub'),
				"hierarchical" => __("This post type is hierarchical", 'greyd_hub')
			), $post );

			$rows[] = [
				"id" => "settings_architecture",
				"title" => __("Post architecture", 'greyd_hub'),
				"desc" => __("How should the posts act in the WordPress architecture?", 'greyd_hub'),
				"content" =>
					'<div>'.Posttype_Helper::render_list_as_checkboxes(
						$checkboxes,
						( isset($options["arguments"]) ? $options["arguments"] : array() ),
						false,
						$pre,
						$after
					).'</div>'
			];
		}

		if ( $is_taxonomy ) {
			// -------------------- Row: Taxonomy Posttypes
			$dynamic_posttypes  = Posttype_Helper::get_dynamic_posttypes();
			$editable_posttypes = Posttype_Helper::get_editable_posttypes();
			if ( !count($editable_posttypes) && !count($dynamic_posttypes) ) {
				$content = "<i>".__("No post types available", "greyd_hub")."</i>";
			}
			else {
				$pre = $name."[posttypes][";
				$after = "]";
				$checkboxes = $editable_posttypes;
				foreach ( $dynamic_posttypes as $id => $posttype ) {
					$checkboxes[$posttype["slug"]] = $posttype["title"];
				}
				$content = Posttype_Helper::render_list_as_checkboxes($checkboxes, ( isset($options["posttypes"]) ? $options["posttypes"] : array() ), false, $pre, $after);
			}
			$rows[] = [
				"id" => "settings_posttypes",
				"title" => __("Post Types", 'greyd_hub'),
				"desc" => __("Which post types should this taxonomy be assigned to?", 'greyd_hub'),
				"content" =>
					'<div>
						<input type="hidden" name="posttype_settings[is_taxonomy]" value="true">
						'.$content.'
					</div>'
			];

			// -------------------- Row: Taxonomy Arguments
			$pre = $name."[arguments][";
			$after = "]";
			$checkboxes = [
				"public"       => __("Show in frontend and backend", 'greyd_hub'),
				"hierarchical" => __("This taxonomy is hierarchical", 'greyd_hub'),
			];
			$rows[] = [
				"id" => "settings_architecture",
				"title" => __("Architecture", 'greyd_hub'),
				"desc" => __("How should the taxonomy behave in the WordPress architecture?", 'greyd_hub'),
				"content" =>
					'<div>
						'.Posttype_Helper::render_list_as_checkboxes($checkboxes, ( isset($options["arguments"]) ? $options["arguments"] : array() ), false, $pre, $after).'
					</div>'
			];

		}

		// -------------------- Render
		echo '<table class="greyd_table vertical big posttype_table">';
		foreach($rows as $row) {
			Posttype_Helper::render_table_row($row);
		}
		echo '</table>';

		if ( $is_taxonomy ) Posttype_Helper::render_submit();

	}

	/**
	 * Metabox: Felder
	 */
	public function render_fields_box($post) {

		// Basic Vars
		$rows       = [];
		$name       = Admin::OPTION."[fields]";
		$types = [
			"_group_input"      => __("Input field", 'greyd_hub'),
				"text_short"    => _x("Text single line, short", 'small', 'greyd_hub'),
				"text"          => _x("Text single line", 'small', 'greyd_hub'),
				"text_long"     => _x("Text single line, long", 'small', 'greyd_hub'),
				"textarea"      => _x("Text multi line", 'small', 'greyd_hub'),
				"number"        => _x("Number", 'small', 'greyd_hub'),
				"email"         => _x("Email", 'small', 'greyd_hub'),
				"date"          => _x("Date", 'small', 'greyd_hub'),
				"time"          => _x("Time", 'small', 'greyd_hub'),
				"datetime-local"=> _x("Date and time", 'small', 'greyd_hub'),
			"_group_wp"         => __("WordPress", 'greyd_hub'),
				"text_html"     => _x("HTML Editor", 'small', 'greyd_hub'),
				"file"          => _x("File / media", 'small', 'greyd_hub'),
				"url"           => _x("Link", 'small', 'greyd_hub'),
			"_group_choose"     => __("Selection", 'greyd_hub'),
				"dropdown"      => _x("Dropdown", 'small', 'greyd_hub'),
				"radio"         => _x("Radio buttons", 'small', 'greyd_hub'),
			"_group_layout"     => __("Backend layout", 'greyd_hub'),
				"headline"      => _x("Headline", 'small', 'greyd_hub'),
				"descr"         => _x("Description", 'small', 'greyd_hub'),
				"hr"            => _x("Separator", 'small', 'greyd_hub'),
				"space"         => _x("Empty space", 'small', 'greyd_hub'),
		];
		$h_units = [
			"h1" => _x("H1", 'small', 'greyd_hub'),
			"h2" => _x("H2", 'small', 'greyd_hub'),
			"h3" => _x("H3", 'small', 'greyd_hub'),
			"h4" => _x("H4", 'small', 'greyd_hub'),
			"h5" => _x("H5", 'small', 'greyd_hub'),
			"h6" => _x("H6", 'small', 'greyd_hub')
		];

		// Get all fields saved
		$post_id    = $post->ID;
		$options    = Posttype_Helper::get_dynamic_posttype_by_id($post_id);
		//debug($options);

		$fields     = isset($options["fields"]) && is_array($options["fields"]) ? array_values($options["fields"]) : [];
		$hidden_field = [[
			"name"          => "",
			"label"         => __("Empty", 'greyd_hub'),
			"type"          => "text",
			"required"      => false,
			"h_unit"        => "h3",
			"hidden"        => true
		]];
		$fields = array_merge($fields, $hidden_field);



		/*
			Render
		*/
		echo '<div class="greyd_table posttype_table fields_table">';

		echo '<div class="thead">';
			echo '<ul class="tr">
					<li class="th"></li>
					<li class="th">
						'.__("Field", 'greyd_hub').'
					</li>
					<li class="th">
						'.__("Type", 'greyd_hub').'
					</li>
					<li class="th">
						'.__("Actions", 'greyd_hub').'
					</li>
				</ul>';
		echo '</div>';

		echo '<div class="tbody" id="greyd_sortable" data-empty="'.__("No fields added yet", 'greyd_hub').'">';

		//get the blacklist for comparing blacklisted field names for adding them as data-attribute
		$blacklisted_field_names  = $this->get_blacklist();
		$blacklisted_field_names  = implode(",", $blacklisted_field_names);

		$i = 0;
		foreach ($fields as $field) {
			$field = (array) $field;

			$hidden = isset($field["hidden"]) && $field["hidden"] ? 'hidden' : '';
			$change_name_alert      = !isset($field["hidden"]) || !$field["hidden"] ? '<div class="change_name_alert">'.__("Attention! If you change the name of this field, the authors' previous entries will be lost!", 'greyd_hub').'</div>' : '';
			$blacklisted_name_alert = '<div class="blacklisted_name_alert">'.__("Attention! You have chosen a name used by WordPress. Conflicts can arise.", 'greyd_hub').'</div>'; //Text ändern

			$locked = isset($field["edit"]) && $field["edit"] === false ? true : false;

			// outer row container
			echo '<div class="row_container '.$hidden.'">';


			$required = isset($field["required"]) ? 'required' : '';
			$label = $field["type"] === 'hr' || $field["type"] === 'space' ? $types[$field["type"]] : $field["label"];
			echo '<ul class="tr" '.( $locked ? 'data-locked="locked"' : '' ).'>
					<li class="td sortable_handle">
						<span class="dashicons dashicons-menu"></span>
					</li>
					<li class="td">
						<a href="javascript:void(0)" class="edit_field field_label '.$required.'"><span class="dyn_field_label">'.$label.'</span><span class="required-star" data-hide="type=headline,hr,descr">&nbsp;*</span></a>
					</li>
					<li class="td">
						<span class="dyn_field_type">'.$types[$field["type"]].'</span>
					</li>
					<li class="td">'.( !$locked ? '
						<span class="button button-secondary edit_field"><span class="dashicons dashicons-edit resize"></span>&nbsp;'.__("Edit", 'greyd_hub').'</span>
						<span class="button button-ghost duplicate_field" title="'.__("Duplicate", 'greyd_hub').'"><span class="dashicons dashicons-admin-page resize"></span></span>
						<span class="button button-danger delete_field" title="'.__("Delete", 'greyd_hub').'"><span class="dashicons dashicons-trash resize"></span></span>
						' : '<span class="dashicons dashicons-lock"></span>' ).'
					</li>
				</ul>';

			// sub container
			$inner_rows = [];
			$inner_rows[] = [
				"title" => __("Title", 'greyd_hub'),
				"content" =>
					'<div class="flex input_section">
						<div data-hide="type=hr,space">
							<label class="required">'.__('Label', 'greyd_hub').'<span class="required-star">&nbsp;*</span></label><br>
							<input type="text" name="'.$name.'['.$i.'][label]" value="'.$field["label"].'">
							'.( $locked ? '<input type="hidden" name="'.$name.'['.$i.'][edit]" value="false">' : '' ).'
						</div>
						<div data-hide="type=headline,hr,descr,space">
							<label class="required">'.__("Unique name", 'greyd_hub').'<span class="required-star">&nbsp;*</span></label><br>
							<input type="text" name="'.$name.'['.$i.'][name]" value="'.$field["name"].'" data-val="'.$field["name"].'" data-blacklisted-fields="'.$blacklisted_field_names.'">
							'.$change_name_alert.'
							'.$blacklisted_name_alert.'
						</div>
					</div>'
			];
			$inner_rows[] = [
				"title" => __("Field type", 'greyd_hub'),
				"content" =>
					'<div class="flex input_section">
						<div>
							<label>'.__("Type", 'greyd_hub').'</label><br>
							<select name="'.$name.'['.$i.'][type]" class="trigger_cond">'
								.Posttype_Helper::render_list_as_options($types, $field["type"], false).
							'</select>
						</div>
						<div data-hide="type=headline,hr,descr,space">
							<label>'.__("Required field", 'greyd_hub').'</label><br>
							<label><input type="checkbox" name="'.$name.'['.$i.'][required]" '.($checked = (isset($field["required"]) && $field["required"] === "required") ? "checked" : "").' value="required">'.__("Yes", 'greyd_hub').'</label>
						</div>
						<div data-show="type=headline">
							<label>'.__("Unit", 'greyd_hub').'</label><br>
							<select name="'.$name.'['.$i.'][h_unit]">'
								.Posttype_Helper::render_list_as_options($h_units, ( isset($field["h_unit"]) ? $field["h_unit"] : null ), false).
							'</select>
						</div>
					</div>'
			];
			$inner_rows[] = [
				"title" => __("Backend view", 'greyd_hub'),
				"desc" => __("Additional backend information for editors", 'greyd_hub'),
				"content" =>
					'<div data-show="type=dropdown,radio">
						<label>'.__("Enter values", 'greyd_hub').'</label><br>
						<textarea name="'.$name.'['.$i.'][options]" rows="5">'.( isset($field["options"]) ? $field["options"] : null ).'</textarea>
						<i>'.__("Separate options with a comma. Value and display can be separated by \"=\". (Example: \"male = Mr, female = Mrs\")", 'greyd_hub').'</i>
					</div>
					<br data-show="type=dropdown,radio">
					<div class="flex input_section">
						<div data-hide="type=headline,hr,descr,radio,space,text_html">
							<label>'.__("Inner text / placeholder", 'greyd_hub').'</label><br>
							<input type="text" name="'.$name.'['.$i.'][placeholder]" value="'.( isset($field["placeholder"]) ? $field["placeholder"] : null ).'">
						</div>
						<div data-hide="type=headline,hr,descr,space,file">
							<label>'.__("Default value", 'greyd_hub').'</label><br>
							<input type="text" name="'.$name.'['.$i.'][default]" value="'.( isset($field["default"]) ? $field["default"] : null ).'">
						</div>
						<div data-show="type=file" style="flex:1">
							<label>'.__("Default value", 'greyd_hub').'</label><br>
							'.Posttype_Helper::render_filepicker( isset($field["default"]) ? $field["default"] : null, "{$name}[{$i}][default_file]", "", false ).'
						</div>
					</div>
					<br data-show="type=number">
					<div class="flex input_section">
						<div data-show="type=number">
							<label>'.__("Minimum value", 'greyd_hub').'</label><br>
							<input type="number" name="'.$name.'['.$i.'][min]" value="'.( isset($field["min"]) ? $field["min"] : null ).'" placeholder="'.__("e.g. \"0\"", 'greyd_hub').'">
						</div>
						<div data-show="type=number">
							<label>'.__("Maximum value", 'greyd_hub').'</label><br>
							<input type="number" name="'.$name.'['.$i.'][max]" value="'.( isset($field["max"]) ? $field["max"] : null ).'" placeholder="'.__("e.g. \"100\"", 'greyd_hub').'">
						</div>
						<div data-show="type=number">
							<label>'.__("Steps", 'greyd_hub').'</label><br>
							<input type="number" name="'.$name.'['.$i.'][steps]" value="'.( isset($field["steps"]) ? $field["steps"] : null ).'" placeholder="'.__("e.g. \"0.1\"", 'greyd_hub').'" min="0" step="0.01">
						</div>
					</div>
					<br data-hide="type=headline,hr,descr,space">
					<div data-hide="type=space">
						<label>'.__("Additional description", 'greyd_hub').'</label><br>
						<textarea name="'.$name.'['.$i.'][description]" rows="4">'.( isset($field["description"]) ? $field["description"] : null ).'</textarea>
					</div>
					<div data-show="type=space">
						<label>'.__("Height (in px or em)", 'greyd_hub').'</label><br>
						<input type="text" name="'.$name.'['.$i.'][height]" value="'.( isset($field["height"]) ? $field["height"] : null ).'" placeholder="'.__("Default: 30px", 'greyd_hub').'">
					</div>'
			];

			echo ' <div class="sub" data-num="'.$i.'">
						<table class="greyd_table posttype_table inner_table vertical">';
							foreach($inner_rows as $row) {
								Posttype_Helper::render_table_row($row);
							}
						echo '</table>
				</div>';
			$i++;

			echo '</div>'; // end of row container
		}

		echo '</div>'; // end of tbody

		echo '<div class="tfoot">';

		echo '<ul class="tr">
				<li class="td" colspan="4">
					<span class="button button-primary add_field"><span class="dashicons dashicons-plus"></span>&nbsp;&nbsp;'.__("Add field", 'greyd_hub').'</span>
				</li>
			</ul>';

		echo '</div>'; // end of table

		Posttype_Helper::render_submit();
	}

	/**
	 * Save posttype
	 */
	public function save_posttype( $post_id, $post ) {

		if (
			$post->post_type != Admin::POST_TYPE
			|| !is_admin()
			|| empty($_POST)
			|| isset($_POST['mode']) // do not call if posttype is saved via ajax
		) return;

		set_transient( 'flush_rewrite', true );

		// return if not called via submitted wordpress backend form
		// if ( !isset($_POST['meta_action']) || $_POST['meta_action'] !== 'wp_backend' ) return;

		// check if title (which generates the slug) has been changed
		$post_type  = Posttype_Helper::get_dynamic_posttype_by_id($post_id);
		if ( $post_type && isset($post_type["slug"]) && !empty($post_type["slug"]) ) {

			$old_slug   = $post_type["slug"];
			$new_slug   = Posttype_Helper::get_clean_slug(get_the_title($post_id));
			/**
			 * @since 1.1 Slug is now generated via get_nice_slug()
			 */
			$new_slug_2 = Posttype_Helper::get_nice_slug(get_the_title($post_id), true);

			if ( $old_slug !== $new_slug && $old_slug !== $new_slug_2 ) {

				/**
				 * Check if this posttype already exists
				 */
				if ( post_type_exists( $new_slug ) ) {
					wp_update_post( array(
						'ID' => $post_id,
						'post_title' => $post_type['title']
					) );
				}
				else {
					// save old title in post metadata
					update_post_meta($post_id, "old_posttype_slug", $old_slug);

					/**
					 * Save old taxonomies as post meta.
					 * 
					 * This is necessary, because after a posttype is moved, the
					 * old taxonomies technically do not exist anymore (even though
					 * they are still saved in the database). Therefore the old 
					 * terms cannot be found. So we need to get all the terms data
					 * beforehand.
					 */
					$taxonomies = $this->get_taxonomies_of_posttype($old_slug);
					if ( $taxonomies !== false ) {
						update_post_meta($post_id, "old_posttype_taxonomies", $taxonomies);

						// delete terms of old taxonomy
						//$this->delete_terms_of_posttype($old_slug, $taxonomies);
					}
				}
			}
		}

		// update posttype meta
		$values = isset( $_POST[Admin::OPTION] ) ? (array) $_POST[Admin::OPTION] : array();
		$result = self::update_dynamic_posttype( $post_id, $values );

		// update global taxonomies
		if ( !isset($_POST[Admin::OPTION]['is_taxonomy']) || !$_POST[Admin::OPTION]['is_taxonomy'] ) {
			$global_taxonomies = Posttype_Helper::get_global_taxonomies();
			if (count($global_taxonomies) > 0) {
				$taxes = isset( $_POST["global_taxonomies"] ) ? (array)$_POST["global_taxonomies"] : array();
				// debug($post);
				// debug($taxes);
				foreach ( $global_taxonomies as $dynamic_tax ) {
					// debug($dynamic_tax);
					$posttypes = array();
					if (isset($dynamic_tax["posttypes"])) $posttypes = $dynamic_tax["posttypes"];
					$update = false;
					if (isset($taxes[$dynamic_tax["id"]])) {
						$posttypes[$post->post_name] = $post->post_name;
						$update = true;
					}
					else if (isset($posttypes[$post->post_name])) {
						unset($posttypes[$post->post_name]);
						$update = true;
					}
					// debug($posttypes);
					if ($update) {
						$meta = $dynamic_tax;
						unset($meta["id"]);
						$meta["posttypes"] = $posttypes;
						if (empty($meta["posttypes"])) unset($meta["posttypes"]);
						update_post_meta($dynamic_tax["id"], Admin::OPTION, $meta);
					}
				}
				// exit();
			}
		}

	}

	/**
	 * Delete posttype
	 */
	public function on_delete_posttype($post_id) {
		if (get_post_type($post_id) != Admin::POST_TYPE) return;
		if (!is_admin()) return;

		$posttype   = Posttype_Helper::get_dynamic_posttype_by_id($post_id);
		$slug       = isset($posttype["slug"]) ? $posttype["slug"] : Posttype_Helper::get_posttype_slug_from_title($posttype["post_title"]);
		if (post_type_exists($slug) || empty($slug)) return; // posttype exists already

		$posts = get_posts([
			'post_type' => $slug,
			'numberposts' => -1,
			'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
		]);
		if ($posts) {
			foreach ( (array) $posts as $item ) {
				wp_delete_post( $item->ID,  true );
			}
			set_transient( 'flush_rewrite', true );
		}
	}

	/**
	 * Remove quick edit for posttypes
	 *
	 * @param  array    $actions Array of current actions
	 * @param  \WP_Post $post Post object
	 * 
	 * @return array
	 */
	public function remove_quick_edit( $actions, $post ) {

		if ( $post->post_type === Admin::POST_TYPE ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	/**
	 * Add class to body for posttypes
	 *
	 * @param  string $classes CSS classes string
	 * 
	 * @return string
	 */
	public function edit_body_class( $classes ) {
		global $pagenow;

		if (
			'post.php' !== $pagenow && 'post-new.php' !== $pagenow
			|| empty( $_GET['post'] )
		) {
			return $classes;
		}

		if ( Posttype_Helper::is_edited_posttype(get_post()) ) {
			$classes .= ' edited_posttype';
		}

		return $classes;
	}

	/**
	 * Modify the title in the WP_List view
	 */
	public function edit_posttype_admin_list() {
		$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : "";
		if ( $post_type == Admin::POST_TYPE ) {
			add_filter( 'the_title', array($this, 'edit_posttype_admin_list_title'), 100, 2 );
		}
	}

	/**
	 * Replace slug with post type label in the WP_List view
	 */
	public function edit_posttype_admin_list_title( $title, $id ) {
		if ( Posttype_Helper::is_edited_posttype($title) ) {
			$title = Posttype_Helper::get_editable_posttypes()[$title];
		}
		return $title;
	}

	/**
	 * Add state "Core Post Type" or "Taxonomy" after title
	 */
	public function edit_posttype_admin_state( $states, $post ) {
		if ( $post && isset($post->ID) && Admin::POST_TYPE == get_post_type($post->ID)) {
			if ( Posttype_Helper::is_edited_posttype($post->post_title) ) {
				$states['edited_posttype'] = __("Core Post Type", "greyd_hub");
			}
			else {
				$meta = (array)get_post_meta( $post->ID, Admin::OPTION, true );
				if ($meta && isset($meta['is_taxonomy']) && $meta['is_taxonomy']) {
					$states['is_taxonomy'] = __("Taxonomy", "greyd_hub");
				}
			}
		}
		return $states;
	}

	/**
	 * Add a notice for admins in the frontend 404 page.
	 * 
	 * Sometimes flush_rewrite() is not performed, even if
	 * the transient is set. This way we notify the admin on
	 * the frontend 404 page - the point where he realises
	 * that something is not working correctly.
	 */
	public function add_flush_rewrite_notice() {
		if ( is_404() && is_user_logged_in() && current_user_can('manage_options') ) {
			echo '<div class="front_admin_notice">
				'.sprintf(
					__( 'Certain content, such as new post types, is not displayed although it should be? Try resaving the %sPermalinks%s or check the settings %sof your post types%s.', 'greyd_hub' ),
					'<a href="'.admin_url('options-permalink.php').'">',
					'</a>',
					'<a href="'.Admin::$page['url'].'">',
					'</a>'
				).'
				<br><br>
				<small>'.__("This information is only visible to admins.", 'greyd_hub').' <a onclick="$(this).parent().parent().fadeOut();">'.__("Hide", 'greyd_hub').'</a></small>
			</div>';
			$style = '
				.front_admin_notice {
					z-index: 99999;
					position: fixed;
					width: 400px;
					max-width: 80%;
					bottom: 10px;
					left: 10px;
					padding: 10px 12px;
					border-radius: 4px;
					font: normal 400 13px/1.5 system-ui, mono;
					color: #ccc;
					background: #23282d;
					-webkit-box-shadow: 0 4px 6px rgba(50,50,93,.18), 0 1px 3px rgba(0,0,0,.08);
					box-shadow: 0 4px 6px rgba(50,50,93,.18), 0 1px 3px rgba(0,0,0,.08);
				}
				.front_admin_notice a {
					color: #fff;
					font: inherit;
					font-weight: 600;
					cursor: pointer;
				}
			';
			Helper::add_custom_style($style);
		}
	}


	/*
	====================================================================================
		Handle taxonomies
	====================================================================================
	*/

	/**
	 * Get all taxonomies of a posttype as formatted array
	 * 
	 * @param string $slug  Name of the post type.
	 *  
	 * @return array        All taxonomies. Example:
	 *  
	 *  {{tax_name}} => array(
	 *      {{term_id}} => array(
	 *          name        => Name of the term,
	 *          description => Description (if any),
	 *          parent      => Slug of the parent term (0 if no parent),
	 *          slug        => Slug of the term
	 *          posts       => Array of post-IDs assigned with the term.
	 *      )
	 *      ...more terms
	 *  )
	 *  ...more taxonomies
	 */
	public function get_taxonomies_of_posttype( $slug ) {

		$return = [];
		$taxs   = get_object_taxonomies( $slug );
		if ( empty($taxs) || !is_array($taxs) || count($taxs) === 0 ) return false;

		foreach ( $taxs as $tax_name ) {
			$_terms = [];
			$terms = get_terms( $tax_name );
			foreach( $terms as $term ) {
				$_term = [ 'posts' => [] ];
				$id = $term->term_id;
				$_term['name']          = $term->name;
				$_term['description']   = $term->description;
				$_term['parent']        = $term->parent === 0 ? 0 : get_term_by('id', $term->parent, $tax_name)->slug;
				$_term['slug']          = $term->slug;

				// get all posts of term
				wp_reset_query();
				$args = array(
					'post_type' => $slug,
					'tax_query' => array(
						array(
							'taxonomy' => $tax_name,
							'field' => 'slug',
							'terms' => $term->slug,
						),
					),
				 );
				 $loop = new \WP_Query($args);
				 if ( $loop->have_posts() ) {
					while($loop->have_posts()) : $loop->the_post();
						$_term['posts'][] = get_the_ID();
					endwhile;
				 }

				$_terms[$id] = $_term;
			}
			$return[$tax_name] = $_terms;
		}
		return count($return) > 0 ? $return : false;
	}

	/**
	 *  Delete all terms of a posttype
	 *
	 *  @returns true or false
	 */
	public function delete_terms_of_posttype($slug='', $taxonomies=[]) {
		if ( empty($slug) && empty($taxonomies) ) return false;

		if ( !is_array($taxonomies) || count($taxonomies) === 0 ) {
			if ( !$taxonomies = $this->get_taxonomies_of_posttype($slug) ) return false;
		}
		$return = true;

		foreach($taxonomies as $tax_name => $terms) {
			foreach($terms as $term_id => $term) {
				$deleted = wp_delete_term( $term_id, $tax_name );
				if ( is_wp_error($deleted) ) $return = false;
			}
		}
		return $return;
	}


	/*
	====================================================================================
		Helper functions
	====================================================================================
	*/

	/**
	 * Update dynamic posttype when saving.
	 */
	public static function update_dynamic_posttype( $post_id, $inputs ) {

		if ( !$post_id ) return false;

		// default empty settings
		$final_settings = array(
			"singular"          => "",
			"plural"            => "",
			"icon"              => "",
			"position"          => "",
			"custom_taxonomies" => array(),
			"fields"            => array(),
			"supports"          => array(),
			"arguments"         => array(),
		);

		// get blacklist for field names and set counter to 1 for postfix ("_1, _2, ...")
		$blacklist = self::get_blacklist();
		$i = 1;

		// update values
		foreach ((array) $inputs as $name => $value) {

			// loop through array
			if ( is_array($value) ) {
				$cache = array();

				// handle custom taxonomies array
				if ( $name === 'custom_taxonomies' ) {
					$value['hierarchical'] = self::modify_hierarchical_saved_array($value['hierarchical']); // because unchecked checkboxes are not posted
					foreach ((array) $value['slug'] as $key => $slug) {
						if ( empty($slug) ) continue;
						$cache[] = [
							'slug'          => Posttype_Helper::get_clean_slug($slug),
							'singular'      => isset($value['singular'][$key])      ? sanitize_text_field($value['singular'][$key])     : "",
							'plural'        => isset($value['plural'][$key])        ? sanitize_text_field($value['plural'][$key])       : "",
							'hierarchical'  => isset($value['hierarchical'][$key])  ? sanitize_text_field($value['hierarchical'][$key]) : ""
						];
					}
				}
				// handle other array values, such as 'fields', 'supports' ...
				else {
					foreach ( $value as $key => $val ) {

						// sanitize text field
						if ( !is_array($val) ) {
							$cache[$key] = sanitize_text_field($val);
						}

						// ...or loop through array (fields)
						else {
							$_cache = array();

							foreach ( (array) $val as $k => $field ) {
								$field = sanitize_text_field($field);
								if ( $k === "name" ) {
									$field      = preg_replace('/\s/', '-', $field);
									$postfix    = intval(substr($field, strpos($field, "_") + 1));
									if ( isset($postfix) && $postfix > 0 ) $i++;
									// check if slug is in blacklist and if so add a postfix: "_1, _2, ..."
									if ( in_array($field, $blacklist) ) {    
										$field = $field.'_'.$i;
										$i++;
									}
								}
								// save to parent array
								$_cache[$k] = $field;
							}

							if ( $_cache["type"] === 'file' ) {
								$_cache["default"] = $_cache["default_file"];
							}
							unset( $_cache["default_file"] );

							// only save field if name isset or we don't need a name
							if (
								!empty($_cache["name"]) ||
								$_cache["type"] === 'hr' ||
								$_cache["type"] === 'space' ||
								$_cache["type"] === 'headline' ||
								$_cache["type"] === 'descr'
							) {
								$cache[$key] = $_cache;
							}
						}
					}
				}

				// save to parent array
				$final_settings[$name] = $cache;
			}
			// ...or save plain text
			else {
				$final_settings[$name] = sanitize_text_field($value);
			}
		}

		// save title & slug in metadata
		$final_settings["title"]        = get_the_title($post_id);
		$final_settings["slug"]         = Posttype_Helper::get_posttype_slug_from_title($final_settings["title"]);
		$final_settings["full_slug"]    = Posttype_Helper::get_posttype_slug_from_title($final_settings["title"], false);

		// save as meta option
		return update_post_meta($post_id, Admin::OPTION, $final_settings);
	}

	/**
	 * Modify the array for the control $option[custom_taxonomies][hierarchical]
	 * 
	 * this is needed, because only checked HTML-checkboxes are passed to the
	 * global $_POST array. We use a hidden field (value="false") right in front
	 * of the actual checkbox to always log a value in the $_POST array. Then,
	 * whenever there is an actual value passed to the array - which means the
	 * checkbox is checked - we override the last value.
	 */
	public static function modify_hierarchical_saved_array($array=array()) {
		$return = [];
		foreach ( (array) $array as $key => $value ) {

			// 'false' (untouched) or '' (unchecked) values represent a checkbox
			if ( $value === '' || $value === 'false' ) $return[] = $value;

			// we override the last array value with the actual value if passed
			else if ( $value === 'hierarchical' ) {
				if ( count($return) === 0 ) $return[] = $value;
				else $return[ count($return) - 1 ] = $value;
			}
		}
		return $return;
	}

	/**
	 * Get blacklisted names from single and archive dynamic tags.
	 */
	public static function get_blacklist() {
		$blacklisted_names_single     = array_keys(\Greyd\Dynamic\Dynamic_Helper::get_dynamic_tags("single"));
		$blacklisted_names_archive    = array_keys(\Greyd\Dynamic\Dynamic_Helper::get_dynamic_tags("archive"));
		$blacklisted_names            = array_unique(array_merge($blacklisted_names_single, $blacklisted_names_archive));
		return $blacklisted_names; 
	}


}
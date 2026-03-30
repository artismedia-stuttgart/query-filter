<?php
/**
 * Dynamic Posttypes Feature
 * Posttypes based on the posts of the main posttype.
 */

namespace Greyd\Posttypes;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Dynamic_Posttypes($config);
class Dynamic_Posttypes {

	/**
	 * Holds global config args.
	 */
	private $config;

	/**
	 * Holds all error messages.
	 * 
	 *  @var array
	 */
	public static $errors = [];
	
	/**
	 * Whether all dynamic posttypes are registered.
	 * 
	 * @since 1.7.4
	 * We used to check this with wp_cache_get( 'dynamic_posttypes_registered', 'greyd' )
	 * but this caused issues with some performance plugins where the variable was
	 * cached for too long. This lead to the dynamic posttypes not being registered
	 * on reload of any admin page. We now use a static variable instead.
	 * 
	 * @var bool
	 */
	private static $dynamic_posttypes_registered = false;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// set config
		$this->config = (object)$config;

		/**
		 * Add dynamic post types based on the posts of the main posttype.
		 * 
		 * Best practice is to keep the priority of this function ~later than
		 * the default (= 10). This way registered posttypes from other plugins
		 * can be edited and fields can be added.
		 * A good example is the WooCommerce product. Always test changes with
		 * those posttypes.
		 * 
		 * @since 1.1.2
		 */
		add_action( 'init', array($this, 'add_dynamic_posttypes'), 11 );
		add_action( 'init', array($this, 'rewrite_rules'), 12 );

		// define edit screen for dynamic posts
		add_action( 'add_meta_boxes', array($this, 'add_dynamic_box') );
		add_action( 'save_post', array($this, 'save_dynamic_post'), 20, 2 );

		// display errors
		add_action( 'admin_notices', array($this, 'display_errors') );

		// add vc roles
		add_filter( 'greyd_add_vc_posttypes', array($this, 'add_vc_roles') );

		// capabilities
		add_action( 'admin_enqueue_scripts', array($this, 'add_capabilities') );
	}


	/*
	====================================================================================
		Register dynamic post types
	====================================================================================
	*/

	/**
	 * Register all post types.
	 * 
	 * @param bool $use_cache Whether to use the cache or not.
	 */
	public static function add_dynamic_posttypes( $use_cache=true ) {

		/**
		 * @since 1.7.4
		 * See variable declaration for more info.
		 */
		if ( $use_cache && self::$dynamic_posttypes_registered ) {
			return;
		}

		$posttypes = Posttype_Helper::get_dynamic_posttypes();

		if ( !$posttypes || !is_array($posttypes) ) {
			$posttypes = [];
		}

		foreach ( $posttypes as $id => $posttype ) {

			$title = $posttype["title"];
			$slug  = $posttype["slug"];

			/**
			 * @since 1.1 Handle post type slug character limit of 20.
			 */
			$full_slug = isset($posttype["full_slug"]) && $slug !== $posttype["full_slug"] ? $posttype["full_slug"] : $slug;

			$posttype_exists    = post_type_exists($slug);
			$is_edited_posttype = Posttype_Helper::is_edited_posttype($slug);

			// do not register if post type already exists
			if ( $posttype_exists && ! $is_edited_posttype ) {
				if ( $use_cache ) {
					// set error message
					self::$errors[$slug] = sprintf(
						__("The post type with the unique name \"%s\" already exists. Please <a href='%s'>change the name</a> and be careful not to use names already used by WordPress such as \"Post\" or \"Page\".", 'greyd_hub'),
						$slug, get_edit_post_link($id)
					);
				}
				continue;
			}

			$plural     = isset($posttype["plural"]) ? $posttype["plural"] : $title;
			$singular   = isset($posttype["singular"]) ? $posttype["singular"] : $title;
			$position   = isset($posttype["position"]) ? intval($posttype["position"]) : Admin::DEFAULTS["position"];
			$icon       = isset($posttype["icon"]) ? "dashicons-".$posttype["icon"] : Admin::DEFAULTS["icon"];
			$supports   = isset($posttype["supports"]) ? array_merge(
				array(
					"title" => "title", 
					"custom-fields" => "custom-fields",
					/* author is set in the posttype supports */
					// "author" => "author"
				),
				(array) $posttype["supports"]
			) : Admin::DEFAULTS["supports"];

			// debug($supports);

			// register the posttype
			if ( ! $posttype_exists ) {
				// add args
				$arguments  = isset($posttype["arguments"]) ? (array)$posttype["arguments"] : Admin::DEFAULTS["arguments"];
				$search     = isset($arguments["search"]) && !empty($arguments["search"]) ? true : false;
				$archive    = isset($arguments["archive"]) ? true : false;
				$menus      = isset($arguments["menus"]) ? true : false;
				$hierarchical = isset($arguments["hierarchical"]) ? true : false;
				if ($hierarchical) $supports[] = "page-attributes";

				// define new post type
				$post_type_labels = array(
					'name'               => $plural,
					'singular_name'      => $singular,
					'menu_name'          => $title,
					'name_admin_bar'     => $title,
					'add_new'            => sprintf(__( "Create %s", 'greyd_hub' ), $singular),
					'add_new_item'       => sprintf(__( "Create %s", 'greyd_hub' ), $singular),
					'new_item'           => sprintf(__( "Create %s", 'greyd_hub' ), $singular),
					'edit_item'          => sprintf(__( "Edit %s", 'greyd_hub' ), $singular),
					'view_item'          => sprintf(__( "Show %s", 'greyd_hub'), $singular),
					'view_items'         => sprintf(__( "Show all %s", 'greyd_hub'), $plural),
					'all_items'          => sprintf(__( "All %s", 'greyd_hub'), $plural),
					'search_items'       => sprintf(__( "Search %s", 'greyd_hub'), $singular),
					'parent_item_colon'  => sprintf(__( "Parent %s", 'greyd_hub'), $singular),
					'not_found'          => sprintf(__( "No %s found", 'greyd_hub'), $plural),
					'not_found_in_trash' => sprintf(__( "No %s found in the trash", 'greyd_hub'), $plural),
				);
				$post_type_arguments = array(
					'labels'             => $post_type_labels,
					'description'        => __( "Description", 'greyd_hub' ),
					'public'             => true,
					'hierarchical'       => $hierarchical,
					'exclude_from_search'=> !$search,
					/**
					 * This needs to be true in order for the block editor to interact
					 * with the post type.
					 */
					'publicly_queryable' => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
					'show_in_nav_menus'  => $menus,
					'show_in_rest'       => true,
					/**
					 * @since 1.7.0 Prevent single posttype pages from being displayed.
					 * 
					 * If query_var is empty, null, or a boolean FALSE, WordPress will
					 * still attempt to interpret it (4.2.2) and previews/views of your
					 * custom post will return 404s.
					 */
					'query_var'          => true, // $archive,
					'rewrite'            => array( 'with_front' => false, 'slug' => $full_slug ),
					'capability_type'    => 'post',
					'has_archive'        => $archive,
					'admin_column_sortable' => true,
					'admin_column_filter'   => true,
					'menu_position'      => $position,
					'menu_icon'          => $icon,
					'supports'           => $supports,
				);

				//debug($posttype);
				register_post_type($slug, $post_type_arguments);
			}

			/**
			 * Add taxonomies
			 */
			$taxonomies = [];
			if ( isset($posttype["categories"]) && $posttype["categories"] === "categories" ) {
				$taxonomies[] = [
					"slug"          => "category",
					"singular"      => __("Category", 'greyd_hub'),
					"plural"        => __("Categories", 'greyd_hub'),
					"hierarchical"  => "hierarchical"
				];
			}
			if ( isset($posttype["tags"]) && $posttype["tags"] === "tags" ) {
				$taxonomies[] = [
					"slug"          => "tag",
					"singular"      => __("Tag", 'greyd_hub'),
					"plural"        => __("Tags", 'greyd_hub')
				];
			}
			if ( isset($supports["custom_taxonomies"]) && $supports["custom_taxonomies"] === "custom_taxonomies" && is_array($posttype["custom_taxonomies"]) && !empty($posttype["custom_taxonomies"]) ) {
				foreach ( $posttype["custom_taxonomies"] as $custom_tax ) {
					$taxonomies[] = $custom_tax;
				}
			}
		
			// register taxonomies
			foreach ( $taxonomies as $taxonomy ) {

				if ( is_object($taxonomy) ) $taxonomy = (array) $taxonomy;
				if ( !isset($taxonomy['slug']) ) continue;

				$tax_name = $slug."_".$taxonomy['slug'];

				/**
				 * @since 1.4.4 Handle taxonomy name character limit of 32.
				 */
				$full_tax_name = $tax_name;
				if ( strlen( $tax_name ) > 32 ) {
					$tax_name = $taxonomy['slug'];
				}

				$tax_singular       = isset($taxonomy['singular']) && !empty($taxonomy['singular']) ? $taxonomy['singular'] : $full_tax_name;
				$tax_plural         = isset($taxonomy['plural']) && !empty($taxonomy['plural']) ? $taxonomy['plural'] : $tax_singular;
				$tax_plural_2       = isset($taxonomy['plural_2']) && !empty($taxonomy['plural_2']) ? $taxonomy['plural_2'] : $tax_plural;
				$tax_public         = !isset($taxonomy['public']) || $taxonomy['public'] === "public" ? true : false;
				$tax_hierarchical   = isset($taxonomy['hierarchical']) && $taxonomy['hierarchical'] === "hierarchical" ? true : false;

				$tax_labels = array(
					'name'                          => $tax_plural,
					'singular_name'                 => $tax_singular,
					'menu_name'                     => $tax_plural,
					'search_items'                  => sprintf(__("Search %s", 'greyd_hub'), $tax_singular ),
					'all_items'                     => sprintf(__("All %s", 'greyd_hub'), $tax_plural ),
					'parent_item'                   => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
					'parent_item_colon'             => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
					'edit_item'                     => sprintf(__("Edit %s", 'greyd_hub'), $tax_singular ),
					'update_item'                   => sprintf(__("Update %s", 'greyd_hub'), $tax_singular ),
					'add_new_item'                  => sprintf(__("Add new %s", 'greyd_hub'), $tax_singular ),
					'new_item_name'                 => sprintf(__("New %s", 'greyd_hub'), $tax_singular ),
					'popular_items'                 => __( "Frequently used", 'greyd_hub'),
					'view_item'                     => sprintf( __( "View %s", 'greyd_hub'), $tax_singular ),
					'new_item_name'                 => sprintf( __( "New %s name", 'greyd_hub'), $tax_singular ),
					'separate_items_with_commas'    => sprintf( __( "Separate %s by commas", 'greyd_hub'), $tax_plural ),
					'add_or_remove_items'           => sprintf( __( "Add or remove %s", 'greyd_hub'), $tax_plural ),
					'choose_from_most_used'         => __( "Select from most used", 'greyd_hub' ),
					'not_found'                     => sprintf( __( "No %s found.", 'greyd_hub'), $tax_plural ),
					'no_terms'                      => sprintf( __( "No %s", 'greyd_hub'), $tax_plural ),
					'items_list_navigation'         => sprintf( __( "%s list navigation", 'greyd_hub'), $tax_plural ),
					'items_list'                    => sprintf( __( "%s list", 'greyd_hub'), $tax_plural ),
					'most_used'                     => __( "Frequently used", 'greyd_hub'),
					'back_to_items'                 => sprintf( __( "&larr; Back to %s", 'greyd_hub'), $tax_plural ),
				);
				if ( $taxonomy['slug'] === "tag" ) {
					$tax_labels = array_merge( $tax_labels, array(
						'parent_item'               => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
						'parent_item_colon'         => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
						'add_new_item'              => sprintf(__("Add new %s", 'greyd_hub'), $tax_singular ),
						'new_item_name'             => sprintf(__("New %s", 'greyd_hub'), $tax_singular ),
						'new_item_name'             => sprintf( __( "New %s name", 'greyd_hub'), $tax_singular ),
					) );
				}
				else if ( $taxonomy['slug'] === "category" ) {
					$tax_labels = array_merge( $tax_labels, array(
						'parent_item'               => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
						'parent_item_colon'         => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
						'add_new_item'              => sprintf(__("Add new %s", 'greyd_hub'), $tax_singular ),
						'new_item_name'             => sprintf(__("New %s", 'greyd_hub'), $tax_singular ),
						'new_item_name'             => sprintf( __( "New %s name", 'greyd_hub'), $tax_singular ),
					) );
				}
				$tax_args = array(
					'hierarchical'      => $tax_hierarchical,
					'labels'            => $tax_labels,
					'show_ui'           => $tax_public,
					'show_admin_column' => $tax_public,
					'show_in_rest'      => $tax_public,
					'query_var'         => $tax_public,
					'rewrite'           => array( 'slug' => $full_tax_name ),
				);

				$result = register_taxonomy( $tax_name, $slug, $tax_args );

				/**
				 * Handle an error registering the taxonomy.
				 * @since 1.4.4
				 */
				if ( is_wp_error( $result ) && is_admin() ) {
					echo '<div class="notice notice-error">
						<p>'.sprintf(
							__("The individual taxonomy %s of the post type %s could not be registered, probably because the unique name %s of the taxonomy is too long. Please edit the post type and assign a shorter name for the taxonomy. If this doesn't help, enable debug mode and contact your IT administrator or contact support.", 'greyd_hub'),
							'<strong>'.$tax_singular.'</strong>',
							'<strong>'.$singular.'</strong>',
							'<code>'.$taxonomy['slug'].'</code>'
						).'</p>
					</div>';
				}
			}

			/**
			 * Rewrite old posts to new posttype
			 */
			if ( ! $is_edited_posttype ) {
				$old_slug = get_post_meta( $id, 'old_posttype_slug', true );
				if ( !empty($old_slug) ) {

					$posts = get_posts([
						'post_type' => $old_slug,
						'numberposts' => -1,
						'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
					]);
					foreach ($posts as $post) {
						$update['ID'] = $post->ID;
						$update['post_type'] = $slug;
						wp_update_post( $update );
					}
					delete_post_meta( $id, 'old_posttype_slug' );
				}
			}

			/**
			 * Insert old terms into new taxonomies
			 * 
			 * If there were old taxonomies & terms set as a post meta,
			 * we need to insert them. This happens after a posttype is
			 * renamed or imported from another site.
			 */
			if ( ! $is_edited_posttype ) {
				$old_taxs = get_post_meta( $id, 'old_posttype_taxonomies', true );
				if ( !empty($old_taxs) && is_array($old_taxs) && count($old_taxs) ) {
					$tax_error = false;

					foreach ( $old_taxs as $old_tax_name => $old_terms ) {

						// get new tax name by replacing old
						$new_tax_name = str_replace( $old_slug, $slug, $old_tax_name );

						// loop through terms
						foreach( $old_terms as $term_id => $args ) {

							if ( $term_id = self::set_term_for_tax($new_tax_name, $args) ) {

								// check if there were any posts set with this term
								if ( !isset($args['posts']) || count($args['posts']) === 0 ) continue;

								// set terms for all posts
								foreach ( $args['posts'] as $post_id ) {
									if ( !self::add_term_to_post($post_id, $new_tax_name, $term_id) ) $tax_error = true;
								}
							}
							else $tax_error = true;
						}
					}
					if ( $tax_error ) self::$errors[$slug] = __( "While changing the title of this post type an error occurred converting categories and tags. It would be best to undo your changes so that no categories or tags are misallocated.", 'greyd_hub' );
					delete_post_meta( $id, 'old_posttype_taxonomies' );
				}
			}
		}
		
		/**
		 * Add global taxonomies
		 */
		foreach ( Posttype_Helper::get_global_taxonomies() as $dynamic_tax ) {

			// convert object to array
			if ( is_object($dynamic_tax) ) {
				$dynamic_tax = json_decode(json_encode($dynamic_tax), true);
			}
			
			// if arguments is object, convert to array
			if ( isset($dynamic_tax["arguments"]) && is_object($dynamic_tax["arguments"]) ) {
				$dynamic_tax["arguments"] = json_decode(json_encode($dynamic_tax["arguments"]), true);
			}

			// debug($dynamic_tax["posttypes"]);
			if ( !isset($dynamic_tax["posttypes"]) || empty($dynamic_tax["posttypes"]) ) continue;

			$taxonomy = array(
				"slug"          => $dynamic_tax['slug'],
				"singular"      => $dynamic_tax['singular'],
				"plural"        => $dynamic_tax['plural'],
				"public"        => isset($dynamic_tax["arguments"]["public"]) && $dynamic_tax["arguments"]["public"] ? "public" : "",
				"hierarchical"  => isset($dynamic_tax["arguments"]["hierarchical"]) && $dynamic_tax["arguments"]["hierarchical"] ? "hierarchical" : "",
			);
			
			if ( !isset($taxonomy['slug']) || empty($taxonomy['slug']) ) continue;

			$tax_name = $taxonomy['slug'];

			$tax_singular       = isset($taxonomy['singular']) && !empty($taxonomy['singular']) ? $taxonomy['singular'] : $tax_name;
			$tax_plural         = isset($taxonomy['plural']) && !empty($taxonomy['plural']) ? $taxonomy['plural'] : $tax_singular;
			$tax_public         = !isset($taxonomy['public']) || $taxonomy['public'] === "public" ? true : false;
			$tax_hierarchical   = isset($taxonomy['hierarchical']) && $taxonomy['hierarchical'] === "hierarchical" ? true : false;

			$tax_labels = array(
				'name'                          => $tax_plural,
				'singular_name'                 => $tax_singular,
				'menu_name'                     => $tax_plural,
				'search_items'                  => sprintf(__("Search %s", 'greyd_hub'), $tax_singular ),
				'all_items'                     => sprintf(__("All %s", 'greyd_hub'), $tax_plural ),
				'parent_item'                   => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
				'parent_item_colon'             => sprintf(__("Parent %s", 'greyd_hub'), $tax_singular ),
				'edit_item'                     => sprintf(__("Edit %s", 'greyd_hub'), $tax_singular ),
				'update_item'                   => sprintf(__("Update %s", 'greyd_hub'), $tax_singular ),
				'add_new_item'                  => sprintf(__("Add new %s", 'greyd_hub'), $tax_singular ),
				'new_item_name'                 => sprintf(__("New %s", 'greyd_hub'), $tax_singular ),
				'popular_items'                 => __( "Frequently used", 'greyd_hub'),
				'view_item'                     => sprintf( __( "View %s", 'greyd_hub'), $tax_singular ),
				'new_item_name'                 => sprintf( __( "New %s name", 'greyd_hub'), $tax_singular ),
				'separate_items_with_commas'    => sprintf( __( "Separate %s by commas", 'greyd_hub'), $tax_plural ),
				'add_or_remove_items'           => sprintf( __( "Add or remove %s", 'greyd_hub'), $tax_plural ),
				'choose_from_most_used'         => __( "Select from most used", 'greyd_hub' ),
				'not_found'                     => sprintf( __( "No %s found.", 'greyd_hub'), $tax_plural ),
				'no_terms'                      => sprintf( __( "No %s", 'greyd_hub'), $tax_plural ),
				'items_list_navigation'         => sprintf( __( "%s list navigation", 'greyd_hub'), $tax_plural ),
				'items_list'                    => sprintf( __( "%s list", 'greyd_hub'), $tax_plural ),
				'most_used'                     => __( "Frequently used", 'greyd_hub'),
				'back_to_items'                 => sprintf( __( "&larr; Back to %s", 'greyd_hub'), $tax_plural ),
			);
			$tax_args = array(
				'hierarchical'      => $tax_hierarchical,
				'labels'            => $tax_labels,
				'show_ui'           => $tax_public,
				'show_admin_column' => $tax_public,
				'show_in_rest'      => $tax_public,
				'query_var'         => $tax_public,
			);

			$result = register_taxonomy( $tax_name, $dynamic_tax["posttypes"], $tax_args );
		}
		
		/**
		 * @since 1.7.4
		 * See variable declaration for more info.
		 */
		self::$dynamic_posttypes_registered = true;
	}

	/**
	 * Rewrite rules if slugs have changed.
	 */
	public function rewrite_rules() {
		// rewrite permalinks if posttype slugs have been changed
		if ( is_admin() && get_transient( 'flush_rewrite' ) ) {
			// --> rewrite permalinks
			flush_rewrite_rules();
			delete_transient( 'flush_rewrite' );
		}
	}

	/**
	 * Add Meta Box to dynamic post
	 */
	public function add_dynamic_box() {

		// get options
		$posttypes = Posttype_Helper::get_dynamic_posttypes();
		//debug($posttypes);

		foreach ($posttypes as $id => $posttype) {
			add_meta_box(
				'dynamic_posttype_metabox', // ID
				__("Settings", 'greyd_hub'), // Title
				array($this, 'render_dynamic_meta_box'), // Callback
				$posttype["slug"], // CPT name
				'normal', // advanced = at bottom of page
				'high' // Priority
				//array $callback_args = null // Arguments passed to the callback
			);
		}
	}

	public function render_dynamic_meta_box($post) {
		// debug($post);
		__debug( get_post_meta( $post->ID, 'dynamic_meta', true ) );

		// get posttype data
		$slug       = $post->post_type;
		$posttype   = Posttype_Helper::get_dynamic_posttype_by_slug($slug);
		$fields     = (array) $posttype["fields"];

		// check if post type is an editable post type
		$is_edited_posttype = Posttype_Helper::is_edited_posttype($slug);

		// hide wpbakery if editor is not supported
		$supports = isset($posttype["supports"]) ? (array) $posttype["supports"] : array();
		$supports_editor = isset($supports["editor"]) ? true : false;
		if (!$supports_editor && !$is_edited_posttype) echo "<style> #poststuff .composer-switch, #poststuff #wpb_visual_composer { display: none !important; } </style>";

		if (count($fields) === 0) return false;

		// get data of post
		$values = Posttype_Helper::get_dynamic_meta($post->ID);
		// debug($values);

		foreach ($fields as $field) {
			$field = (array) $field;
			// debug($field);
			$value = isset($values[$field["name"]]) ? $values[$field["name"]] : null;
			$field_html = $this->render_meta_field($field, $value);

			$field_html = apply_filters( 'greyd_dynamic_posttype_render_field', $field_html, $field, $value, $post, $posttype  );

			echo $field_html;
		}

		Posttype_Helper::render_submit(false);

		/**
		 * Display a snackbar when not all required fields are filled
		 * @see https://github.com/WordPress/gutenberg/issues/17632
		 * 
		 * @since 1.0.9
		 */
		if ( $supports_editor && is_greyd_blocks() ) {
			?>
			<script id='posttype-required-fields__script'>
				( function ( wp ) {

					if ( typeof $ === 'undefined' ) {
						var $ = jQuery;
					}

					// get all empty required meta fields
					const getEmptyRequiredFields = function() {
						let fields = [];
						$('#dynamic_posttype_metabox .input_wrapper').each( function() {

							const label     = $(this).children('label');
							const input     = $(this).find('input, textarea, select').first();
							const required  = label.length ? label.hasClass('required') : input.attr('required');

							if ( required ) {
								const val = input.val();
								if ( !val || val.length === 0 ) {
									const name = label.length ? label.clone().children().remove().end().text() : input.attr('name');
									fields.push( name );
								}
							}
						});
						return fields;
					}

					// display notice for empty required fields
					const displayRequiredFieldsNotice = function() {
						const fields = getEmptyRequiredFields();
						if ( fields.length ) {
							wp.data.dispatch( 'core/notices' ).createNotice(
								'info',
								wp.i18n.sprintf( wp.i18n._n(
									'<?php echo __("Attention: You have not filled out %d required field of this post type: %s.", 'greyd_hub'); ?>',
									'<?php echo __("Attention: You have not filled out %d required fields of this post type: %s.", 'greyd_hub'); ?>',
									fields.length
								), fields.length, fields.join(', ')
								),
								{
									type: 'snackbar',
									isDismissible: true,
									icon: wp.element.createElement( wp.components.Icon, { icon: 'warning' } ),
								}
							);
						}
					}

					// subscribe to saving post action
					var checked = true;
					wp.data.subscribe( () => {
						if ( wp.data.select( 'core/editor' ).isSavingPost() ) {
							checked = false;
						} else {
							if ( ! checked ) {
								checked = true;
								displayRequiredFieldsNotice();
							}
						}
					} );

					// call on init
					<?php if (isset($_GET['post'])) echo 'displayRequiredFieldsNotice();'; ?>

				} )( window.wp );
			</script>
			<?php
		}
	}

	public function render_meta_field ($atts=[], $value=null) {
		if (!isset($atts) || !is_array($atts) || count($atts) === 0) return false;

		$type           = !empty($atts["type"]) ? esc_attr($atts["type"]) : "text";
		$name           = $atts["name"];
		$label          = !empty($atts["label"]) ? esc_attr($atts["label"]) : null;
		$default        = !empty($atts["default"]) ? esc_attr($atts["default"]) : null;
		$value          = !empty($value) ? $value : $default;
		$required       = isset($atts["required"]) && $atts["required"] === "required" ? "required" : "";
		$description    = !empty($atts["description"]) ? esc_attr($atts["description"]) : null;
		$locked         = isset($atts["fill"]) && $atts["fill"] === false ? true : false;

		// placeholder
		if ($type === "file") {
			$placeholder = __("Insert file", 'greyd_hub');
		} 
		else if ($type === "dropdown") {
			$placeholder = _x("Please select", 'small', 'greyd_hub');
		} 
		else if ($type === "url") {
			$placeholder = __("Select URL", 'greyd_hub');
		} 
		else {
			$placeholder = __("Enter here", 'greyd_hub');
		}
		$placeholder = !empty($atts["placeholder"]) ? $atts["placeholder"] : $placeholder;

		// render
		ob_start();

		if ($type === "hr") {
			echo "<hr>";
			if (!empty($description)) echo '<i class="descr">'.$description.'</i>';
		}
		else if ($type === "space") {
			$description = '';
			$height = !empty($atts["height"]) ? esc_attr($atts["height"]) : '30';
			$height = strpos($height, 'px') === false || strpos($height, 'em') === false ? $height.'px' : $height;
			echo "<div style='height:".$height.";'></div>";
		}
		else if ($type === "headline") {
			$unit = isset($atts["h_unit"]) ? $atts["h_unit"] : "h3";
			if (!empty($label)) echo "<".$unit.">".$label."</".$unit.">";
			if (!empty($description)) echo '<i class="descr">'.$description.'</i>';
		}
		else if ($type === "descr") {
			if (!empty($label)) echo "<p>".$label."<p>";
			if (!empty($description)) echo '<i class="descr">'.$description.'</i>';
		}
		else {
			if (empty($atts["name"])) {
				Helper::show_message( sprintf(__("A unique name has not been defined for field %s.", 'greyd_hub'), $label ) , 'danger');
				return false;
			}

			if ( empty( $value) ) {
				$value = "";
			}
			else if ( $type === "textarea" || $type === "text_html" ) {
				$value = htmlspecialchars_decode($value);
			}
			else {
				$value = html_entity_decode($value);
			}

			// wrapper
			echo '<div class="input_wrapper" '.( $locked ? 'data-locked="locked"' : '' ).'>';

			// label
			if (!empty($label)) echo '<label class="'.$required.'">'.$label.'<span class="required-star">&nbsp;*</span></label>';

			// textarea
			if ($type === "textarea") {
				echo '<textarea name="'.$name.'" placeholder="'.$placeholder.'" rows="5" '.$required.'>'.$value.'</textarea>';
			}
			// file
			else if ($type === "file") {
				Posttype_Helper::render_filepicker($value, $name, $placeholder);
			}
			// html
			else if ($type === "text_html") {
				wp_editor(
					$value,
					$name,
					$settings = array(
						'editor_height' => 100,
						'media_buttons' => false,
						/**
						 * @since 2.15.0 Always force the TinyMCE editor to wrap the text in <p>-tags.
						 */
						'wpautop'       => false,
					)
				);
			}
			// link
			else if ($type === "url") {
				Posttype_Helper::render_linkpicker($value, $name, $placeholder, $required);
			}
			// text
			else if (strpos($type, "text") !== false) {
				echo '<input type="text" class="'.$type.'" name="'.$name.'" placeholder="'.$placeholder.'" value="'.$value.'" '.$required.'>';
			}
			// number
			else if ($type === "number") {
				$min    = !empty($atts["min"])   ? 'min="'.$atts["min"].'"' : "";
				$max    = !empty($atts["max"])   ? 'max="'.$atts["max"].'"' : "";
				$steps  = !empty($atts["steps"]) ? 'step="'.$atts["steps"].'"' : "";
				echo '<input type="number" name="'.$name.'" placeholder="'.$placeholder.'" value="'.$value.'" '.$required.' '.$min.' '.$max.' '.$steps.'>';
			}
			// dropdown & radio
			else if ($type === "dropdown" || $type === "radio") {
				$options_input = isset($atts["options"]) ? explode(',', $atts['options']) : "";
				if (empty($options_input)) {
					echo __("No options set", 'greyd_hub');
				} else {
					$options = array();
					foreach ((array) $options_input as $option) {
						$split = explode('=', $option);
						if (count($split) > 1) {
							$val = $split[0];
							$show = $split[1];
						} else {
							$val = $show = $split[0];
						}
						array_push( $options, ["value" => trim($val), "show" => trim($show)] );
					}
				}
				//debug($options);

				if ($type === "dropdown") {
					echo "<select name='$name' $required>";
					echo "<option>$placeholder</option>";
					foreach ($options as $option) {
						$selected = $value === $option['value'] ? "selected" : "";
						echo "<option value='".$option['value']."' $selected>".$option['show']."</option>";
					}
					echo "</select>";
				} else {
					echo "<fieldset $required>";
					foreach ($options as $option) {
						$selected = $value === $option['value'] ? "checked" : "";
						echo "<div><label for='".$option['value']."'>".
								"<input type='radio' name='$name' id='".$option['value']."' value='".$option['value']."' $required $selected>".
								$option['show'].
							"</label></div>";
					}

					echo "</fieldset>";
				}
			}
			// other: email, date ...
			else {
				echo '<input type="'.$type.'" name="'.$name.'" placeholder="'.$placeholder.'" value="'.$value.'" '.$required.'>';
			}

			if (!empty($description)) echo '<i class="descr">'.$description.'</i>';
			echo '</div>';
		}

		// Return
		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	/**
	 * Save dynamic post
	 */
	public function save_dynamic_post($post_id, $post) {
		if(!is_admin()) return;
		if(empty($_POST)) return;

		// save transient for rewriting permalink rules
		set_transient( 'flush_rewrite', true );

		// return if not called via submitted wordpress backend form
		if ( !isset($_POST['meta_action']) || $_POST['meta_action'] !== 'wp_backend' ) return;

		// get the post type
		$posttype = Posttype_Helper::get_dynamic_posttype_by_slug($post->post_type);

		// return if not a dynamic post type
		if ($posttype === false) return;

		// get all fields
		$fields = (array) $posttype["fields"];
		if (count($fields) === 0) return false;

		$metadata = [];
		foreach ($fields as $field) {
			$field  = (array) $field;
			$name   = $field["name"];
			$value  = isset($_POST[$name]) ? $_POST[$name] : '';
			if ( $field["type"] === "textarea" || $field["type"] === "text_html" ) {
				$value = htmlspecialchars($value);
			} else {
				$value = htmlentities( sanitize_text_field($value) );
			}
			$metadata[$name] = $value;
		}
		update_post_meta($post_id, Admin::META, $metadata);
	}

	public function display_errors($post_id=null) {
		$post_type = get_post_type($post_id);
		foreach ((array) self::$errors as $slug => $error) {
			// don't show if not in posttype screen
			if ($post_type != Admin::POST_TYPE && $post_type !== $slug) continue;
			echo '<div class="error">
				<p>'.$error.'</p>
			</div>';
		}
		$screen = get_current_screen();
		if ($screen->base == "options-permalink") {
			echo '<div class="notice notice-warning">
				<p>'.__("<b>Attention:</b> If you change the permalink settings, remember that posts, dynamic posts and page URLs can come into conflict.", 'greyd_hub').'</p>
			</div>';
		}
	}

	/**
	 * Add VC Roles
	 */
	public function add_vc_roles($rules=[]) {
		return array_merge( $rules, self::get_posttype_vc_roles() );
	}


	/*
	====================================================================================
		Capabilities
	====================================================================================
	*/

	public function add_capabilities() {

		$script = $styles = $styles_type = $styles_post = $script_type = $script_post = [];
		$not_allowed_css = '%1$s { cursor: default; pointer-events: none; opacity: .5; }';

		$posttypes = Posttype_Helper::get_dynamic_posttypes();
		foreach ( $posttypes as $id => $posttype ) {

			if ( !isset($posttype['capabilities']) ) continue;

			if ( is_object($posttype['capabilities']) ) {
				$posttype['capabilities'] = json_decode( json_encode($posttype['capabilities']), true );
			}

			/*
			"capabilities" => [
				"posttype" => [
					"edit" => true | false,
					"delete" => true | false,
					"quickedit" => true | false,
					"title" => true | false,
					"setup" => true | false,
					"setup-wording" => true | false,
					"setup-menu" => true | false,
					"setup-features" => true | false,
					"setup-architecture" => true | false,
					"fields" => true | false,
				],
				"posts" => [
					"add" => true | false,
					"edit" => true | false,
					"delete" => true | false,
					"quickedit" => true | false,
					"title" => true | false,
				],
			]
			*/
			$caps_type = isset($posttype['capabilities']['posttype']) ? (array) $posttype['capabilities']['posttype'] : array();
			foreach ( $caps_type as $cap => $allowed ) {
				if ( $allowed ) continue;
				switch ($cap) {
					case "edit":
						$styles[] = sprintf( $not_allowed_css, "#post-".$id." .row-actions > .edit" ); // row action edit button
						$css = sprintf( $not_allowed_css, ".page-title-action.button-ghost"); // 'edit post type' button on posts pages
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						$script_type[] = "$('#post-".$id." .row-title').removeAttr('href').css('color', 'inherit')"; // remove link to edit screen
						break;
					case "delete":
						$styles[] = sprintf( $not_allowed_css, "#post-".$id." .row-actions > .trash" ); // row action delete button
						$css = sprintf( $not_allowed_css, "#delete-action"); // delete button inside posttype
						$styles_type[$id] = isset($styles_type[$id]) ? $styles_type[$id]." ".$css : $css;
						break;
					case "quickedit":
						$styles[] = sprintf( $not_allowed_css, "#post-".$id." .row-actions > .inline" );
						break;
					case "title":
						$css = sprintf( $not_allowed_css, '#titlediv');
						$styles_type[$id] = isset($styles_type[$id]) ? $styles_type[$id]." ".$css : $css;
						break;
					case "setup":
						$css = sprintf( $not_allowed_css, '#greyd_posttype_settings');
						$styles_type[$id] = isset($styles_type[$id]) ? $styles_type[$id]." ".$css : $css;
						break;
					case "setup-wording":
					case "setup-menu":
					case "setup-features":
					case "setup-architecture":
					case "setup-taxonomies":
						$css = sprintf( $not_allowed_css, '#settings_'.str_replace("setup-", "", $cap) );
						$styles_type[$id] = isset($styles_type[$id]) ? $styles_type[$id]." ".$css : $css;
						break;
					case "add-fields":
						$css = sprintf( $not_allowed_css, '#greyd_posttype_fields');
						$styles_type[$id] = isset($styles_type[$id]) ? $styles_type[$id]." ".$css : $css;
						break;
				}
			}

			$caps_post = isset($posttype['capabilities']['posts']) ? (array) $posttype['capabilities']['posts'] : array();
			foreach ( $caps_post as $cap => $allowed ) {
				if ( $allowed ) continue;
				switch ($cap) {
					case "add":
						$styles[] = "#menu-posts-".$posttype['slug']." .wp-submenu .wp-first-item + li { display: none; }"; // adminbar create menu item
						$css = sprintf($not_allowed_css, "h1.wp-heading-inline + .page-title-action, .row-actions > .duplicate"); // add new button headline
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						break;
					case "edit":
						$css = sprintf($not_allowed_css, ".row-actions > .edit"); // row action edit button
						$css .= ".bulkactions select option[value='edit'] { display: none; }"; // bulk actions option
						$css .=sprintf( $not_allowed_css, "#submitdiv" ); // submit post
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						$script_post[] = "$('.row-title').removeAttr('href').css('color', 'inherit')"; // remove link to edit screen
						break;
					case "delete":
						$css = sprintf($not_allowed_css, ".row-actions > .trash, #delete-action"); // row action delete & delete button inside post
						$css .= ".bulkactions select option[value='trash'] { display: none; }"; // bulk actions option
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						break;
					case "quickedit":
						$css = sprintf($not_allowed_css, ".row-actions > .inline");
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						break;
					case "title":
						$css = sprintf( $not_allowed_css, '#titlediv');
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						break;
					case "edit-taxonomies":
						$divs = [
							"form#addtag", // add new taxonomy
							"body.edit-tags-php form input:not([name=paged])", // manage taxonomies
							"body.edit-tags-php form select", // manage taxonomies
							"body.edit-tags-php form table", // manage taxonomies
							"form[name=\"edittag\"]",
							"div[id^=\"tagsdiv-\"]", // post add tags
							"div[id$=\"categorydiv\"]" // post add categories
						];
						$css = sprintf( $not_allowed_css, implode(", ", $divs) );
						$styles_post[$id] = isset($styles_post[$id]) ? $styles_post[$id]." ".$css : $css;
						break;
				}
			}
		}

		// Vars
		$page           = isset($_GET['page']) ? $_GET['page'] : '';
		$screen         = function_exists('get_current_screen') ? get_current_screen() : false;
		$posttype_cur   = get_post_type() !== false ? get_post_type() : ( $screen !== false && isset($screen->post_type) ? $screen->post_type : "" );

		// Inside Posttypes Screen
		if ( $posttype_cur === Admin::POST_TYPE ) {
			$post_id = isset($_GET['post']) ? $_GET['post'] : false;
			if ( $post_id && isset($styles_type[$post_id]) ) $styles[] = $styles_type[$post_id]; // inside post screen
			if ( count($script_type) > 0 ) $script = array_merge( $script, $script_type );
		}

		// Inside Posts Screen
		$cpt = Posttype_Helper::get_dynamic_posttype_by_slug($posttype_cur);
		if ($cpt !== false) {
			$post_id = $cpt['id'];
			if ( isset($styles_post[$post_id]) ) $styles[] = $styles_post[$post_id];
			if ( count($script_post) > 0 ) $script = array_merge( $script, $script_post );
		}

		/*
			Render Inline Scripts & Styles
		*/
		if ( count($script) > 0 ) wp_add_inline_script( $this->config->plugin_name.'_posttypes_js', "jQuery(function() { ".implode( " ", $script )." });", 'after');
		if ( count($styles) > 0 ) wp_add_inline_style( $this->config->plugin_name.'_posttypes_css', implode( " ", $styles) );
	}


	/*
	====================================================================================
		Helper functions
	====================================================================================
	*/

	/**
	 *  Insert or find a term in a taxonomy
	 *
	 *  @returns the term_id on success, false on failure
	 */
	public static function set_term_for_tax( $tax_name='', $args=[] ) {
		if ( empty($tax_name) ) return false;
		if ( !is_array($args) || !isset($args['slug']) || !isset($args['name']) ) return false;

		// find if term
		$term = get_term_by( 'slug', $args['slug'], $tax_name, 'ARRAY_A' );

		// if not found -> insert new term
		if ( !$term ) $term = wp_insert_term( $args['name'], $tax_name, $args );

		// WP_Error occurred
		if ( is_wp_error($term) ) {
			debug($term);
			return false;
		}

		// return term_id on success
		return isset($term['term_id']) ? $term['term_id'] : false;
	}

	/**
	 *  Assigns a term to a post
	 *  without deleting the current terms of the post
	 *
	 *  @returns array of assigned term_ids on success, false on failure
	 */
	public static function add_term_to_post($post_id='', $tax_name='', $term_id='') {
		if ( empty($post_id) || empty($tax_name) || empty($term_id) ) return false;

		$terms = [];

		// get existing terms for post
		$existing_terms = wp_get_post_terms( $post_id, $tax_name );

		// WP_Error occurred
		if ( is_wp_error($existing_terms) ) {
			debug( $existing_terms, true );
		}
		// terms found
		else if ( is_array($existing_terms) ) {
			foreach( $existing_terms as $term ) {
				$terms[] = $term->term_id;
			}
		}
		$terms[] = $term_id;

		// make all terms unique
		$tags = array_keys( array_flip($terms) );

		// set terms
		$result = wp_set_post_terms( $post_id, $tags, $tax_name );

		// WP_Error occurred
		if ( is_wp_error($result) ) {
			debug( $result, true );
			return false;
		}

		return $result;
	}

	/**
	 * Add posttype to array of vc_roles
	 */
	public static function get_posttype_vc_roles() {
		$vc_roles   = [];
		$posttypes  = Posttype_Helper::get_dynamic_posttypes();
		//debug($posttypes);

		if (count($posttypes) > 0) {
			foreach ($posttypes as $id => $posttype) {
				if ( isset($posttype["supports"]) && array_key_exists("editor", (array) $posttype["supports"]) )
					$vc_roles[] = $posttype["slug"];;
			}
		}
		return $vc_roles;
	}

}
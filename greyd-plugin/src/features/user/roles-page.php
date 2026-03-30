<?php
/**
 * Admin page to manage users roles
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Roles_Page {

	/**
	 * Class constructor.
	 */
	public function __construct() {

	}

	/**
	 * Render roles page.
	 *
	 * @param string $cap   The required capability to edit things.
	 */
	public function render_roles_page( $cap ) {

		if ( !Manage::wp_up_to_date( true ) ) {
			return;
		}

		// todo: network admin and global roles
		if ( is_multisite() && is_network_admin() ) {

			// title
			echo '<h1>' . __( 'Roles', 'greyd_hub' ) . '</h1><hr>';

			$links = array();
			foreach ( get_sites( array( 'number' => 999 ) ) as $blog ) {
				// debug($blog);
				// debug($blog->blogname);
				// debug($blog->home);
				array_push(
					$links,
					sprintf(
						'- %s%s%s',
						'<a href="' . $blog->home . '/wp-admin/admin.php?page=greyd_user&tab=roles" target="_blank">',
						'<strong>' . $blog->blogname . '</strong>',
						'</a>'
					)
				);
			}

			echo Helper::render_info_box(
				array(
					'text' =>
						__( 'You can manage user roles on each site individually:', 'greyd_hub' ) . '<br>' .
						implode( '<br>', $links ),
				)
			);

			return;

		}

		$user_can_edit = current_user_can( $cap );
		if ( $user_can_edit && function_exists( 'wc_current_user_has_role' ) && wc_current_user_has_role( 'shop_manager' ) ) {
			// woo modifies the 'edit_users' capability of Shop Managers with a hook which also enables editing of roles.
			// this is fixed by re-checking with the users raw capabilites.
			$allcaps = wp_get_current_user()->allcaps;
			$user_can_edit = is_array($allcaps) && isset($allcaps[$cap]);
		}

		$query_args_create = array(
			'page'   => wp_unslash( $_REQUEST['page'] ),
			'tab'    => 'roles',
			'action' => 'create',
		);
		$create_url        = remove_query_arg( array( 'action', 'roles', 'role', '_wpnonce' ) );
		$create_url        = esc_url( add_query_arg( $query_args_create, $create_url ) );
		$create_role_link  = "<a href='" . $create_url . "' class='page-title-action'>" . __( 'Create new role', 'greyd_hub' ) . '</a>';

		// get view
		// '', 'edit', 'view', 'create', 'delete' or 'clone'
		$current_view = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$current_view = ! empty( $_REQUEST['action2'] ) ? $_REQUEST['action2'] : $current_view;
		// check permission
		if ( ! $user_can_edit ) {
			$create_role_link = '';
			if ( $current_view == 'edit' ) {
				echo Helper::show_message( __( 'You are not allowed to edit roles.', 'greyd_hub' ), 'warning inline' );
				$current_view = 'view';
				$url          = add_query_arg( array( 'action' => 'view' ), remove_query_arg( 'action' ) );
				echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
			} elseif ( $current_view == 'create' ) {
				echo Helper::show_message( __( 'You are not allowed to create roles.', 'greyd_hub' ), 'warning inline' );
				$current_view = '';
				$url          = remove_query_arg( 'action' );
				echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
			}
		}
		// get role
		$role = false;
		if ( $current_view == 'create' ) {
			// blank role
			$role = Manage::get_role( '' );
		} elseif ( $current_view == 'edit' || $current_view == 'view' ) {
			if ( isset( $_GET['role'] ) ) {
				$role = Manage::get_role( $_GET['role'] );
				// debug($role);
				if ( ! $role ) {
					echo Helper::show_message( sprintf( __( 'The role "%s" does not exist.', 'greyd_hub' ), $_GET['role'] ), 'warning inline' );
					$current_view = '';
					$url          = remove_query_arg( array( 'action', 'role' ) );
					echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
				}
			} else {
				// no role
				echo Helper::show_message( __( 'No role selected.', 'greyd_hub' ), 'warning inline' );
				$current_view = '';
				$url          = remove_query_arg( 'action' );
				echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
			}
		}

		//
		// render role edit page
		if ( $role && ( $current_view == 'edit' || $current_view == 'view' || $current_view == 'create' ) ) {

			if ( $current_view == 'edit' && $role['slug'] == 'super' ) {
				// super admin is not editable
				echo Helper::show_message( sprintf( __( 'The role "%s" is not editable.', 'greyd_hub' ), $role['title'] ), 'warning inline' );
				$current_view = 'view';
				$url          = add_query_arg( array( 'action' => 'view' ), remove_query_arg( 'action' ) );
				echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
			}
			// handle POST Data
			if ( $current_view != 'view' && ! empty( $_POST ) ) {
				$response = $this->save_data( $current_view, $role, $_POST );
				if ( $response ) {
					$old_slug = $role['slug'];
					$role     = Manage::get_role( $response );
					if ( $current_view == 'edit' ) {
						echo Helper::show_message( sprintf( __( 'Role "%s" updated.', 'greyd_hub' ), $role['title'] ), 'success inline' );
						if ( $old_slug != $role['slug'] ) {
							$url = add_query_arg( array( 'role' => $role['slug'] ), remove_query_arg( 'role' ) );
							echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
						}
					}
					if ( $current_view == 'create' ) {
						echo Helper::show_message( sprintf( __( 'Role "%s" created.', 'greyd_hub' ), $role['title'] ), 'success inline' );
						$current_view = 'edit';
						$url          = add_query_arg(
							array(
								'action' => 'edit',
								'role'   => $role['slug'],
							),
							remove_query_arg( 'action' )
						);
						echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
					}
				} else {
					if ( $current_view == 'edit' ) {
						echo Helper::show_message( sprintf( __( 'Unable to update role "%s".', 'greyd_hub' ), $role['title'] ), 'error inline' );
					}
					if ( $current_view == 'create' ) {
						echo Helper::show_message( __( 'Unable to create role.', 'greyd_hub' ), 'error inline' );
					}
				}
			}

			// debug( array_keys($GLOBALS['wp_post_types']) );
			// debug( $GLOBALS['wp_post_types']['greyd_posttypes'] );
			// global $post_type_meta_caps;
			// debug($post_type_meta_caps);
			// debug(array_keys(Manage::$blocks));

			$title  = __( 'Roles', 'greyd_hub' );
			$submit = '';
			$locked = '';
			if ( $current_view == 'edit' ) {
				$title  = __( 'Edit role', 'greyd_hub' );
				$submit = __( 'Update role', 'greyd_hub' );
			} elseif ( $current_view == 'view' ) {
				$title  = __( 'View role', 'greyd_hub' );
				$locked = 'locked';
			} elseif ( $current_view == 'create' ) {
				$title            = __( 'Create role', 'greyd_hub' );
				$submit           = __( 'Create role', 'greyd_hub' );
				$create_role_link = '';
			}

			// title headlines
			echo "<h1 class='wp-heading-inline'>" . $title . '</h1>';
			echo $create_role_link;

			/**
			 * it is possible to add metaboxes to custom screens.
			 * add them at the right time without filter:
			 * https://developer.wordpress.org/reference/functions/add_meta_box/
			 * and render them manually by calling do_meta_boxes:
			 * https://developer.wordpress.org/reference/functions/do_meta_boxes/
			 */
			$this->add_meta_boxes();

			// form
			echo "<form id='roles-edit-form' method='post'>";
			wp_nonce_field( 'greyd_users_edit_role' );

			// poststuff
			echo "<div id='poststuff' class='metabox-holder " . $locked . "'>";

				// render title input
				echo "<div id='post-body-content'>";
					echo "<div id='titlediv'>
							<input type='text' name='role_settings[title]' id='title' size='30' value='" . $role['title'] . "' spellcheck='true' autocomplete='off' required>
						</div>";
				echo '</div>';

				// render metaboxes
				echo "<div class='postbox-container'>";
					do_meta_boxes(
						'edit-role',
						'normal',
						array(
							'role' => $role,
							'view' => $locked,
						)
					);
				echo '</div>';

			echo '</div>';

			// Submit
			if ( $submit != '' ) {
				echo submit_button( $submit );
			}
			echo '</form>';

		}

		//
		// render roles list page
		else {

			// handle REQUEST/GET Data
			if ( $current_view != '' && ! empty( $_REQUEST ) ) {
				// debug($current_view);
				$this->process_bulk_action( $current_view, $_REQUEST );

			}

			// init roles list table
			$roles_table = new Roles_List_Table();
			$roles_table->prepare_items( $user_can_edit );
			// debug($roles_table);

			// title
			echo "<h1 class='wp-heading-inline'>" . __( 'Roles', 'greyd_hub' ) . '</h1>';
			echo $create_role_link;

			// render table
			$roles_table->render_table();

		}

	}

	/*
	=======================================================================
		Metaboxes
	=======================================================================
	*/

	/**
	 * Add Metaboxes to 'view', 'edit' and 'create' screens
	 */
	public function add_meta_boxes() {

		// metaboxes
		add_meta_box(
			'greyd_role_settings', // ID
			__( 'Settings', 'greyd_hub' ), // Title
			array( $this, 'render_settings_box' ), // Callback
			'edit-role', // screen name
			'normal'
		);
		add_meta_box(
			'greyd_role_caps', // ID
			__( 'Capabilities', 'greyd_hub' ), // Title
			array( $this, 'render_caps_box' ), // Callback
			'edit-role', // screen name
			'normal'
		);
		add_meta_box(
			'greyd_role_posttype_caps', // ID
			__( 'Post type capabilities', 'greyd_hub' ), // Title
			array( $this, 'render_posttype_caps_box' ), // Callback
			'edit-role', // screen name
			'normal'
		);
		add_meta_box(
			'greyd_role_block_caps', // ID
			__( 'Block capabilities', 'greyd_hub' ), // Title
			array( $this, 'render_block_caps_box' ), // Callback
			'edit-role', // screen name
			'normal'
		);
		add_meta_box(
			'greyd_role_meta', // ID
			__( 'Meta fields', 'greyd_hub' ), // Title
			array( $this, 'render_metafields_box' ), // Callback
			'edit-role', // screen name
			'normal'
		);

	}

	/**
	 * Render the primary settings metabox.
	 *
	 * @param array $args
	 *      @property mixed role    The role object with all settings.
	 *      @property string view   Indication for editability: '' or 'locked'
	 */
	public function render_settings_box( $args ) {

		$role = $args['role'];
		// debug($role);

		// render
		echo '<table class="greyd_table vertical big meta_table">';

			$row = array(
				'id'      => 'settings_wording',
				'title'   => __( 'Name', 'greyd_hub' ),
				'desc'    => __( 'For the correct designation of commands and states.', 'greyd_hub' ),
				'content' =>
					'<div>
						<label for="role_settings[slug]">' . __( 'Slug', 'greyd_hub' ) . '</label><br>
						<input type="text" name="role_settings[slug]" value="' . ( isset( $role['slug'] ) ? $role['slug'] : '' ) . '" autocomplete="off" required>
					</div>',
			);
			echo '<tr id="' . $row['id'] . '">
					<th><h3>' . $row['title'] . '</h3>' . $row['desc'] . '</th>
					<td>' . $row['content'] . '</td>
				</tr>';

			$hide_posts = isset( $role['hide_posts'] ) && $role['hide_posts'] ? "checked='checked'" : '';
			$hide_pages = isset( $role['hide_pages'] ) && $role['hide_pages'] ? "checked='checked'" : '';
			$row = array(
				'id'      => 'settings_core_posttype',
				'title'   => __( 'Disable post types', 'greyd_hub' ),
				'desc'    => __( 'If the capabilities for Posts or Pages are removed, custom post types may not be editable by this role. Instead, use this option to disable the Posts or Pages menu items.', 'greyd_hub' ),
				'content' =>
					'<div>
						<label for="role_settings_hide_posts">
							<input type="checkbox" id="role_settings_hide_posts" name="role_settings[hide_posts]" ' . $hide_posts . ' autocomplete="off">' . __( 'Posts', 'greyd_hub' ) . '
						</label>
					</div><div>
						<label for="role_settings_hide_pages">
							<input type="checkbox" id="role_settings_hide_pages" name="role_settings[hide_pages]" ' . $hide_pages . ' autocomplete="off">' . __( 'Pages', 'greyd_hub' ) . '
						</label>
					</div>',
			);
			echo '<tr id="' . $row['id'] . '">
					<th><h3>' . $row['title'] . '</h3>' . $row['desc'] . '</th>
					<td>' . $row['content'] . '</td>
				</tr>';

		echo '</table>';

	}

	/**
	 * Render the primitive capabilities metabox.
	 *
	 * @param array $args
	 *      @property mixed role    The role object with all settings.
	 *      @property string view   Indication for editability: '' or 'locked'
	 */
	public function render_caps_box( $args ) {

		$role = $args['role'];
		// debug($role);
		$labels = array(
			'network' => __( 'Network', 'greyd_hub' ),
			'general' => __( 'General', 'greyd_hub' ),
			'posts'   => __( 'Posts', 'greyd_hub' ),
			'pages'   => __( 'Pages', 'greyd_hub' ),
			'themes'  => __( 'Themes', 'greyd_hub' ),
			'plugins' => __( 'Plugins', 'greyd_hub' ),
			'user'    => __( 'User', 'greyd_hub' ),
			'level'   => __( 'Level', 'greyd_hub' ),
			'custom'  => __( 'Custom', 'greyd_hub' ),
		);

		$menu    = '<li data-tab="all" class="active"><big>All</big></li>';
		$content = '';

		$all_caps = Manage::get_capabilities();
		// debug($all_caps);
		foreach ( $all_caps as $data_cap => $caps ) {
			// menu item
			$menu .= '<li data-tab="' . $data_cap . '"><big>' . $labels[ $data_cap ] . '</big></li>';
			// contents
			foreach ( $caps as $cap ) {
				$sel      = isset( $role['capabilities'][ $cap ] ) ? "checked='checked'" : '';
				$content .= "<li data-tab='" . $data_cap . "'>
								<label for='role_capability_" . $cap . "' class='flex'>" . $cap . "
									<input type='checkbox' id='role_capability_" . $cap . "' name='role_settings[capabilities][" . $cap . "]' " . $sel . " autocomplete='off'>
								</label>
							</li>";
			}
		}
		if ( $args['view'] != 'locked' ) {
			$content .= '<li data-tab="custom"><div class="flex add_cap">
							<input type="text" value="" autocomplete="off">
							<span class="button button-primary"><span class="dashicons dashicons-plus"></span>&nbsp;&nbsp;' . __( 'Add capability', 'greyd_hub' ) . '</span>
						</div></li>';
		}

		// render
		echo '<table class="greyd_table vertical meta_table tabs_table">';

			echo '<tr id="settings_capabilities">
					<th><ul>' . $menu . '</ul></th>
					<td><div class="items_ul"><ul>' . $content . '</ul></div></td>
				</tr>';

		if ( $args['view'] != 'locked' ) {

			$options = "<option value=''>" . __( 'Select role', 'greyd_hub' ) . '</option>';
			foreach ( Manage::get_basic_roles() as $role ) {
				$options .= "<option value='" . $role['slug'] . "' data-caps='" . json_encode( $role['capabilities'] ) . "'>" . $role['title'] . '</option>';
			}
			echo '<tr id="settings_capabilities_foot">
					<th></th>
					<td><div class="li flex" style="align-items:center">
						<span class="copy_from">
							' . __( 'Copy capabilities from', 'greyd_hub' ) . '
							&nbsp;<select autocomplete="off">' . $options . '</select>&nbsp;
							<span class="button button-primary" style="display:none">
								<span class="dashicons dashicons-admin-page resize"></span>
							</span>
						</span>
						<span class="toggle_all">
							<span class="unmark_all">' . __( 'Unmark all', 'greyd_hub' ) . '</span> | <span class="mark_all">' . __( 'Mark all', 'greyd_hub' ) . '</span>
						</span>
					</div></td>
				</tr>';

		}

		echo '</table>';

	}

	/**
	 * Render the extended post_type capabilities metabox.
	 *
	 * @param array $args
	 *      @property mixed role    The role object with all settings.
	 *      @property string view   Indication for editability: '' or 'locked'
	 */
	public function render_posttype_caps_box( $args ) {

		$role = $args['role'];
		// debug($role["posttype_caps"]);

		$menu    = '<li data-tab="all" class="active"><big>All</big></li>';
		$content = '';

		// debug(Manage::$posttypes);
		foreach ( Manage::$posttypes as $pt => $data ) {
			// debug($data);
			$posttype = get_post_type_object( $pt );
			// menu item
			$menu .= '<li data-tab="' . $pt . '"><big>' . $posttype->label . '</big></li>';
			// contents
			$caps = (array) $posttype->cap;
			unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'] );
			$caps = array_unique( array_values( $caps ) );
			sort( $caps );
			// debug($caps);
			foreach ( $caps as $basecap => $cap ) {
				$basecap = str_replace( '_' . $data['plural'], '_' . $data['capability_type'] . 's', $cap );
				$basecap = str_replace( '_' . $data['singular'], '_' . $data['capability_type'], $basecap );
				// debug($basecap);
				$sel = isset( $role['capabilities'][ $basecap ] ) ? "checked='checked'" : '';
				if ( isset( $role['posttype_caps'][ $pt ] ) ) {
					// debug($role["posttype_caps"][$pt][$basecap]);
					if ( ! isset( $role['posttype_caps'][ $pt ][ $basecap ] ) || $role['posttype_caps'][ $pt ][ $basecap ] == false ) {
						$sel = '';
					} else {
						$sel = "checked='checked'";
					}
				}
				$content .= "<li data-tab='" . $pt . "'>
								<label for='role_capability_" . $cap . "' class='flex'>" . $cap . "
									<input type='checkbox' id='role_capability_" . $cap . "' name='role_settings[posttype_caps][" . $pt . '][' . $basecap . "]' " . $sel . " autocomplete='off'>
								</label>
							</li>";
			}
		}

		// render
		echo '<table class="greyd_table vertical meta_table tabs_table">';

			echo '<tr id="settings_capabilities">
					<th><ul>' . $menu . '</ul></th>
					<td><div class="items_ul"><ul>' . $content . '</ul></div></td>
				</tr>';

		if ( $args['view'] != 'locked' ) {

			echo '<tr id="settings_capabilities_foot">
					<th></th>
					<td><div class="li flex" style="align-items:center">
						<span></span>
						<span class="toggle_all">
							<span class="unmark_all">' . __( 'Unmark all', 'greyd_hub' ) . '</span> | <span class="mark_all">' . __( 'Mark all', 'greyd_hub' ) . '</span>
						</span>
					</div></td>
				</tr>';

		}

		echo '</table>';

	}

	/**
	 * Render the blocks capabilities/visibility metabox.
	 *
	 * @param array $args
	 *      @property mixed role    The role object with all settings.
	 *      @property string view   Indication for editability: '' or 'locked'
	 */
	public function render_block_caps_box( $args ) {

		$role = $args['role'];
		// debug($role);

		$groups  = array();
		$menu    = '<li data-tab="all" class="active"><big>All</big></li>';
		$content = '';

		foreach ( Manage::get_blocks() as $block_type ) {
			// debug($block_type->supports);
			$name  = $block_type->name;
			$title = $block_type->title;
			if ( $title != '' ) {
				$title = '(' . $title . ')';
			}
			$category = 'core ' . $block_type->category;
			if ( strpos( $name, 'core/' ) === false ) {
				$category = explode( '/', $name )[0];
			}
			// menu item
			array_push( $groups, $category );
			// contents
			$locked = '';
			$sel    = "checked='checked'";
			if ( isset( $block_type->supports['inserter'] ) && $block_type->supports['inserter'] === false ) {
				$locked = "class='locked'";
				$sel    = '';
			} elseif ( isset( $role['block_caps'][ $name ] ) ) {
				// debug($role["block_caps"][$name]);
				$sel = $role['block_caps'][ $name ] == true ? "checked='checked'" : '';
			}
			$content .= "<li data-tab='" . $category . "' " . $locked . ">
							<label for='role_capability_" . $name . "' class='flex'>" . $name . ' ' . $title . "
								<input type='checkbox' id='role_capability_" . $name . "' name='role_settings[block_caps][" . $name . "]' " . $sel . " autocomplete='off'>
							</label>
						</li>";
		}
		$groups = array_unique( $groups );
		foreach ( $groups as $category ) {
			$menu .= '<li data-tab="' . $category . '"><big>' . $category . '</big></li>';
		}

		// render
		echo '<table class="greyd_table vertical meta_table tabs_table">';

			echo '<tr id="block_capabilities">
					<th><ul>' . $menu . '</ul></th>
					<td><div class="items_ul"><ul>' . $content . '</ul></div></td>
				</tr>';

		if ( $args['view'] != 'locked' ) {

			echo '<tr id="block_capabilities_foot">
					<th></th>
					<td><div class="li flex" style="align-items:center">
						<span></span>
						<span class="toggle_all">
							<span class="unmark_all">' . __( 'Unmark all', 'greyd_hub' ) . '</span> | <span class="mark_all">' . __( 'Mark all', 'greyd_hub' ) . '</span>
						</span>
					</div></td>
				</tr>';

		}

		echo '</table>';

	}

	/**
	 * Render the additional user metafields metabox.
	 *
	 * @param array $args
	 *      @property mixed role    The role object with all settings.
	 *      @property string view   Indication for editability: '' or 'locked'
	 */
	public function render_metafields_box( $args ) {

		$metafields_table = new Metafields_Table();
		$metafields_table->render(
			array(
				'name'   => 'role_settings[fields]',
				'fields' => $args['role']['fields'],
				'locked' => $args['view'] == 'locked',
			// todo: extra options (position and visibility)
			)
		);

	}

	/*
	=======================================================================
		handle data
	=======================================================================
	*/

	/**
	 * handle POST Data for edit or create role.
	 *
	 * @param string $action    edit or create.
	 * @param object $role      the role object.
	 * @param array  $post_data  Raw $_POST data.
	 * @return string|bool      The role slug or false on failure.
	 */
	public function save_data( $action, $role, $post_data ) {

		if ( $action == 'edit' || $action == 'create' ) {
			if ( isset( $post_data['submit'] ) && isset( $post_data['role_settings'] ) ) {
				$nonce = isset( $post_data['_wpnonce'] ) ? wp_unslash( $post_data['_wpnonce'] ) : null;
				if ( $nonce && wp_verify_nonce( $nonce, 'greyd_users_edit_role' ) ) {
					// debug("save role:");
					// debug($post_data);
					$role_args = $post_data['role_settings'];
					array_pop( $role_args['fields'] );
					if ( ! empty( $role_args['title'] ) && ! empty( $role_args['slug'] ) ) {
						if ( $action == 'create' ) {
							return Manage::create_role( $role_args );
						} elseif ( $action == 'edit' ) {
							return Manage::update_role( $role['slug'], $role_args );
						}
					}
				}
			}
		}
		return false;

	}

	/**
	 * Process bulk- and row-actions.
	 * handle POST/GET Data for clone, delete or default role.
	 *
	 * @param string $action        clone, delete or make_default.
	 * @param array  $request_data   Raw $_REQUEST data.
	 * @return void
	 */
	public function process_bulk_action( $action, $request_data ) {

		if ( $action == 'clone' || $action == 'make_default' || $action == 'delete' ) {
			/*
			 * the nonce field is set by the parent class
			 * wp_nonce_field( 'bulk-'.$this->_args['plural'] );
			 */
			$nonce = isset( $request_data['_wpnonce'] ) ? wp_unslash( $request_data['_wpnonce'] ) : null;
			if ( $nonce && wp_verify_nonce( $nonce, 'bulk-roles_table' ) ) {
				// debug($request_data);
				// clone
				if ( $action == 'clone' && isset( $request_data['role'] ) ) {
					$slug = $request_data['role'];
					// debug("duplicate ".$slug);
					$response = Manage::duplicate_role( $slug );
					if ( $response ) {
						$role = Manage::get_role( $response );
						echo Helper::show_message( sprintf( __( 'Duplicate role "%s" created.', 'greyd_hub' ), $role['title'] ), 'success inline' );
					} else {
						$role = Manage::get_role( $slug );
						echo Helper::show_message( sprintf( __( 'Unable to duplicate role "%s".', 'greyd_hub' ), $role['title'] ), 'error inline' );
					}
				}
				// make_default
				if ( $action == 'make_default' && isset( $request_data['role'] ) ) {
					$slug = $request_data['role'];
					// debug("make_default ".$slug);
					$response     = false;
					$default_role = get_option( 'default_role' );
					if ( $slug != $default_role ) {
						$response = update_option( 'default_role', $slug );
					}
					$role = Manage::get_role( $slug );
					if ( $response ) {
						echo Helper::show_message( sprintf( __( '"%s" is now the default role.', 'greyd_hub' ), $role['title'] ), 'success inline' );
					} else {
						echo Helper::show_message( sprintf( __( 'Unable to make "%s" the default role.', 'greyd_hub' ), $role['title'] ), 'error inline' );
					}
				}
				// delete
				if ( $action == 'delete' && isset( $request_data['roles'] ) ) {
					foreach ( $request_data['roles'] as $i => $slug ) {
						// debug("delete ".$slug);
						$role    = Manage::get_role( $slug );
						$newslug = '';
						if ( $role['users'] > 0 ) {
							if ( isset( $request_data['newroles'][ $i ] ) ) {
								// debug($request_data['newroles'][$i]);
								$newslug = $request_data['newroles'][ $i ];
							} else {
								echo Helper::show_message( sprintf( __( 'Unable to delete role "%1$s", %2$s users have this role.', 'greyd_hub' ), $role['title'], $role['users'] ), 'error inline' );
								continue;
							}
						}
						$response = Manage::delete_role( $slug, $newslug );
						if ( $response ) {
							echo Helper::show_message( sprintf( __( 'Role "%s" deleted.', 'greyd_hub' ), $role['title'] ), 'success inline' );
						} else {
							echo Helper::show_message( sprintf( __( 'Unable to delete role "%s".', 'greyd_hub' ), $role['title'] ), 'error inline' );
						}
					}
				}
			}

			if ( isset( $_GET['action'] ) ) {
				$url = remove_query_arg( array( 'action', 'roles', 'newroles', 'role', '_wpnonce' ) );
				echo '<script> window.history.pushState({}, "Hide", "' . $url . '"); </script>';
			}
		}

	}
}

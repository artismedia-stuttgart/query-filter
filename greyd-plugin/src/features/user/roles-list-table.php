<?php
/**
 * Displays all roles as WP-Admin list table
 *
 * @since 1.5.4
 * @see wp-admin/includes/class-wp-list-table.php
 */
namespace Greyd\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Roles_List_Table extends \WP_List_Table {

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'roles_table', // table class
				'plural'   => 'roles_table', // table class
				'ajax'     => false,
			)
		);
	}


	/**
	 * =================================================================
	 *                          GENERAL
	 * =================================================================
	 */

	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @param bool $user_can_edit   Indication for editability of the items
	 */
	public function prepare_items( $user_can_edit = false ) {

		// process bulk action on roles page
		// $this->process_bulk_action();

		// init items
		$all_roles = array();

		// search
		$search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
		foreach ( Manage::get_roles() as $role ) {
			if ( $search_key == '' ||
				strpos( $role['slug'], strtolower( $search_key ) ) !== false ||
				strpos( $role['title'], $search_key ) !== false ) {
				// adjust visibility
				$role['view'] = 'edit';
				if ( $role['slug'] == 'super' ) {
					$role['view'] = 'view';
				}
				if ( $role['slug'] == 'administrator' ) {
					$role['view'] = 'no_delete';
				}
				if ( ! $user_can_edit ) {
					$role['view'] = 'view';
				}
				// add role
				array_push( $all_roles, $role );
			}
		}

		// sort
		usort(
			$all_roles,
			function( $a, $b ) {
				// Set defaults
				$orderby = 'caps';
				$order   = 'desc';
				// If orderby is set, use this as the sort column
				if ( ! empty( $_GET['orderby'] ) ) {
					$orderby = $_GET['orderby'];
				}
				// If order is set use this as the order
				if ( ! empty( $_GET['order'] ) ) {
					$order = $_GET['order'];
				}
				// compare
				if ( $orderby === 'caps' ) {
					if ( count( $a['capabilities'] ) != count( $b['capabilities'] ) ) {
						$result = count( $a['capabilities'] ) - count( $b['capabilities'] );
					} else {
						$result = strcmp( $b['title'], $a['title'] );
					}
				} else {
					$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
				}
				// return
				if ( $order === 'asc' ) {
					return $result;
				}
				return -$result;
			}
		);

		// columns
		$this->_column_headers = array(
			$this->get_columns(), // columns
			array(), // hidden columns
			$this->get_sortable_columns(), // sortable columns
		);
		// no pagination
		$this->set_pagination_args(
			array(
				'total_items' => count( $all_roles ),
				'per_page'    => count( $all_roles ),
			)
		);

		// set items
		$this->items = $all_roles;
		// debug($this->items);
	}

	/**
	 * Render the table.
	 * Before calling, make sure to call prepare_items function.
	 */
	public function render_table() {

		echo '<form id="roles-form" method="post">';

		$this->search_box( __( 'Find role', 'greyd_hub' ), 'roles' );
		$this->display();

		echo '</form>';
	}

	/**
	 * Display text when no items found
	 */
	public function no_items() {
		echo "<div style='margin: 4px 0;''><strong>" . __( 'No roles found.', 'greyd_hub' ) . '</strong></div>';
	}


	/**
	 * =================================================================
	 *                          COLUMNS
	 * =================================================================
	 */

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Title', 'greyd_hub' ),
			'slug'  => __( 'Slug', 'greyd_hub' ),
			'caps'  => __( 'Capabilities', 'greyd_hub' ),
			'users' => __( 'Users', 'greyd_hub' ),
			// 'debug'          => 'Debug',
		);

		return $columns;
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array(
			'caps'  => array( 'caps', true ),
			'title' => array( 'title', false ),
			'slug'  => array( 'slug', false ),
		);
	}

	/**
	 * Render a column when no column specific method exist.
	 * All columns are rendered here, only 'title' has row-actions.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'title':
				$default_role = get_option( 'default_role' );
				// view url
				$query_args_view = array(
					'page'   => wp_unslash( $_REQUEST['page'] ),
					'tab'    => 'roles',
					'action' => 'view',
					'role'   => $item['slug'],
				);
				$view_url        = remove_query_arg( array( 'action', 'roles', '_wpnonce' ) );
				$view_url        = esc_url( add_query_arg( $query_args_view, $view_url ) );
				// edit url
				$query_args_edit = array(
					'page'   => wp_unslash( $_REQUEST['page'] ),
					'tab'    => 'roles',
					'action' => 'edit',
					'role'   => $item['slug'],
				);
				$edit_url        = remove_query_arg( array( 'action', 'roles', '_wpnonce' ) );
				$edit_url        = esc_url( add_query_arg( $query_args_edit, $edit_url ) );
				// clone url
				$query_args_clone = array(
					'page'     => wp_unslash( $_REQUEST['page'] ),
					'tab'      => 'roles',
					'action'   => 'clone',
					'role'     => $item['slug'],
					'_wpnonce' => wp_create_nonce( 'bulk-roles_table' ),
				);
				$clone_url        = remove_query_arg( array( 'action', 'roles', '_wpnonce' ) );
				$clone_url        = esc_url( add_query_arg( $query_args_clone, $clone_url ) );
				// make default url
				$query_args_make_default = array(
					'page'     => wp_unslash( $_REQUEST['page'] ),
					'tab'      => 'roles',
					'action'   => 'make_default',
					'role'     => $item['slug'],
					'_wpnonce' => wp_create_nonce( 'bulk-roles_table' ),
				);
				$make_default_url        = remove_query_arg( array( 'action', 'roles', '_wpnonce' ) );
				$make_default_url        = esc_url( add_query_arg( $query_args_make_default, $make_default_url ) );
				// delete url (bulk)
				$query_args_delete = array(
					'page'     => wp_unslash( $_REQUEST['page'] ),
					'tab'      => 'roles',
					'action'   => 'delete',
					'roles'    => array( $item['slug'] ),
					'_wpnonce' => wp_create_nonce( 'bulk-roles_table' ),
				);
				$delete_url        = remove_query_arg( array( 'action', 'role' ) );
				$delete_url        = esc_url( add_query_arg( $query_args_delete, $delete_url ) );
				// actions
				$title   = $item['title'];
				$actions = array();
				if ( $item['view'] == 'view' ) {
					$actions['edit'] = "<a href='" . $view_url . "'>" . __( 'View', 'greyd_hub' ) . '</a>';
				} else {
					$title            = "<a href='" . $edit_url . "'>" . $title . '</a>';
					$actions['edit']  = "<a href='" . $edit_url . "'>" . __( 'Edit', 'greyd_hub' ) . '</a>';
					$actions['clone'] = "<a href='" . $clone_url . "'>" . __( 'Duplicate', 'greyd_hub' ) . '</a>';
					if ( $item['view'] != 'no_delete' ) {
						$data              = "data-role='" . $item['slug'] . "'";
						$data             .= " data-usercount='" . $item['users'] . "'";
						$actions['delete'] = "<a href='" . $delete_url . "' " . $data . '>' . __( 'Delete', 'greyd_hub' ) . '</a>';
					}
					if ( $item['slug'] != $default_role ) {
						$actions['make_default'] = "<a href='" . $make_default_url . "'>" . __( 'Make default', 'greyd_hub' ) . '</a>';
					}
				}
				$is_default = $item['slug'] == $default_role ? ' (' . __( 'Default user role', 'greyd_hub' ) . ')' : '';
				return '<strong>' . $title . '</strong>' . $is_default . $this->row_actions( $actions );
				break;

			case 'caps':
				return count( $item['capabilities'] );
				break;

			case 'debug':
				return debug( $item );
				break;

			default:
				return esc_attr( $item[ $column_name ] );
		}
	}

	/**
	 * =================================================================
	 *                          BULK ACTIONS
	 * =================================================================
	 */

	/**
	 * Render the bulk edit checkbox
	 */
	function column_cb( $item ) {

		// no checkbox
		if ( $item['view'] == 'view' || $item['view'] == 'no_delete' ) {
			return;
		}

		return sprintf(
			'<input type="checkbox" name="roles[]" value="%s" />',
			$item['slug']
		);
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array();
		if ( current_user_can( 'edit_users' ) ) {
			$actions['delete'] = __( 'Delete role', 'greyd_hub' );
		}

		return $actions;
	}

}

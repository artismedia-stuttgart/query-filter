<?php

/**
 * Displays all connections as WP-Admin list table
 * 
 * @see wp-admin/includes/class-wp-list-table.php
 */
namespace Greyd\Connections;

use Greyd\Helper as Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

// include the parent class
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH.'wp-admin/includes/class-wp-list-table.php' );
}

class Connections_List_Table extends \WP_List_Table {

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'gc_connection_table', // table class
			'plural'   => 'gc_connection_table', // table class
			'ajax'     => false
		] );
	}
	
	
	/**
	 * =================================================================
	 *                          GENERAL
	 * =================================================================
	 */

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		// process bulk action
		$this->process_bulk_action();

		// general
		$this->_column_headers = array($this->get_columns());
		$this->set_pagination_args( [
			'total_items' => 100,
			'per_page'    => 100
		] );

		// set items
		$this->items = Connections_Helper::get_connections();
		// debug($this->items);
	}

	/**
	 * Render the table
	 */
	public function render_table() {

		$this->prepare_items();

		echo '<form id="posts-filter" method="post" style="margin-top: 1em;">';

		$this->display();

		echo '</form>';
	}
	
	/**
	 * Display text when no items found
	 */
	public function no_items() {
		echo "<div style='margin: 4px 0;''><strong>".__( "No connections found.", 'greyd_hub' )."</strong> ".__( "Create your first shortcuts now to manage content across pages.", 'greyd_hub' )."</div>";
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		return;
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
		$columns = [
			'cb'			=> '<input type="checkbox" />',
			'site_name'		=> __( "Title", 'greyd_hub' ),
			'site_url'		=> __( 'URL', 'greyd_hub' ),
			'user_login'	=> __( "User", 'greyd_hub' ),
			'active'		=> __( "State", 'greyd_hub' ),
			// 'password'		=> __( "Password", 'greyd_hub' ),
			'options'		=> __( "Settings", 'greyd_hub' ),
			// 'debug'			=> 'Debug',
		];
		
		return $columns;
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		
		switch ( $column_name ) {

			case 'site_name':
				$site_url  = isset($item['site_url']) ? esc_attr($item['site_url']) : null;
				$site_name = isset($item['site_name']) ? esc_attr($item['site_name']) : null;
				if ( !$site_name ) {
					$site_name = Connections_Helper::get_site_name($site_url) ?? $site_url;
				}
				$network_url = Connections_Helper::get_nice_url( $site_url );
				$delete_url  = remove_query_arg( array('user_login','password','site_url','success'), add_query_arg( "delete", $network_url ) );
				$actions = array(
					'view'		=> "<a href='$site_url' target='_blank'>".__("View website", 'greyd_hub')."</a>",
					'delete'	=> "<a href='$delete_url'>".__("Delete connection", 'greyd_hub')."</a>",
				);
				return $site_name.$this->row_actions( $actions );
				break;

			case 'site_url':
				$site_url = isset($item['site_url']) ? esc_attr($item['site_url']) : null;
				return "<a href='".$site_url."' target='_blank'>".$site_url."</a>";
				break;

			case 'active':
				$unused = isset($item['contents']) && $item['contents'] === false && isset($item['search']) && $item['search'] === false;
				if ( $unused ) {
					return Helper::render_info_box([
						"style" => "",
						"text" => __("Connection not used", 'greyd_hub')
					]);
				} else {
					$active = isset($item['active']) ? (bool) $item['active'] : false;
					return Helper::render_info_box([
						"style" => $active ? 'green' : 'red',
						"text" => $active ? __("Connection active", 'greyd_hub') : __("Connection inactive", 'greyd_hub')
					]);
				}
				break;

			case 'options':

				$site_url         = isset($item['site_url']) ? Connections_Helper::get_nice_url( $item['site_url']) : null;
				$contents_checked = !isset($item['contents']) || $item['contents'] ? "checked" : "";
				$search_checked   = !isset($item['search']) || $item['search'] ? "checked" : "";

				if ( class_exists("GC_Post") ) 
					return "<div class='gc_connection_options' data-site_url='$site_url'>
						<label>
							<input type='checkbox' name='contents' value='contents' $contents_checked>
							".__("Use for Global Content", 'greyd_hub')."
						</label>
						<label>
							<input type='checkbox' name='search' value='search' $search_checked>
							".__("Use for Global Search", 'greyd_hub')."
						</label>
						<span
							class='button small save_connection_options hidden'
							data-text='".__("Save", 'greyd_hub')."'
							data-text_loading='".__("Saving...", 'greyd_hub')."'
							data-text_success='".__("✓ Saved", 'greyd_hub')."'
							data-text_fail='".__("Not saved", 'greyd_hub')."'
						></span>
					</div>";
				else return "";
				break;

			case 'debug':
				return debug( $item );
				break;
				
			default:
				return esc_attr( $item[$column_name] );
		}
	}
	
	/**
	 * Render the bulk edit checkbox
	 */
	function column_cb( $item ) {

		if ( !isset($item['site_url']) ) {
			return;
		}

		return sprintf(
			'<input type="checkbox" name="connections[]" value="%s" />', $item['site_url']
		);
	}
	
	
	/**
	 * =================================================================
	 *                          BULK ACTIONS
	 * =================================================================
	 */
	
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		
		$actions = [
			'delete' => __("Delete connection", 'greyd_hub'),
		];

		return $actions;
	}

	/**
	 * Process the bulk actions
	 * Called via prepare_items()
	 */
	public function process_bulk_action() {

		// delete GET request
		if ( isset($_GET['delete']) ) {
			$delete_site_url = urldecode($_GET['delete']);
			$deleted = Connections_Helper::delete_connection($delete_site_url);
			// successfull
			if ($deleted) {
				Helper::show_message( sprintf(
					__("The connection to the %s page was successfully deleted.", 'greyd_hub'),
					"<strong>".$delete_site_url."</strong>"
				), "success" );
			}
			// failed
			else if ($deleted === false) {
				Helper::show_message( sprintf(
					__("Errors occurred while deleting the connection to the '%s' page.", 'greyd_hub'),
					"<strong>".$delete_site_url."</strong>"
				), "error" );
			}
		}
	}
}
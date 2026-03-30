<?php
/**
 * Main Admin page to manage Headless Features.
 */
namespace Greyd\Headless;

use Greyd\Helper as Helper;
use Greyd\Posttypes\Posttype_Helper as Posttype_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Api_Page( $config );
class Api_Page {

	/**
	 * Holds the plugin config.
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Standalone mode.
	 *
	 * @var bool
	 */
	public static $is_standalone = false;

	/**
	 * Hold the feature page config
	 * slug, title, url, cap, callback
	 */
	public static $page = array();

	/**
	 * Class constructor.
	 */
	public function __construct( $config ) {

		// if ( !Manage::wp_up_to_date() ) {
		// return;
		// }

		if ( ! is_admin() ) {
			return;
		}

		// set config
		$this->config = (object) $config;
		$this->config->css_uri        = plugin_dir_url( __FILE__ ) . 'assets/css';
		$this->config->js_uri         = plugin_dir_url( __FILE__ ) . 'assets/js';

		// standalone mode
		if ( isset( $this->config->is_standalone ) && $this->config->is_standalone == true ) {
			self::$is_standalone = true;
		}

		// define page details
		add_action(
			'init',
			function() {
				self::$page = array(
					'slug'     => 'greyd_headless',
					'title'    => __( 'Headless', 'greyd_hub' ),
					'url'      => is_network_admin() ? network_admin_url( 'admin.php?page=greyd_headless' ) : admin_url( 'admin.php?page=greyd_headless' ),
					'cap'      => 'manage_options',
					'callback' => array( $this, 'render_page' ),
				);
				// debug(self::$page);
			},
			0
		);

		// greyd backend
		if ( self::$is_standalone ) {
			// standalone mode
			add_action( 'admin_menu', array( $this, 'standalone_submenu' ), 40 );
		} else {
			// in hub
			add_filter( 'greyd_submenu_pages', array( $this, 'add_greyd_submenu_page' ) );
			add_filter( 'greyd_dashboard_tabs', array( $this, 'add_greyd_dashboard_tab' ) );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ) );

	}

	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * add scripts
	 */
	public function load_backend_scripts() {

		// Scripts
		wp_register_script(
			$this->config->plugin_name . '_headless_js',
			$this->config->js_uri . '/backend-headless.js',
			null,
			GREYD_VERSION,
			true
		);
		wp_enqueue_script( $this->config->plugin_name . '_headless_js' );
		// inline script before
		// define global greyd var
		wp_add_inline_script( $this->config->plugin_name . '_headless_js', 'var greyd = greyd || {}; greyd.rest_base = "' . esc_url( get_rest_url( null, '/' ) ) . '"', 'before' );

		// load only on this page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::$page['slug'] ) {
			return;
		}

		// Styles
		wp_register_style(
			$this->config->plugin_name . '_headless_css',
			$this->config->css_uri . '/backend-headless.css',
			null,
			GREYD_VERSION,
			'all'
		);
		wp_enqueue_style( $this->config->plugin_name . '_headless_css' );

	}

	/**
	 * Add a standalone submenu item to general settings if Hub is not installed
	 */
	public function standalone_submenu() {
		add_submenu_page(
			'options-general.php', // parent slug
			self::$page['title'],  // page title
			self::$page['title'], // menu title
			self::$page['cap'], // capability
			self::$page['slug'], // slug
			self::$page['callback'], // function
			80 // position
		);
	}

	/**
	 * Add the submenu item to Greyd.Suite if Hub is installed
	 *
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page( $submenu_pages ) {
		// debug($submenu_pages);

		array_push(
			$submenu_pages,
			array(
				'page_title' => __( 'Greyd.Suite', 'greyd_hub' ) . ' ' . self::$page['title'],
				'menu_title' => self::$page['title'],
				'cap'        => self::$page['cap'],
				'slug'       => self::$page['slug'],
				'callback'   => self::$page['callback'],
				'position'   => 50,
			)
		);

		// debug( self::$page );

		return $submenu_pages;
	}

	/**
	 * Add dashboard tab if Hub is installed
	 *
	 * @see filter 'greyd_dashboard_tabs'
	 */
	public function add_greyd_dashboard_tab( $tabs ) {
		// debug($tabs);

		$tabs[ self::$page['slug'] ] = array(
			'title'    => self::$page['title'],
			'slug'     => self::$page['slug'],
			'url'      => self::$page['url'],
			'cap'      => self::$page['cap'],
			'priority' => 50,
		);

		return $tabs;
	}


	/*
	=======================================================================
		render
	=======================================================================
	*/

	/**
	 * Render the page
	 */
	public function render_page() {

		if ( ! is_admin() ) {
			return;
		}

		echo "<div class='wrap settings_wrap'>";

			// title
			// echo "<h1 class='wp-heading-inline'>".__( 'APIs', 'greyd_hub' ).'</h1>';
			echo "<h1 class=''>" . __( 'APIs', 'greyd_hub' ) . '</h1>';

			// global $wp_post_types;
			// global $wp_taxonomies;
			// // debug(array_keys($wp_post_types));
			// // debug(array_keys($wp_taxonomies));

			// // debug($wp_post_types);
			// foreach ($wp_post_types as $posttype => $args) {
			// debug($posttype);
			// debug($args->show_in_rest);
			// }

			// $posttypes = Posttype_Helper::get_dynamic_posttypes();
			// foreach ($posttypes as $posttype) {
			// debug($posttype['slug']);
			// }

			$rest_base = esc_url( get_rest_url( null, '/' ) );
			// $rest_base = esc_url( get_rest_url() );
			// debug($rest_base);

			$settings = Admin::get_settings( 'api' );
			// $settings = self::get_defaults()['api'];
			// debug($settings);

			echo "<div class='headless_wrap'>";

				// apis panel
				echo "<div class='apis_wrap'>";

					// my api
					echo "<div class='api_route'>
							<h4 class='api_head'>
								<span class='api_headline'>Home (my api)</span>
								<span>
									" . Helper::render_dashicon( "admin-tools api_edit' title='Edit my API" ) . "
								</span>
							</h4>
							<div class='api_route_edit hidden'>
								<h4>basic</h4>
								<pre>" . print_r( $settings['basic'], true ) . '</pre>
								<h4>posttypes</h4>
								<pre>' . print_r( $settings['posttypes'], true ) . '</pre>
								<h4>taxonomies</h4>
								<pre>' . print_r( $settings['taxonomies'], true ) . '</pre>
								<h4>endpoints</h4>
								<pre>' . print_r( $settings['endpoints'], true ) . "</pre>
							</div>
							<div class='api_route_input'>
								<input type='text' class='api_url' value='" . $rest_base . "' disabled>
								<input type='button' class='button api_call' value='Call'>
							</div>
						</div>";

					// all apis in sortable container
					echo "<div class='api_sortable'>";

		if ( isset( $settings['apis'] ) && is_array( $settings['apis'] ) ) {

			foreach ( $settings['apis'] as $api ) {
				// debug($api);
				echo $this->render_api(
					$api,
					array(
						'class'     => 'api_route',
						'draggable' => 'true',
					)
				);
			}
		}

					echo '</div>';

					// new api
					echo "<div class='api_route'>
							<h4 class='api_head'>
								<span class='api_headline'>Create API</span>
								<span>
									" . Helper::render_dashicon( "plus api_add' title='Add API" ) . "
								</span>
							</h4>
							<div class='api_route_input'>
								<input type='text' class='api_url' value=''>
								<input type='button' class='button api_call' value='Test'>
							</div>
						</div>";

					// dummies
					echo "<div class='dummies hidden'>";
						echo $this->render_api(
							array(),
							array(
								'class' => 'api_dummy',
							)
						);
						echo $this->render_api_item(
							array(
								'class'     => 'api_route_attribute_dummy',
								'del_title' => 'Delete URL Attribute',
							)
						);
						echo $this->render_api_item(
							array(
								'class'     => 'api_route_header_dummy',
								'del_title' => 'Delete Header',
							)
						);
						echo $this->render_route(
							array(),
							array(),
							array(
								'class' => 'api_route_dummy',
							)
						);
						echo $this->render_block(
							array(),
							array(),
							array(
								'class' => 'api_block_dummy',
							)
						);
						echo $this->render_block_data_item(
							array(),
							array(
								'class' => 'api_block_data_item_dummy',
							)
						);
						echo $this->render_block_data_item_action(
							array(),
							array(
								'class' => 'api_block_data_item_action_dummy',
							)
						);
					echo '</div>';

				echo '</div>'; // end of apis_wrap

				echo "<div class='split'></div>";

				// terminal panel
				echo "<div class='console_wrap'>";

					echo "<div id='apiLoader' class='hidden'><span class='loading'><span class='loader'></span></span></div>";
					echo "<pre id='apiResult'>></pre>";

				echo '</div>'; // end of console_wrap

			echo '</div>'; // end of headless_wrap

		echo '</div>'; // end of settings_wrap
	}

	public function render_api( $api, $wrapper_atts ) {

		// debug($api);
		$api = wp_parse_args(
			$api,
			array(
				'title'       => '',
				'slug'        => '',
				'description' => '',
				'base_url'    => '',
				'url_path'    => '',
			// 'url_atts' => '',
			// 'headers' => '',
			// 'routes' => '',
			// 'blocks' => '',
			// 'posttypes' => ''
			)
		);

		// wrapper atts (class, etc)
		$html_atts = '';
		if ( isset( $wrapper_atts ) && ! empty( $wrapper_atts ) && is_array( $wrapper_atts ) ) {
			$atts = array();
			foreach ( $wrapper_atts as $key => $value ) {
				$atts[] = $key . "='" . $value . "'";
			}
			$html_atts = implode( ' ', $atts );
		}

		return '<div ' . $html_atts . ">
					<h3 class='api_head'>
						<span class='api_headline'>" . $api['title'] . "</span>
						<span class='api_buttons hidden'>
							" . Helper::render_dashicon( "trash api_delete' title='Delete API" ) . '
							' . Helper::render_dashicon( "controls-play api_call' title='Call API Route" ) . '
							' . Helper::render_dashicon( "admin-tools api_edit' title='API Configuration" ) . '
							' . Helper::render_dashicon( "admin-generic api_routes' title='API Routes" ) . '
							' . Helper::render_dashicon( "rest-api api_setup' title='API Setup" ) . '
						</span>
					</h3>' .
					// "<div class='api_route_input'>
					// <input type='text' class='api_url' value='".$this->render_url( $api )."' autocomplete='off' disabled>
					// <input type='button' class='button api_call' value='Call'>
					// </div>".
					"<div class='api_body api_route_edit hidden' data-route='" . $api['slug'] . "'>
						<h4>API Configuration " . Helper::render_dashicon( 'admin-tools txt' ) . '</h4>' .
						$this->render_basic( $api ) .
					"</div>
					<div class='api_body api_route_routes hidden'>
						<h4>API Routes " . Helper::render_dashicon( 'admin-generic txt' ) . '</h4>' .
						$this->render_routes( $api ) . "
					</div>
					<div class='api_body api_route_setup hidden'>
						<h4>API Setup " . Helper::render_dashicon( 'rest-api txt' ) . '</h4>
						<h4>Blocks</h4>' .
						$this->render_blocks( $api ) .
						'<h4>Posttypes</h4>' .
						$this->render_posttypes( $api ) .
					'</div>
				</div>';
	}

	public function render_basic( $api ) {

		$api = wp_parse_args(
			$api,
			array(
				'title'       => '',
				'slug'        => '',
				'description' => '',
				'base_url'    => '',
				'url_path'    => '',
				'method'      => 'GET',
			// 'url_atts' => '',
			// 'headers' => '',
			// 'routes' => '',
			// 'blocks' => '',
			// 'posttypes' => ''
			)
		);

		$title    =
			"<label>Title</label>
			<input type='text' class='api_input api_title' value='" . $api['title'] . "' autocomplete='off'>
			<input type='text' class='api_input api_slug' value='" . $api['slug'] . "' autocomplete='off' disabled>";
		$info     =
			"<label>Info</label>
			<input type='text' class='api_input api_description' value='" . $api['description'] . "' autocomplete='off'>";
		$base_url =
			"<label>Base URL</label>
			<input type='text' class='api_input api_base_url' value='" . $api['base_url'] . "' autocomplete='off' " . ( isset( $api['subroute'] ) ? 'disabled' : '' ) . '>';
		$path     =
			"<label>URL Path</label>
			<input type='text' class='api_input api_url_path' value='" . $api['url_path'] . "' autocomplete='off'>";
		$method   =
			"<label>Method</label>
			<input type='text' class='api_input api_method' value='" . $api['method'] . "' autocomplete='off'>";

		$attributes = $this->render_attributes( $api );
		$headers    = $this->render_headers( $api );

		$call =
			"<input type='text' class='api_url' value='" . $this->render_url( $api ) . "' autocomplete='off' disabled>
			<input type='button' class='button api_call' value='Call'>";

		$items = array(
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $title,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $info,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $base_url,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $path,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $method,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $attributes,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $headers,
				)
			),
			$this->render_api_body_item(
				array(
					'class'   => isset( $api['subroute'] ) ? 'hidden' : '',
					'content' => $call,
				)
			)
		);
		return implode( '', $items );
	}

	public function render_attributes( $api ) {

		$attributes = '';
		if ( isset( $api['url_atts'] ) && ! empty( $api['url_atts'] ) && is_array( $api['url_atts'] ) ) {
			foreach ( $api['url_atts'] as $key => $value ) {
				$attributes .= $this->render_api_item(
					array(
						'class'     => 'api_route_attribute',
						'key'       => $key,
						'value'     => $value,
						'del_title' => 'Delete URL Attribute',
					)
				);
			}
		}
		return "<label>URL Atts</label>
				<div class='api_items api_route_attributes'>
					" . $attributes . "
					<div class='api_item'>
						" . Helper::render_dashicon( "plus api_item_add' data-dummy='api_route_attribute_dummy' title='Add URL Attribute" ) . '
					</div>
				</div>';
	}

	public function render_headers( $api ) {

		$headers = '';
		if ( isset( $api['headers'] ) && ! empty( $api['headers'] ) && is_array( $api['headers'] ) ) {
			foreach ( $api['headers'] as $key => $value ) {
				$headers .= $this->render_api_item(
					array(
						'class'     => 'api_route_header',
						'key'       => $key,
						'value'     => $value,
						'del_title' => 'Delete Header',
					)
				);
			}
		}
		return "<label>Headers</label>
				<div class='api_items api_route_headers'>
					" . $headers . "
					<div class='api_item'>
						" . Helper::render_dashicon( "plus api_item_add' data-dummy='api_route_header_dummy' title='Add Header" ) . '
					</div>
				</div>';
	}


	public function render_url( $api ) {

		// base url
		$url = $api['base_url'];
		if ( isset( $api['url_path'] ) && ! empty( $api['url_path'] ) ) {
			$url .= '/' . $api['url_path'];
		}

		// url attributes
		if ( isset( $api['url_atts'] ) && ! empty( $api['url_atts'] ) && is_array( $api['url_atts'] ) ) {
			$url_attributes = array();
			foreach ( $api['url_atts'] as $key => $value ) {
				$url_attributes[] = $key . '=' . $value;
			}
			$url .= '?' . implode( '&', $url_attributes );
		}

		return $url;
	}

	// routes

	public function merge_routes( $api, $route ) {

		$api = wp_parse_args(
			$api,
			array(
				'base_url' => '',
				'url_atts' => array(),
				'headers'  => array(),
			)
		);
		if ( ! is_array( $api['url_atts'] ) ) {
			$api['url_atts'] = array();
		}
		if ( ! is_array( $api['headers'] ) ) {
			$api['headers'] = array();
		}

		$route = wp_parse_args(
			$route,
			array(
				'base_url' => $api['base_url'],
				'url_atts' => array(),
				'headers'  => array(),
			)
		);
		if ( ! is_array( $route['url_atts'] ) ) {
			$route['url_atts'] = array();
		}
		if ( ! is_array( $route['headers'] ) ) {
			$route['headers'] = array();
		}

		$route['url_atts'] = array_merge( $api['url_atts'], $route['url_atts'] );
		$route['headers']  = array_merge( $api['headers'], $route['headers'] );

		return $route;
	}

	public function render_routes( $api ) {

		$routes = '';
		if ( isset( $api['routes'] ) && is_array( $api['routes'] ) && ! empty( $api['routes'] ) ) {
			foreach ( $api['routes'] as $route ) {
				$routes .= $this->render_route( $api, $route );
			}
		}
		$routes .= "<div class='api_item'>
						" . Helper::render_dashicon( "plus api_route_add' title='Add Route" ) . '
					</div>';

		return $routes;
	}

	public function render_route( $api, $route, $atts = array() ) {

		$api = wp_parse_args(
			$api,
			array(
				'base_url' => '',
				'url_atts' => array(),
				'headers'  => array(),
			)
		);

		$route = wp_parse_args(
			$route,
			array(
				'subroute' => true,
				'title'    => '',
				'slug'     => '',
				'base_url' => $api['base_url'],
				'url_atts' => array(),
				'headers'  => array(),
			)
		);

		$atts = wp_parse_args(
			$atts,
			array(
				'class' => '',
			)
		);

		$route = $this->merge_routes( $api, $route );

		return "<div class='api_body " . $atts['class'] . "' data-route='" . $route['slug'] . "'>
					<h4>
						<span class='api_headline'>" . $route['title'] . "</span>
						<span class='api_buttons'>
							" . Helper::render_dashicon( "trash api_route_delete' title='Delete API Route" ) . '
							' . Helper::render_dashicon( "controls-play api_call' title='Call API Route" ) . '
							' . Helper::render_dashicon( "admin-tools api_edit_route' title='API Route Configuration" ) . '
						</span>
					</h4>
					' . $this->render_basic( $route ) . '
				</div>';

	}

	// blocks

	public function render_blocks( $api ) {

		$blocks = '';
		if ( isset( $api['blocks'] ) && is_array( $api['blocks'] ) && ! empty( $api['blocks'] ) ) {
			foreach ( $api['blocks'] as $block ) {
				$blocks .= $this->render_block( $api, $block );
			}
		}
		$blocks .= "<div class='api_item'>
						" . Helper::render_dashicon( "plus api_block_add' title='Add API Block" ) . '
					</div>';

		return $blocks;
	}

	public function render_block( $api, $block, $atts = array() ) {

		$api   = wp_parse_args(
			$api,
			array(
				'title'  => '',
				'slug'   => '',
				'routes' => array(),
			)
		);
		$block = wp_parse_args(
			$block,
			array(
				'route'     => '',
				'vars'      => '',
				'data_prop' => '',
				'data_item' => '',
			)
		);
		$atts  = wp_parse_args(
			$atts,
			array(
				'class' => '',
			)
		);

		$routes = array();
		if ( $api['title'] != '' && $api['slug'] != '' ) {
			$routes[ $api['slug'] ] = $api;
		}
		if ( is_array( $api['routes'] ) && ! empty( $api['routes'] ) ) {
			foreach ( $api['routes'] as $route ) {
				$routes[ $route['slug'] ] = $route;
			}
		}

		$current     = false;
		$route_title = __( 'Select route', 'greyd_hub' );
		$options     = "<option value=''>" . __( 'Select route', 'greyd_hub' ) . "</option>";
		foreach ( $routes as $slug => $route ) {
			$sel = '';
			if ( $slug == $block['route'] ) {
				$sel         = 'selected';
				$route_title = $route['title'];
				$route_title .= " <small>-> ".$block['route']."</small>";
				$current     = $route;
			}
			$options .= "<option value='" . $slug . "' " . $sel . '>' . $route['title'] . '</option>';
		}

		if ( isset($block['description']) ) {
			$route_title .= "<br><small><i>".$block['description']."</i></small>";
		}

		$vars = '<i>none</i>';
		if ( $current ) {
			$route = $this->merge_routes( $api, $current );
			$url   = $this->render_url( $route );
			preg_match_all( '/\{[a-z-_0-9]*\}/', $url, $matches );
			if ( $matches && $matches[0] && count( $matches[0] ) > 0 ) {
				$vars = '';
				foreach ( $matches[0] as $var ) {
					$val   = isset( $block['vars'][ $var ] ) ? $block['vars'][ $var ] : '';
					$vars .= "<div class='api_item'>
								<input type='text' class='api_var_key' value='" . $var . "' autocomplete='off' disabled>
								<input type='text' class='api_input_block api_var_value' value='" . $val . "' autocomplete='off'>
							</div>";
				}
			}
		}

		$data = '';
		if ( is_array( $block['data_item'] ) && ! empty( $block['data_item'] ) ) {
			foreach ( $block['data_item'] as $key => $value ) {
				$data .= $this->render_block_data_item(
					array(
						'key'   => $key,
						'value' => $value,
					)
				);
			}
		}
		$data .= "<div class='api_item'>
					" . Helper::render_dashicon( "plus api_item_add' data-dummy='api_block_data_item_dummy' title='Add Item" ) . '
				</div>';

		return "<div class='api_body " . $atts['class'] . "'>
					<h4>
						<span class='api_headline'>" . $route_title . "</span>
						<span class='api_buttons'>
							" . Helper::render_dashicon( "trash api_block_delete' title='Delete API Block" ) . '
							' . Helper::render_dashicon( "controls-play api_call' data-block='true' title='Call API Block" ) . '
							' . Helper::render_dashicon( "admin-tools api_edit_block' title='API Block Configuration" ) . '
						</span>
					</h4>
					' . $this->render_api_body_item(
							array(
								'class'   => 'hidden',
								'content' =>
								"<label>Route</label>
								<select class='api_input_block api_block_route' value='" . $block['route'] . "' autocomplete='off'>
									" . $options . '
								</select>',
							)
						) . '
					' . $this->render_api_body_item(
							array(
								'class'   => 'hidden',
								'content' =>
								"<label>Vars</label>
								<div class='api_items api_block_vars'>
									" . $vars . '
								</div>',
							)
						) . '
					' . $this->render_api_body_item(
							array(
								'class'   => 'hidden',
								'content' =>
								"<label>Data Prop</label>
								<input type='text' class='api_input_block api_block_prop' value='" . $block['data_prop'] . "' autocomplete='off'>",
							)
						) . '
					' . $this->render_api_body_item(
							array(
								'class'   => 'hidden',
								'content' =>
								"<label>Data Item</label>
								<div class='api_items api_block_data'>
									" . $data . '
								</div>',
							)
						) . '
					' . $this->render_api_body_item(
							array(
								'class'   => 'hidden',
								'content' =>
								"<input type='button' class='button api_call' data-block='true' value='Call Block Data'>",
							)
						) . '
				</div>';
	}

	public function render_block_data_item( $item, $atts = array() ) {

		$item = wp_parse_args(
			$item,
			array(
				'key'   => '',
				'value' => '',
			)
		);
		$atts = wp_parse_args(
			$atts,
			array(
				'class' => '',
			)
		);

		if ( is_string( $item['value'] ) ) {
			$item['value'] = array( 'prop' => $item['value'] );
		}
		$data_values = '';
		$keys        = array(
			array(
				'label' => 'Title',
				'key'   => 'title',
			),
			array(
				'label' => 'Info',
				'key'   => 'description',
			),
			array(
				'label' => 'Type',
				'key'   => 'type',
			),
			array(
				'label' => 'Prop',
				'key'   => 'prop',
			),
			array(
				'label' => 'Actions',
				'key'   => 'actions',
			),
		);
		foreach ( $keys as $data_key ) {
			$data_value = $item['value'][ $data_key['key'] ] ?? '';
			// if ( is_array($data_value) ) $data_value = json_encode($data_value);
			if ( $data_key['key'] == 'type' ) {
				$input = "<select class='api_input_block api_var_value' data-key='" . $data_key['key'] . "' value='" . $data_value . "' autocomplete='off'>
							<option value=''>" . __( 'Text', 'greyd_hub' ) . "</option>
							<option value='url' " . ( $data_value == 'url' ? 'selected' : '' ) . ">URL</option>
							<option value='file' " . ( $data_value == 'file' ? 'selected' : '' ) . '>File</option>
						</select>';
			} elseif ( $data_key['key'] == 'actions' ) {
				$data = '';
				if ( is_array( $data_value ) ) {
					foreach ( $data_value as $action ) {
						$data .= $this->render_block_data_item_action( $action );
					}
				}
				$input = "<div class='api_items api_block_data_item_values'>
							" . $data . "
							<div class='api_item'>
								" . Helper::render_dashicon( "plus api_item_add' data-dummy='api_block_data_item_action_dummy' title='Add Action" ) . '
							</div>
						</div>';
			} else {
				$input = "<input type='text' class='api_input_block api_var_value' data-key='" . $data_key['key'] . "' value='" . $data_value . "' autocomplete='off'>";
			}
			$data_values .= "<div class='api_item api_block_data_item_value'>
								<label>" . $data_key['label'] . '</label>
								' . $input . '
							</div>';
		}

		return "<div class='api_item api_block_data_item " . $atts['class'] . "'>
					<div class='api_action_move'>
						" . Helper::render_dashicon( "arrow-up api_action_up' title='Move Up" ) . '
						' . Helper::render_dashicon( "arrow-down api_action_down' title='Move Down" ) . "
					</div>
					<input type='text' class='api_input_block api_var_key' value='" . $item['key'] . "' autocomplete='off'>
					<div class='api_items api_block_data_items'>
						" . $data_values . '
					</div>
					' . Helper::render_dashicon( "trash api_item_delete' title='Delete Item" ) . '
				</div>';
	}

	public function render_block_data_item_action( $action, $atts = array() ) {

		$action = wp_parse_args(
			$action,
			array(
				'action' => '',
				'value'  => '',
			)
		);
		$atts   = wp_parse_args(
			$atts,
			array(
				'class' => '',
			)
		);

		// todo
		if ( is_array( $action['value'] ) ) {
			$action['value'] = json_encode( $action['value'] );
		}

		return "<div class='api_item api_block_data_item_action " . $atts['class'] . "'>
					<div class='api_action_move'>
						" . Helper::render_dashicon( "arrow-up api_action_up' title='Move Up" ) . '
						' . Helper::render_dashicon( "arrow-down api_action_down' title='Move Down" ) . "
					</div>
					<select class='api_input_block api_action_key' value='" . $action['action'] . "' autocomplete='off'>
						<option value=''>--</option>
						<option value='prepend' " . ( $action['action'] == 'prepend' ? 'selected' : '' ) . ">prepend</option>
						<option value='append' " . ( $action['action'] == 'append' ? 'selected' : '' ) . ">append</option>
						<option value='url_encode' " . ( $action['action'] == 'url_encode' ? 'selected' : '' ) . ">url_encode</option>
						<option value='json_encode' " . ( $action['action'] == 'json_encode' ? 'selected' : '' ) . ">json_encode</option>
						<option value='implode' " . ( $action['action'] == 'implode' ? 'selected' : '' ) . ">implode</option>
						<option value='explode' " . ( $action['action'] == 'explode' ? 'selected' : '' ) . ">explode</option>
						<option value='index' " . ( $action['action'] == 'index' ? 'selected' : '' ) . ">index</option>
						<option value='count' " . ( $action['action'] == 'count' ? 'selected' : '' ) . ">count</option>
						<option value='filter' " . ( $action['action'] == 'filter' ? 'selected' : '' ) . ">filter</option>
						<option value='call' " . ( $action['action'] == 'call' ? 'selected' : '' ) . ">call</option>
					</select>
					<input type='text' class='api_input_block api_action_value' value='" . $action['value'] . "' autocomplete='off'>
					" . Helper::render_dashicon( "trash api_item_delete' title='Delete Action" ) . '
				</div>';
	}

	// posttypes

	public function render_posttypes( $api ) {

		$posttypes = '';
		if ( isset( $api['posttypes'] ) && is_array( $api['posttypes'] ) && ! empty( $api['posttypes'] ) ) {
			foreach ( $api['posttypes'] as $posttype ) {
				$posttypes .= $this->render_posttype( $api, $posttype );
			}
		}
		// $posttypes .= "<div class='api_item'>
		// 				" . Helper::render_dashicon( "plus api_posttype_add' title='Add API Posttype" ) . '
		// 			</div>';

		return $posttypes;
	}

	public function render_posttype( $api, $posttype, $atts = array() ) {

		$posttype = array(
			'posttype_settings' => wp_parse_args(
				$posttype['posttype_settings'] ?? array(),
				array(
					'slug'        => '',
					'title'       => '',
					'is_taxonomy' => false,
				)
			),
			'api_settings' => $posttype['api_settings'] ?? array()
		);
		$atts  = wp_parse_args(
			$atts,
			array(
				'class' => '',
			)
		);

		$route_title = $posttype['posttype_settings']['title'];
		if ( $posttype['posttype_settings']['is_taxonomy'] ) {
			$route_title .= " <small>(Taxonomy)</small>";
		}
		if ( isset($posttype['api_settings']['route']) ) {
			$route_title .= " <small>-> ".$posttype['api_settings']['route']."</small>";
		}
		if ( isset($posttype['api_settings']['description']) ) {
			$route_title .= "<br><small><i>".$posttype['api_settings']['description']."</i></small>";
		}

		return "<div class='api_body ".$atts['class']."' data-posttype-json='".json_encode($posttype)."'>
					<h4>
						<span class='api_headline'>".$route_title."</span>
						<span class='api_buttons'>
							".Helper::render_dashicon( "controls-play api_call' data-posttype='true' title='Call API Posttype" )."
							".Helper::render_dashicon( "admin-tools api_edit_block' title='API Block Configuration" )."
						</span>
					</h4>
					".$this->render_api_body_item(
						array(
							'class'   => 'hidden',
							'content' =>
								"<div class='api_items api_posttype_data'>
									<h4>posttype_settings</h4>
									<pre>".print_r( $posttype['posttype_settings'], true )."</pre>
									<h4>api_settings</h4>
									<pre>".print_r( $posttype['api_settings'], true )."</pre>
								</div>",
						)
					)."
				</div>";
	}

	// helper

	public function render_api_body_item( $item ) {

		$item = wp_parse_args(
			$item,
			array(
				'class'   => '',
				'content' => '',
			)
		);

		return "<div class='api_body_item " . $item['class'] . "'>
					" . $item['content'] . '
				</div>';
	}

	public function render_api_item( $item ) {

		// debug($item);
		$item = wp_parse_args(
			$item,
			array(
				'class'     => '',
				'key'       => '',
				'value'     => '',
				'del_title' => 'Delete Item',
			)
		);

		return "<div class='api_item " . $item['class'] . "'>
					<input type='text' class='api_item_key' value='" . $item['key'] . "' autocomplete='off'>
					<input type='text' class='api_item_value' value='" . $item['value'] . "' autocomplete='off'>
					" . Helper::render_dashicon( "trash api_item_delete' title='" . $item['del_title'] . '' ) . '
				</div>';
	}
}

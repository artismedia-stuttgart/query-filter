<?php
/**
 * Features admin functionality.
 * 
 * Handles all admin rendering and menu functionality for the features page.
 */
namespace Greyd\Features;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	/**
	 * Holds the plugin config.
	 *
	 * @var object
	 */
	private $config;

	/**
	 * Holds the current mode (site or global)
	 * 
	 * @var string
	 */
	private $mode;

	/**
	 * Holds the current data
	 * 
	 * @var array
	 */
	private $data;

	/**
	 * Holds plugins data
	 * 
	 * @var array
	 */
	private $plugins;

	/**
	 * Constructor
	 */
	public function __construct( $config ) {
		$this->config = (object) $config;

		if ( is_admin() ) {
			// add menu and pages
			add_filter( 'greyd_submenu_pages_network', array( $this, 'add_greyd_submenu_page_network' ) );
			add_filter( 'greyd_submenu_pages', array( $this, 'add_greyd_submenu_page' ) );
			
			// enqueue scripts and styles
			add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_scripts' ), 10 );
		}
	}

	/**
	 * Load scripts in the admin area for features page.
	 */
	public function load_backend_scripts() {

		// Scripts
		wp_register_script(
			"greyd-admin-features",
			plugin_dir_url( __FILE__ ) . 'assets/js/admin-features.js',
			array('wp-data', 'jquery'),
			GREYD_VERSION,
			true
		);
	}

	/*
	=======================================================================
		admin menu
	=======================================================================
	*/

	/**
	 * Add the network submenu item to Greyd.Suite
	 *
	 * @see filter 'greyd_submenu_pages_network'
	 */
	public function add_greyd_submenu_page_network( $submenu_pages ) {
		// debug($submenu_pages);

		if ( \Greyd\Helper::is_greyd_classic() ) {
			return $submenu_pages;
		}

		array_push(
			$submenu_pages,
			array(
				'title'    => \Greyd\Features::$page['title'],
				'cap'      => \Greyd\Features::$page['cap'],
				'slug'     => \Greyd\Features::$page['slug'],
				'callback' => array( $this, 'render_features_page' ),
				'position' => 1,
			)
		);

		return $submenu_pages;
	}

	/**
	 * Add the submenu item to Greyd.Suite
	 *
	 * @see filter 'greyd_submenu_pages'
	 */
	public function add_greyd_submenu_page( $submenu_pages ) {
		// debug($submenu_pages);

		if ( \Greyd\Helper::is_greyd_classic() ) {
			return $submenu_pages;
		}

		array_push(
			$submenu_pages,
			array(
				'page_title' => __( 'Greyd.Suite', 'greyd_hub' ) . ' ' . \Greyd\Features::$page['title'],
				'menu_title' => \Greyd\Features::$page['title'],
				'cap'        => \Greyd\Features::$page['cap'],
				'slug'       => \Greyd\Features::$page['slug'],
				'callback'   => array( $this, 'render_features_page' ),
				'position'   => 1,
			)
		);

		return $submenu_pages;
	}

	/**
	 * Prepare the settings mode.
	 *
	 * @return string
	 */
	public function get_mode() {
		// $mode = is_multisite() ? (is_network_admin() ? "network_admin" : "network_site") : "site";
		return is_multisite() && is_network_admin() ? 'global' : 'site';
	}

	/**
	 * Prepare the plugins data.
	 *
	 * @return array
	 */
	public function get_plugins_data() {
		return array(
			'site'   => \Greyd\Helper::active_plugins( 'site' ),
			'global' => \Greyd\Helper::active_plugins( 'global' ),
		);
	}


	/*
	=======================================================================
		FEATURES PAGE RENDERING
	=======================================================================
	*/

	/**
	 * render features page
	 *
	 * @param array $data   All current features.
	 */
	public function render_features_page( $data = null ) {

		wp_enqueue_script(
			"greyd-admin-features"
		);
		wp_localize_script("greyd-admin-features", 'greyd_features_ajax', array(
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('greyd-features-ajax')
		));

		// load data
		$this->data    = \Greyd\Features::get_saved_features();
		$this->mode    = $this->get_mode();
		$this->plugins = $this->get_plugins_data();

		// prepare features
		$features = \Greyd\Features::prepare_features();

		?>
		<div class="wrap greyd-dashboard greyd-features" style="<?php echo \Greyd\Admin::get_admin_bar_color(); ?>">

			<form method="post">
				<input type="hidden" name="mode" value="<?php echo $this->mode; ?>">

				<h1><?php _e( 'Features', 'greyd_hub' ); ?></h1>
				<h2><?php _e( 'Customize your Greyd.Suite experience and make it your own.', 'greyd_hub' ); ?></h2>
				
				<hr>

				<?php
				wp_nonce_field( 'greyd_features_save' );

				$features_found = false;

				foreach ( $features as $section => $items ) {

					if ( count( $items ) == 0 ) {
						continue;
					}

					$features_found = true;

					$this->render_features_section( $section, $items );

				}

				if ( $features_found ) {
					echo get_submit_button();
				} else {
					echo '<p>' . __( 'No features available.', 'greyd_hub' ) . '</p>';
				}
				?>
			</form>
		</div>
		<style> #wpcontent { height: auto !important } </style>
		<?php
	}

	/**
	 * Render a features section
	 *
	 * @param string $section   Slug of the section.
	 * @param array  $features  Features.
	 */
	public function render_features_section( $section, $features ) {

		$section_metadata = array(
			'core'     => array(
				'type'        => __( 'Feature', 'greyd_hub' ),
				'headline'    => '',
				'description' => '',
			),
			'bundled'  => array(
				'type'        => __( 'Plugin', 'greyd_hub' ),
				'headline'    => __( "Bundled plugins", 'greyd_hub' ),
				'description' => sprintf(
					__( 'Non-Greyd plugins, bundled inside the "features" folder of the %s plugin.', 'greyd_hub' ),
					$this->config->plugin_name_full
				),
			),
			'internal' => array(
				'type'        => __( 'Plugin', 'greyd_hub' ),
				'headline'    => __( "Recommended plugins", 'greyd_hub' ),
				'description' => __( "Plugins within this WordPress installation.", 'greyd_hub' ),
			),
			'external' => array(
				'type'        => __( "External plugin", 'greyd_hub' ),
				'headline'    => __( 'External plugins', 'greyd_hub' ),
				'description' => __( 'External plugins in different DIR on same server (or with execute permission), linked by filter: "greyd_features_files".', 'greyd_hub' ),
			),
		);

		$metadata = isset( $section_metadata[ $section ] ) ? $section_metadata[ $section ] : array(
			'type'        => __( 'Unknown', 'greyd_hub' ),
			'headline'    => '',
			'description' => '',
		);

		echo "<h2>{$metadata['headline']}</h2>
			<small class='color_light'>{$metadata['description']}</small>
			<div class='greyd-card-grid'>";

		foreach ( $features as $feature ) {
			$this->render_features_item( $feature, $metadata['type'] );
		}

		echo '</div>';
	}

	/**
	 * Render a feature card
	 *
	 * @param array  $feature   Metadata.
	 * @param string $type      Type of the Feature. (default: 'Feature')
	 */
	public function render_features_item( $feature, $type ) {

		// hidden
		if ( isset( $feature['Hidden'] ) && $feature['Hidden'] ) {
			return;
		}

		// invalid
		if ( ! isset( $feature['state'] ) || $feature['state'] != 'valid' ) {
			return;
		}

		$checked    = isset( $this->data[ $this->mode ][ $feature['slug'] ] ) ? 'checked' : '';
		$classNames = array();
		$disabled   = '';
		$info       = '';
		$state      = '';
		$dashicon   = 'info';

		// check if feature has requirements
		if ( ! empty( $feature['RequiresHub'] ) ) {

			$disabled     = 'disabled';
			$dependencies = explode( ',', $feature['RequiresHub'] );
			$check        = array();

			foreach ( $dependencies as $i => $dependency ) {

				$dependencies[ $i ] = trim( $dependency );

				// check if requirement is met
				if (
					isset( $this->data[ $this->mode ][ $dependencies[ $i ] ] ) ||
					( $this->mode == 'site' && isset( $this->data['global'][ $dependencies[ $i ] ] ) )
				) {
					array_push( $check, 'true' );
				} else {
					array_push( $check, 'false' );
				}
			}
			$check = array_unique( $check );

			if ( count( $check ) == 1 && $check[0] == 'true' ) {
				$disabled = '';
				$dashicon = 'saved';
			} elseif ( $checked != '' ) {
				$checked = '';
			}

			$feature['RequiresHub'] = implode( ', ', $dependencies );
			if ( ! empty( $disabled ) ) {
				$info = sprintf( __( 'Requires: %s', 'greyd_hub' ), $feature['RequiresHub'] );
			}
		}

		// check if feature is globally active
		if ( $this->mode == 'site' && isset( $this->data['global'][ $feature['slug'] ] ) ) {
			$checked  = 'checked';
			$disabled = 'disabled';
			if ( is_multisite() ) {
				$info     = __( 'Globally enabled in network admin', 'greyd_hub' );
				$dashicon = 'saved';
			}
			else {
				$info     = __( 'Always active on single sites', 'greyd_hub' );
				$dashicon = 'info';
			}
		}

		// check if feature is active as plugin
		$maybe_plugin = str_replace( '../', '', $feature['include'] );
		if (
			in_array( $maybe_plugin, $this->plugins[ $this->mode ] ) ||
			( $this->mode == 'site' && in_array( $maybe_plugin, $this->plugins['global'] ) )
		) {
			// plugin is enabled
			$checked  = 'checked';
			$disabled = 'disabled';
			$dashicon = 'saved';

			if ( in_array( $maybe_plugin, $this->plugins['global'] ) ) {
				$info = __( 'Sitewide enabled in network plugins', 'greyd_hub' );
			} else {
				$info = __( "Enabled in plugins", 'greyd_hub' );
			}
		}

		if ( $feature['Disabled'] ) {
			$info     = __( 'In development', 'greyd_hub' );
			$dashicon = 'editor-code';
		}

		// Flag-based indicators (alpha, beta)
		$flag_info       = '';
		$flag_state      = '';
		$flag_dashicon   = 'info';
		if ( isset( $feature['Flag'] ) && ! empty( $feature['Flag'] ) ) {
			$status = strtolower( trim( $feature['Flag'] ) );
			
			switch ( $status ) {
				case 'alpha':
					$flag_info     = __( 'Alpha', 'greyd_hub' );
					$flag_state    = 'red';
					$flag_dashicon = 'warning';
					break;
				case 'beta':
					$flag_info     = __( 'Beta', 'greyd_hub' );
					$flag_state    = 'orange';
					$flag_dashicon = 'info';
					break;
			}
		}

		// forced
		if ( $feature['Forced'] ) {
			$checked      = 'checked';
			$classNames[] = 'is-hidden';
		}

		echo "<label class='greyd-card greyd-feature " . implode( ' ', $classNames ) . "' for='feature-{$feature["slug"]}' data-disabled=" . ( $feature['Disabled'] ? 'true' : '' ) . ">
			<div>
			<span class='components-form-toggle'>
				<input type='hidden' name='include[{$feature["slug"]}]' value='{$feature["include"]}'>
				<input type='hidden' name='requires[{$feature["slug"]}]' value='{$feature["RequiresHub"]}'>
				<input class='components-form-toggle__input' type='checkbox' name='feature[{$feature["slug"]}]' id='feature-{$feature["slug"]}' {$checked} {$disabled}>
				<span class='components-form-toggle__track'></span>
				<span class='components-form-toggle__thumb'></span>
			</span>
			</div>
			<div>
				<h5 class='greyd-card--title'>{$feature["Name"]}</h5>
				<p class='greyd-card--desc'>{$feature["Description"]}</p>
				" .
				( empty( $info ) ? '' : "<p class='greyd-card--info {$state}'><span class='dashicons dashicons-{$dashicon}'></span>{$info}</p>" ) .
				( !empty( $info ) && !empty( $flag_info ) ? '<br>' : "" ) .
				( empty( $flag_info ) ? '' : "<p class='greyd-card--info {$flag_state}'><span class='dashicons dashicons-{$flag_dashicon}'></span>{$flag_info}</p>" ) .
				'
			</div>
		</label>';
	}
}

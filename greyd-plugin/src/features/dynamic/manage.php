<?php
/**
 * Dynamic block features.
 * - Template Block
 * - dynamic content
 * - dynamic tags
 */
namespace Greyd\Dynamic;

if ( ! defined( 'ABSPATH' ) ) exit;

new Manage($config);
class Manage {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if (!function_exists('register_block_type')) return;

		// set config
		$this->config = (object) $config;

		// enqueue dynamic scripts
		add_action( 'enqueue_block_editor_assets', array($this, 'register_blocks_assets') );

		// register block
		add_action( 'init', array($this, 'register_blocks'), 99 );
		add_filter( 'greyd_blocks_editor_data', array( $this, 'add_editor_data' ) );
		add_action( 'admin_notices', array( $this, 'reference_error_notice' ) );

		// allow endpoints in block editor
		add_filter( 'greyd_allow_rest_endpoints', array( $this, 'allow_rest_endpoints' ) );
		add_action( 'init', array( $this, 'allow_navigation_in_rest' ), 9999 );

	}

	/**
	 * Register and enqueue dynamic scripts for the editor
	 */
	public function register_blocks_assets() {

		if ( method_exists( '\greyd\blocks\render', 'render_dynamic' ) ) return;
		

		$js_uri = plugin_dir_url( __FILE__ ) . 'assets/js';

		// editor script
		wp_register_script(
			'greyd-dynamic-editor',
			trailingslashit( $js_uri ).'editor.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-dynamic-editor');

		// dynamic script
		wp_register_script(
			'greyd-dynamic',
			trailingslashit( $js_uri ).'dynamic.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-dynamic');
		
		// dynamic actions script
		wp_register_script(
			'greyd-dynamic-actions',
			trailingslashit( $js_uri ).'actions.js',
			array( 'greyd-dynamic', 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-dynamic-actions');
		
		// dtag format script
		wp_register_script(
			'greyd-format-dtag',
			trailingslashit( $js_uri ).'format-dtag.js',
			array( 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-format-dtag');

		// autocompleters script
		wp_register_script(
			'greyd-dynamic-autocompleter',
			trailingslashit( $js_uri ).'autocompleter.js',
			array( 'greyd-format-dtag', 'greyd-tools', 'greyd-components', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash' ),
			GREYD_VERSION
		);
		wp_enqueue_script('greyd-dynamic-autocompleter');
		
		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-dynamic-editor', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-dynamic', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-dynamic-actions', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-format-dtag', 'greyd_hub', $this->config->plugin_path.'/languages' );
			wp_set_script_translations( 'greyd-dynamic-autocompleter', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
	}

	/**
	 * Blockeditor
	 */
	public function register_blocks() {

		if ( method_exists( '\greyd\blocks\render', 'render_dynamic' ) ) return;

		$js_uri = plugin_dir_url(__FILE__).'assets/js';

		// register script
		wp_register_script(
			'greyd-dynamic-editor-script',
			trailingslashit( $js_uri ).'block-dynamic.js',
			array( 'greyd-dynamic', 'greyd-tools', 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'lodash', 'wp-core-data', 'wp-edit-post' ),
			GREYD_VERSION
		);

		// add script translations
		if ( function_exists('wp_set_script_translations') ) {
			wp_set_script_translations( 'greyd-dynamic-editor-script', 'greyd_hub', $this->config->plugin_path.'/languages' );
		}
		
		// register dynamic template block
		$template_use_reference = $this->get_template_use_reference();
		$template_useSlug = $template_use_reference == "slug" || $template_use_reference == "forceSlug";
		register_block_type( 'greyd/dynamic', array(
			'supports' => array( 'anchor' => true ),
			'attributes' => array(
				'anchor'          => array( 'type' => 'string' ),
				'postId'          => array( 'type' => 'string' ), // backend helper to get the queried post in loop
				'dynamic_parent'  => array( 'type' => 'string' ), // dynamic template backend helper
				'dynamic_value'   => array( 'type' => 'string' ), // dynamic template frontend helper
				'dynamic_fields'  => array( 'type' => 'array' ),
				'inline_css'      => array( 'type' => 'string', 'default' => '' ),
				'inline_css_id'   => array( 'type' => 'string', 'default' => '' ),
				'template'        => array( 'type' => 'string', 'default' => '' ),
				'useSlug'         => array( 'type' => 'boolean', 'default' => $template_useSlug ),
				'dynamic_content' => array( 'type' => 'array', 'items' => array(
					'type' => 'object', 'properties' => array(
						'dkey'   => array( 'type' => 'string' ),
						'dtype'  => array( 'type' => 'string' ),
						'dtitle' => array( 'type' => 'string' ),
						'dvalue' => array( 'type' => 'string' ),
					),
				), 'default' => array() ),
			),
			'uses_context' => array(  "postId", "postType", "queryId" ),
			'editor_script' => 'greyd-dynamic-editor-script',
			// 'render_callback' =>  array('\greyd\blocks\render', 'render_dynamic'),
			// 'render_callback' =>  array('\greyd\blocks\dynamic\Render', 'render_dynamic'),
			'render_callback' =>  array('\Greyd\Dynamic\Render_Blocks', 'render_dynamic'),
		) );
	
	}

	/**
	 * Filter Blockeditor Data values.
	 *
	 * @filter 'greyd_blocks_editor_data'
	 *
	 * @param object $data     Original Data Array
	 * @return object $data    Data Array with filtered Values
	 */
	public function add_editor_data( $data ) {
		$data["template_use_reference"] = $this->get_template_use_reference();
		return $data;
	}

	/**
	 * store error value from filter 'greyd_dynamic_block_use_reference' to show in admin_notice
	 */
	private $use_reference_error = false;

	/**
	 * Get dynamic Template Block reference value.
	 * Uses filter 'greyd_dynamic_block_use_reference' and checks for faulty values.
	 * Errors will be shown in admin_notice.
	 * 
	 * @return string $template_use_reference	Reference value "ID" (default), "slug", "forceID" or "forceSlug"
	 */
	public function get_template_use_reference() {

		/**
		 * Change the way the dynamic Template Block reference the selected template.
		 * Possible values are:
		 * "ID" (default): default reference by ID, toggle available.
		 * "slug": default reference by slug, toggle available.
		 * "forceID": default reference by ID, toggle disabled, focused blocks will be converted to reference by ID.
		 * "forceSlug": default reference by slug, toggle disabled, focused blocks will be converted to reference by slug.
		 * 
		 * @filter 'greyd_dynamic_block_use_reference'
		 * 
		 * @param string $reference		default: "ID"
		 */
		$template_use_reference = apply_filters( 'greyd_dynamic_block_use_reference', "ID" );
		if ( !in_array( $template_use_reference, array( "ID", "slug", "forceID", "forceSlug" ) ) ) {
			// unsupported value
			if ( is_admin() ) {
				$this->use_reference_error = htmlspecialchars( $template_use_reference );
			}
			return "ID";
		}
		return $template_use_reference;

	}

	/**
	 * Show Admin Notice if filter 'greyd_dynamic_block_use_reference' is wrongly used.
	 * 
	 * @filter 'admin_notice'
	 */
	public function reference_error_notice() {

		if ($this->use_reference_error) {
			echo "<div class='error notice'>
				<p>".sprintf( __( 'The filter "greyd_dynamic_block_use_reference" is set to an unsupported value of: "%s".', 'greyd_hub' ), $this->use_reference_error )."</p>
				<p>".__( 'Possible values are "ID" (default), "slug", "forceID" or "forceSlug".', 'greyd_hub' )."</p>
			</div>";
		}
		
	}


	/**
	 * Blockeditor endpoints
	 * @since 2.1.0
	 * @filter 'greyd_allow_rest_endpoints'
	 */
	public function allow_rest_endpoints( $allowed ) {

		$allowed = array_merge( $allowed, array(
			// read templates
			'/wp/v2/dynamic_template',
			'/wp/v2/dynamic_template/(?P<id>[\d]+)',

			// read navigation (in templates)
			'/wp/v2/navigation',
			'/wp/v2/navigation/(?P<id>[\d]+)',
			'/wp/v2/pages',
			'/wp/v2/pages/(?P<id>[\d]+)',

			// read navigation (core block)
			'/wp-block-editor/v1/navigation-fallback',
		) );

		return $allowed;

	}

	/**
	 * Allow to fetch wp_navigation posts of status 'draft'
	 * Needed to allow the endpoint to read navigation in the Editor (see 'allow_rest_endpoints')
	 * @since 2.1.0
	 */
	public function allow_navigation_in_rest() {

		if (
			is_user_logged_in() &&
			current_user_can("edit_posts") &&
			isset($_REQUEST["rest_route"]) &&
			isset($_REQUEST["_locale"]) && $_REQUEST["_locale"] == "user"
		) {

			global $wp_post_types;
			if ( isset($wp_post_types['wp_navigation']) ) {
				// allow to fetch status 'draft'
				$wp_post_types['wp_navigation']->cap->edit_posts = "edit_posts";
			}

		}
		
	}

}
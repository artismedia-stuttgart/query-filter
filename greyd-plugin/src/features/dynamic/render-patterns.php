<?php
/**
 * Renders patterns
 */

namespace Greyd\Dynamic;

use Greyd\Settings;

if ( !defined( 'ABSPATH' ) ) exit;

new Render_Patterns();
class Render_Patterns {
	/**
	 * Constructor
	 */
	public function __construct() {

		/**
		 * Check if the GREYD_ADD_DYNAMIC_TEMPLATE_PATTERNS constant is set to false or true.
		 * This constant can be used to force the rendering of patterns on or off.
		 */
		$force_pattern_setting = defined( 'GREYD_ADD_DYNAMIC_TEMPLATE_PATTERNS' ) ? GREYD_ADD_DYNAMIC_TEMPLATE_PATTERNS : null;
		if ( $force_pattern_setting === false ) {
			return;
		}
		else if ( $force_pattern_setting === true ) {
			add_filter( 'init', array( $this, 'render_block_patterns' ) );
			return;
		}

		// add settings
		add_filter( 'greyd_settings_default_site', array( $this, 'add_setting' ) );
		add_filter( 'greyd_settings_basic', array( $this, 'render_settings' ), 10, 3 );
		add_filter( 'greyd_settings_more_save', array( $this, 'save_settings' ), 10, 3 );

		// check if Gutenberg is active.
		if ( !function_exists( 'register_block_pattern' ) ) return;
		if ( is_admin() ) return;

		// Check if dynamic template patterns are disabled in the settings, return
		$greyd_settings = get_option( 'greyd_settings' );
		if (
			isset( $greyd_settings['editor'] )
			&& isset( $greyd_settings['editor']['hide_dynamic_template_patterns'] )
			&& $greyd_settings['editor']['hide_dynamic_template_patterns'] === true
		) {
			return;
		}

		add_filter( 'init', array( $this, 'render_block_patterns' ) );
	}

	/*
	=================================================================
		SETTINGS
	=================================================================
	*/

	/**
	 * Get default settings
	 */
	public static function get_defaults() {

		$defaults = array(
			'editor' => array(
				'hide_dynamic_template_patterns' => false,
			),
		);

		return $defaults;
	}

	/**
	 * Add default settings
	 *
	 * @see filter 'greyd_settings_default_site'
	 */
	public function add_setting( $settings ) {

		// add default settings
		$settings = array_replace_recursive(
			$settings,
			self::get_defaults()
		);

		return $settings;
	}

	/**
	 * Get the current settings.
	 */
	public static function get_settings() {

		// get from settings
		return Settings::get_setting( array( 'site', 'editor' ) );
	}


	/**
	 * Render the settings
	 *
	 * @param string $content   Content of all additional settings.
	 * @param string $mode      'site' | 'network_site' | 'network_admin'
	 * @param array  $data       Current settings.
	 */
	public function render_settings( $content, $mode, $data ) {

		$defaults = self::get_defaults();
		$settings = $data['site']['editor'];
		$enable   = $settings['hide_dynamic_template_patterns'];

		$content .= '<h2>'.__( 'Editor', 'greyd_hub' ).'</h2>';
		$content .= "
		<table class='form-table'>
			<tr>
				<th>".__( 'Hide Dynamic Template Patterns', 'greyd_hub' )."</th>
				<td>
					<label for='editor[hide_dynamic_template_patterns]'>
					<input type='checkbox' id='editor[hide_dynamic_template_patterns]' name='editor[hide_dynamic_template_patterns]' ".( $enable ? "checked='checked'" : '' ).'/>
						<span>'.__( 'Disable patterns', 'greyd_hub' )."</span><br>
						<small class='color_light'>".__( 'Removes Dynamic Templates as patterns from the pattern library in the left sidebar.', 'greyd_hub' ).'</small>
				</td>
			</tr>
		</table>';
		return $content;
	}

	/**
	 * Save site settings
	 *
	 * @see filter 'greyd_settings_more_save'
	 *
	 * @param array $site       Current site settings.
	 * @param array $defaults   Default values.
	 * @param array $data       Raw $_POST data.
	 */
	public function save_settings( $site, $defaults, $data ) {

		// make new settings
		$site['editor'] = array(
			'hide_dynamic_template_patterns' => isset( $data['editor']['hide_dynamic_template_patterns'] ) && $data['editor']['hide_dynamic_template_patterns'] === 'on' ? true : $defaults['editor']['hide_dynamic_template_patterns'],
		);

		return $site;
	}

	/*
	=================================================================
		RENDER BLOCK PATTERNS
	=================================================================
	*/
	public function render_block_patterns() {

		// Create a pattern category with the name "Greyd Dynamic Templates"
		register_block_pattern_category(
			'greyd-dynamic-templates',
			array( 'label' => 'Greyd Dynamic Templates' )
		);

		// Get all posts of the post type "dynamic_template"
		$dynamic_templates = get_posts(
			array(
				'post_type'   => 'dynamic_template',
				'numberposts' => -1,
			)
		);

		// Use the register_block_pattern() function to register each template as a pattern
		foreach ( $dynamic_templates as $template ) {
			register_block_pattern(
				'greyd-plugin/'.$template->post_name,
				array(
					'title'      => $template->post_title,
					'content'    => '<!-- wp:greyd/dynamic {"template":"'.$template->ID.'"} -->',
					'categories' => array( 'greyd-dynamic-templates' ),
				)
			);
		}
	}
}

<?php
/**
 * Register all blocks & categories.
 */
namespace greyd\blocks;

if( ! defined( 'ABSPATH' ) ) exit;

new blocks($config);
class blocks {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		// check if Gutenberg is active.
		if ( !function_exists('register_block_type') ) return;

		// set config
		$this->config = (object)$config;

		// register blocks
		add_action( 'init', array($this, 'register_block_categories'), 90 );

		// add metadata to core blocks
		add_filter( 'block_type_metadata_settings', array($this, 'filter_blocks_metadata_settings_registration'), 10, 2 );

		// filter allowed html
		add_filter( 'wp_kses_allowed_html', array($this, 'filter_allowed_html_tags'), 98, 2 );
	}

	/**
	 * Register the blocks
	 */
	public function register_block_categories() {

		// categories
		add_filter('block_categories_all', function ($categories, $post) {
			return array_merge(
				array(
					array(
						'slug'  => 'greyd-blocks',
						'title' => 'Greyd',
					),
				),
				$categories
			);
		}, 10, 2);
	}

	/**
	 * Modify the settings determined from the processed block type metadata.
	 * 
	 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#block_type_metadata
	 * 
	 * @param array $settings   Array of determined settings for registering a block type.
	 * @param array $metadata   Metadata loaded from the block.json file.
	 * 
	 * @return array $settings
	 */
	public function filter_blocks_metadata_settings_registration( $settings, $metadata ) {
		if ( $metadata['name'] == 'core/archives' ) {

			$settings['attributes'] = array_merge(
				$settings['attributes'],
				array(
					'filter' => array(
						"type" => "object",
						"default" => array(
							"post_type" 	=> 'post',
							"type" 			=> 'monthly',
							"order" 		=> '',
							"hierarchical" 	=> 0,
							"date_format"	=> ''
						)
					),
					'styles' => array(
						"type" => "object",
						"default" => array(
							"style" 	=> '',
							"size" 		=> '',
							"custom" 	=> 0,
							"icon" 		=> array(
								"content" =>'',
								"position" => 'after',
								"size" => '100%',
								"margin" => '10px'
							)
						)
					),
					'greydClass' => array(
						"type" => "string"
					),
					'customStyles' => array(
						"type" => "object"
					),
				)
			);
		}
		return $settings;
	}


	/**
	 * Filters the HTML tags that are allowed for a given context.
	 * 
	 * HTML tags and attribute names are case-insensitive in HTML but must be
	 * added to the KSES allow list in lowercase. An item added to the allow list
	 * in upper or mixed case will not recognized as permitted by KSES.
	 * 
	 * @param array[] $html    Allowed HTML tags.
	 * @param string  $context Context name.
	 */
	public function filter_allowed_html_tags( $html, $context ) {

		if ( $context !== 'post' ) return $html;

		/**
		 * Allow <style> inside post content to enable custom buttons
		 * with the block component 'RenderSavedStyles'.
		 */
		if ( !isset( $html['style'] ) ) {
			$html['style'] = array( 'class' => true );
		} else {
			$html['style'] = array_merge( $html['style'], array( 'class' => true ) );
		}

		$default_link_attributes = array(
			"id" => true,
			"class" => true,
			"href" => true,
			"name" => true,
			"target" => true,
			"download" => true,
			"data-*" => true,
			"style" => true,
			"title" => true,
			'role' => true,
			'onclick' => true,
			"aria-*" => true,
			'aria-expanded' => true,
			'aria-controls' => true,
			'aria-label' => true,
			'tabindex' => true,
		);

		/**
		 * Allow <a> inside post content to enable custom buttons.
		 */
		if ( !isset( $html['a'] ) ) {
			$html['a'] = $default_link_attributes;
		} else {
			$html['a'] = array_merge( $html['a'], $default_link_attributes );
		}

		/**
		 * Allow <button> inside post content to enable custom buttons.
		 * @since 1.5.6
		 */
		if ( !isset( $html['button'] ) ) {
			$html['button'] = $default_link_attributes;
		} else {
			$html['button'] = array_merge( $html['button'], $default_link_attributes );
		}

		/**
		 * Allow style inside mark attributes.
		 */
		if ( !isset( $html['mark'] ) ) {
			$html['mark'] = array(
				'style' => true,
				"id" => true,
				"class" => true
			);
		} else {
			$html['mark'] = array_merge( $html['mark'], array( 'style' => true ) );
		}

		/**
		 * Allow div attributes 'onclick' & 'aria-*'.
		 * 
		 * @since 1.6.2
		 */
		if ( !isset( $html['div'] ) ) {
			$html['div'] = array(
				"id" => true,
				"class" => true,
				'onclick' => true,
				"aria-*" => true,
				'aria-expanded' => true,
				'aria-controls' => true,
				'aria-label' => true,
			);
		} else {
			$html['div'] = array_merge( $html['div'], array(
				"id" => true,
				"class" => true,
				'onclick' => true,
				"aria-*" => true,
				'aria-expanded' => true,
				'aria-controls' => true,
				'aria-label' => true,
			) );
		}

		/**
		 * Allow dialog to enable popover blocks.
		 * 
		 * @since 1.7.0
		 */
		if ( !isset( $html['dialog'] ) ) {
			$html['dialog'] = array(
				"id" => true,
				"class" => true
			);
		} else {
			$html['dialog'] = array_merge( $html['dialog'], array(
				"id" => true,
				"class" => true
			) );
		}

		/**
		 * Filters the HTML tags that are allowed inside post content.
		 * 
		 * @filter greyd_blocks_filter_allowed_html_tags
		 * 
		 * @param array[] $html    Allowed HTML tags.
		 * @param string  $context Context name.
		 * 
		 * @return array[] $html   Allowed HTML tags.
		 */
		return apply_filters( 'greyd_blocks_filter_allowed_html_tags', $html, $context );
	}
}
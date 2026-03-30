<?php
/*
Feature Name:   Icons
Description:    Include the 'Elegant Icons' library.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Forced:         true
*/
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Icons for render callbacks
 *
 * As of today, only the 'Elegant Icons' library is supported:
 *
 * @see https://www.elegantthemes.com/blog/resources/elegant-icon-font
 */
new Icons( $config );
class Icons {

	private $config;

	public function __construct( $config ) {

		// set config
		$this->config = (object) $config;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ), 98 );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_style' ), 11 );
		add_filter( 'block_editor_settings_all', array($this, 'add_editor_inline_style') );
	}

	/**
	 * Load scripts and styles in the frontend.
	 */
	public function enqueue_frontend_styles() {

		$this->deregister_deprecated_versions();

		wp_register_style(
			'elegant-icons',
			plugin_dir_url( __FILE__ ) . 'assets/elegant_icons.css',
			array(),
			'1.0'
		);
		wp_enqueue_style( 'elegant-icons' );
	}

	/**
	 * Enqueue Icon Stylesheet in the editor.
	 * 
	 * We need to enqueue the stylesheet in the editor, so that the icons are
	 * available in the editor controls & components.
	 */
	public function enqueue_editor_style() {

		$this->deregister_deprecated_versions();

		wp_register_style(
			'elegant-icons',
			plugin_dir_url( __FILE__ ) . 'assets/elegant_icons.css',
			array(),
			'1.0'
		);
		wp_enqueue_style( 'elegant-icons' );
	}

	/**
	 * Load Icon Stylesheet in the editor as inline style.
	 * 
	 * We need to load the stylesheet as inline style, so that the icons are
	 * available in the editor content.
	 */
	public function add_editor_inline_style( $editor_settings ) {

		$inline_style = \Greyd\Helper::get_file_contents(
			plugin_dir_path( __FILE__ ) . 'assets/elegant_icons.css'
		);

		$inline_style = str_replace(
			"url('./",
			"url('" . plugin_dir_url( __FILE__ ) . "assets/",
			$inline_style
		);

		$editor_settings['styles'][] = array( 'css' => $inline_style );
		// $editor_settings['defaultEditorStyles'][] = array( 'css' => $inline_style );

		return $editor_settings;
	}

	/**
	 * Deregister deprecated versions
	 */
	public function deregister_deprecated_versions() {
		if ( wp_style_is( 'elegant-icons', 'registered' ) ) {
			wp_deregister_style( 'elegant-icons' );
		}
		if ( wp_style_is( 'elegant-icons', 'enqueued' ) ) {
			wp_dequeue_style( 'elegant-icons' );
		}
		if ( wp_style_is( 'elegant_icons', 'registered' ) ) {
			wp_deregister_style( 'elegant_icons' );
		}
		if ( wp_style_is( 'elegant_icons', 'enqueued' ) ) {
			wp_dequeue_style( 'elegant_icons' );
		}
	}

	/**
	 * Get all icons
	 */
	public static function get_icons() {
		return self::$icons;
	}

	/**
	 * Get single icon css pseudo content
	 */
	public static function get_icon( $key ) {
		return self::$icons[ $key ]['content'];
	}

	/**
	 * Icons array
	 */
	public static $icons = array(
		'arrow_up'                  => array(
			'content' => "\\21",
			'title'   => 'Arrow up',
		),
		'arrow_down'                => array(
			'content' => "\\22",
			'title'   => 'Arrow down',
		),
		'arrow_left'                => array(
			'content' => "\\23",
			'title'   => 'Arrow left',
		),
		'arrow_right'               => array(
			'content' => "\\24",
			'title'   => 'Arrow right',
		),
		'arrow_left-up'             => array(
			'content' => "\\25",
			'title'   => 'Arrow left-up',
		),
		'arrow_right-up'            => array(
			'content' => "\\26",
			'title'   => 'Arrow right-up',
		),
		'arrow_right-down'          => array(
			'content' => "\\27",
			'title'   => 'Arrow right-down',
		),
		'arrow_left-down'           => array(
			'content' => "\\28",
			'title'   => 'Arrow left-down',
		),
		'arrow-up-down'             => array(
			'content' => "\\29",
			'title'   => 'Arrow up-down',
		),
		'arrow_up-down_alt'         => array(
			'content' => "\\2a",
			'title'   => 'Arrow up-down_alt',
		),
		'arrow_left-right_alt'      => array(
			'content' => "\\2b",
			'title'   => 'Arrow left-right_alt',
		),
		'arrow_left-right'          => array(
			'content' => "\\2c",
			'title'   => 'Arrow left-right',
		),
		'arrow_expand_alt2'         => array(
			'content' => "\\2d",
			'title'   => 'Arrow expand_alt2',
		),
		'arrow_expand_alt'          => array(
			'content' => "\\2e",
			'title'   => 'Arrow expand_alt',
		),
		'arrow_condense'            => array(
			'content' => "\\2f",
			'title'   => 'Arrow condense',
		),
		'arrow_expand'              => array(
			'content' => "\\30",
			'title'   => 'Arrow expand',
		),
		'arrow_move'                => array(
			'content' => "\\31",
			'title'   => 'Arrow move',
		),
		'arrow_carrot-up'           => array(
			'content' => "\\32",
			'title'   => 'Arrow carrot-up',
		),
		'arrow_carrot-down'         => array(
			'content' => "\\33",
			'title'   => 'Arrow carrot-down',
		),
		'arrow_carrot-left'         => array(
			'content' => "\\34",
			'title'   => 'Arrow carrot-left',
		),
		'arrow_carrot-right'        => array(
			'content' => "\\35",
			'title'   => 'Arrow carrot-right',
		),
		'arrow_carrot-2up'          => array(
			'content' => "\\36",
			'title'   => 'Arrow carrot-2up',
		),
		'arrow_carrot-2down'        => array(
			'content' => "\\37",
			'title'   => 'Arrow carrot-2down',
		),
		'arrow_carrot-2left'        => array(
			'content' => "\\38",
			'title'   => 'Arrow carrot-2left',
		),
		'arrow_carrot-2right'       => array(
			'content' => "\\39",
			'title'   => 'Arrow carrot-2right',
		),
		'arrow_carrot-up_alt2'      => array(
			'content' => "\\3a",
			'title'   => 'Arrow carrot-up_alt2',
		),
		'arrow_carrot-down_alt2'    => array(
			'content' => "\\3b",
			'title'   => 'Arrow carrot-down_alt2',
		),
		'arrow_carrot-left_alt2'    => array(
			'content' => "\\3c",
			'title'   => 'Arrow carrot-left_alt2',
		),
		'arrow_carrot-right_alt2'   => array(
			'content' => "\\3d",
			'title'   => 'Arrow carrot-right_alt2',
		),
		'arrow_carrot-2up_alt2'     => array(
			'content' => "\\3e",
			'title'   => 'Arrow carrot-2up_alt2',
		),
		'arrow_carrot-2down_alt2'   => array(
			'content' => "\\3f",
			'title'   => 'Arrow carrot-2down_alt2',
		),
		'arrow_carrot-2left_alt2'   => array(
			'content' => "\\40",
			'title'   => 'Arrow carrot-2left_alt2',
		),
		'arrow_carrot-2right_alt2'  => array(
			'content' => "\\41",
			'title'   => 'Arrow carrot-2right_alt2',
		),
		'arrow_triangle-up'         => array(
			'content' => "\\42",
			'title'   => 'Arrow triangle-up',
		),
		'arrow_triangle-down'       => array(
			'content' => "\\43",
			'title'   => 'Arrow triangle-down',
		),
		'arrow_triangle-left'       => array(
			'content' => "\\44",
			'title'   => 'Arrow triangle-left',
		),
		'arrow_triangle-right'      => array(
			'content' => "\\45",
			'title'   => 'Arrow triangle-right',
		),
		'arrow_triangle-up_alt2'    => array(
			'content' => "\\46",
			'title'   => 'Arrow triangle-up_alt2',
		),
		'arrow_triangle-down_alt2'  => array(
			'content' => "\\47",
			'title'   => 'Arrow triangle-down_alt2',
		),
		'arrow_triangle-left_alt2'  => array(
			'content' => "\\48",
			'title'   => 'Arrow triangle-left_alt2',
		),
		'arrow_triangle-right_alt2' => array(
			'content' => "\\49",
			'title'   => 'Arrow triangle-right_alt2',
		),
		'arrow_back'                => array(
			'content' => "\\4a",
			'title'   => 'Arrow back',
		),
		'icon_minus-06'             => array(
			'content' => "\\4b",
			'title'   => 'Icon minus-06',
		),
		'icon_plus'                 => array(
			'content' => "\\4c",
			'title'   => 'Icon plus',
		),
		'icon_close'                => array(
			'content' => "\\4d",
			'title'   => 'Icon close',
		),
		'icon_check'                => array(
			'content' => "\\4e",
			'title'   => 'Icon check',
		),
		'icon_minus_alt2'           => array(
			'content' => "\\4f",
			'title'   => 'Icon minus_alt2',
		),
		'icon_plus_alt2'            => array(
			'content' => "\\50",
			'title'   => 'Icon plus_alt2',
		),
		'icon_close_alt2'           => array(
			'content' => "\\51",
			'title'   => 'Icon close_alt2',
		),
		'icon_check_alt2'           => array(
			'content' => "\\52",
			'title'   => 'Icon check_alt2',
		),
		'icon_zoom-out_alt'         => array(
			'content' => "\\53",
			'title'   => 'Icon zoom-out_alt',
		),
		'icon_zoom-in_alt'          => array(
			'content' => "\\54",
			'title'   => 'Icon zoom-in_alt',
		),
		'icon_search'               => array(
			'content' => "\\55",
			'title'   => 'Icon search',
		),
		'icon_box-empty'            => array(
			'content' => "\\56",
			'title'   => 'Icon box-empty',
		),
		'icon_box-selected'         => array(
			'content' => "\\57",
			'title'   => 'Icon box-selected',
		),
		'icon_minus-box'            => array(
			'content' => "\\58",
			'title'   => 'Icon minus-box',
		),
		'icon_plus-box'             => array(
			'content' => "\\59",
			'title'   => 'Icon plus-box',
		),
		'icon_box-checked'          => array(
			'content' => "\\5a",
			'title'   => 'Icon box-checked',
		),
		'icon_circle-empty'         => array(
			'content' => "\\5b",
			'title'   => 'Icon circle-empty',
		),
		'icon_circle-slelected'     => array(
			'content' => "\\5c",
			'title'   => 'Icon circle-slelected',
		),
		'icon_stop_alt2'            => array(
			'content' => "\\5d",
			'title'   => 'Icon stop_alt2',
		),
		'icon_stop'                 => array(
			'content' => "\\5e",
			'title'   => 'Icon stop',
		),
		'icon_pause_alt2'           => array(
			'content' => "\\5f",
			'title'   => 'Icon pause_alt2',
		),
		'icon_pause'                => array(
			'content' => "\\60",
			'title'   => 'Icon pause',
		),
		'icon_menu'                 => array(
			'content' => "\\61",
			'title'   => 'Icon menu',
		),
		'icon_menu-square_alt2'     => array(
			'content' => "\\62",
			'title'   => 'Icon menu-square_alt2',
		),
		'icon_menu-circle_alt2'     => array(
			'content' => "\\63",
			'title'   => 'Icon menu-circle_alt2',
		),
		'icon_ul'                   => array(
			'content' => "\\64",
			'title'   => 'Icon ul',
		),
		'icon_ol'                   => array(
			'content' => "\\65",
			'title'   => 'Icon ol',
		),
		'icon_adjust-horiz'         => array(
			'content' => "\\66",
			'title'   => 'Icon adjust-horiz',
		),
		'icon_adjust-vert'          => array(
			'content' => "\\67",
			'title'   => 'Icon adjust-vert',
		),
		'icon_document_alt'         => array(
			'content' => "\\68",
			'title'   => 'Icon document_alt',
		),
		'icon_documents_alt'        => array(
			'content' => "\\69",
			'title'   => 'Icon documents_alt',
		),
		'icon_pencil'               => array(
			'content' => "\\6a",
			'title'   => 'Icon pencil',
		),
		'icon_pencil-edit_alt'      => array(
			'content' => "\\6b",
			'title'   => 'Icon pencil-edit_alt',
		),
		'icon_pencil-edit'          => array(
			'content' => "\\6c",
			'title'   => 'Icon pencil-edit',
		),
		'icon_folder-alt'           => array(
			'content' => "\\6d",
			'title'   => 'Icon folder-alt',
		),
		'icon_folder-open_alt'      => array(
			'content' => "\\6e",
			'title'   => 'Icon folder-open_alt',
		),
		'icon_folder-add_alt'       => array(
			'content' => "\\6f",
			'title'   => 'Icon folder-add_alt',
		),
		'icon_info_alt'             => array(
			'content' => "\\70",
			'title'   => 'Icon info_alt',
		),
		'icon_error-oct_alt'        => array(
			'content' => "\\71",
			'title'   => 'Icon error-oct_alt',
		),
		'icon_error-circle_alt'     => array(
			'content' => "\\72",
			'title'   => 'Icon error-circle_alt',
		),
		'icon_error-triangle_alt'   => array(
			'content' => "\\73",
			'title'   => 'Icon error-triangle_alt',
		),
		'icon_question_alt2'        => array(
			'content' => "\\74",
			'title'   => 'Icon question_alt2',
		),
		'icon_question'             => array(
			'content' => "\\75",
			'title'   => 'Icon question',
		),
		'icon_comment_alt'          => array(
			'content' => "\\76",
			'title'   => 'Icon comment_alt',
		),
		'icon_chat_alt'             => array(
			'content' => "\\77",
			'title'   => 'Icon chat_alt',
		),
		'icon_vol-mute_alt'         => array(
			'content' => "\\78",
			'title'   => 'Icon vol-mute_alt',
		),
		'icon_volume-low_alt'       => array(
			'content' => "\\79",
			'title'   => 'Icon volume-low_alt',
		),
		'icon_volume-high_alt'      => array(
			'content' => "\\7a",
			'title'   => 'Icon volume-high_alt',
		),
		'icon_quotations'           => array(
			'content' => "\\7b",
			'title'   => 'Icon quotations',
		),
		'icon_quotations_alt2'      => array(
			'content' => "\\7c",
			'title'   => 'Icon quotations_alt2',
		),
		'icon_clock_alt'            => array(
			'content' => "\\7d",
			'title'   => 'Icon clock_alt',
		),
		'icon_lock_alt'             => array(
			'content' => "\\7e",
			'title'   => 'Icon lock_alt',
		),
		'icon_lock-open_alt'        => array(
			'content' => "\\e000",
			'title'   => 'Icon lock-open_alt',
		),
		'icon_key_alt'              => array(
			'content' => "\\e001",
			'title'   => 'Icon key_alt',
		),
		'icon_cloud_alt'            => array(
			'content' => "\\e002",
			'title'   => 'Icon cloud_alt',
		),
		'icon_cloud-upload_alt'     => array(
			'content' => "\\e003",
			'title'   => 'Icon cloud-upload_alt',
		),
		'icon_cloud-download_alt'   => array(
			'content' => "\\e004",
			'title'   => 'Icon cloud-download_alt',
		),
		'icon_image'                => array(
			'content' => "\\e005",
			'title'   => 'Icon image',
		),
		'icon_images'               => array(
			'content' => "\\e006",
			'title'   => 'Icon images',
		),
		'icon_lightbulb_alt'        => array(
			'content' => "\\e007",
			'title'   => 'Icon lightbulb_alt',
		),
		'icon_gift_alt'             => array(
			'content' => "\\e008",
			'title'   => 'Icon gift_alt',
		),
		'icon_house_alt'            => array(
			'content' => "\\e009",
			'title'   => 'Icon house_alt',
		),
		'icon_genius'               => array(
			'content' => "\\e00a",
			'title'   => 'Icon genius',
		),
		'icon_mobile'               => array(
			'content' => "\\e00b",
			'title'   => 'Icon mobile',
		),
		'icon_tablet'               => array(
			'content' => "\\e00c",
			'title'   => 'Icon tablet',
		),
		'icon_laptop'               => array(
			'content' => "\\e00d",
			'title'   => 'Icon laptop',
		),
		'icon_desktop'              => array(
			'content' => "\\e00e",
			'title'   => 'Icon desktop',
		),
		'icon_camera_alt'           => array(
			'content' => "\\e00f",
			'title'   => 'Icon camera_alt',
		),
		'icon_mail_alt'             => array(
			'content' => "\\e010",
			'title'   => 'Icon mail_alt',
		),
		'icon_cone_alt'             => array(
			'content' => "\\e011",
			'title'   => 'Icon cone_alt',
		),
		'icon_ribbon_alt'           => array(
			'content' => "\\e012",
			'title'   => 'Icon ribbon_alt',
		),
		'icon_bag_alt'              => array(
			'content' => "\\e013",
			'title'   => 'Icon bag_alt',
		),
		'icon_creditcard'           => array(
			'content' => "\\e014",
			'title'   => 'Icon creditcard',
		),
		'icon_cart_alt'             => array(
			'content' => "\\e015",
			'title'   => 'Icon cart_alt',
		),
		'icon_paperclip'            => array(
			'content' => "\\e016",
			'title'   => 'Icon paperclip',
		),
		'icon_tag_alt'              => array(
			'content' => "\\e017",
			'title'   => 'Icon tag_alt',
		),
		'icon_tags_alt'             => array(
			'content' => "\\e018",
			'title'   => 'Icon tags_alt',
		),
		'icon_trash_alt'            => array(
			'content' => "\\e019",
			'title'   => 'Icon trash_alt',
		),
		'icon_cursor_alt'           => array(
			'content' => "\\e01a",
			'title'   => 'Icon cursor_alt',
		),
		'icon_mic_alt'              => array(
			'content' => "\\e01b",
			'title'   => 'Icon mic_alt',
		),
		'icon_compass_alt'          => array(
			'content' => "\\e01c",
			'title'   => 'Icon compass_alt',
		),
		'icon_pin_alt'              => array(
			'content' => "\\e01d",
			'title'   => 'Icon pin_alt',
		),
		'icon_pushpin_alt'          => array(
			'content' => "\\e01e",
			'title'   => 'Icon pushpin_alt',
		),
		'icon_map_alt'              => array(
			'content' => "\\e01f",
			'title'   => 'Icon map_alt',
		),
		'icon_drawer_alt'           => array(
			'content' => "\\e020",
			'title'   => 'Icon drawer_alt',
		),
		'icon_toolbox_alt'          => array(
			'content' => "\\e021",
			'title'   => 'Icon toolbox_alt',
		),
		'icon_book_alt'             => array(
			'content' => "\\e022",
			'title'   => 'Icon book_alt',
		),
		'icon_calendar'             => array(
			'content' => "\\e023",
			'title'   => 'Icon calendar',
		),
		'icon_film'                 => array(
			'content' => "\\e024",
			'title'   => 'Icon film',
		),
		'icon_table'                => array(
			'content' => "\\e025",
			'title'   => 'Icon table',
		),
		'icon_contacts_alt'         => array(
			'content' => "\\e026",
			'title'   => 'Icon contacts_alt',
		),
		'icon_headphones'           => array(
			'content' => "\\e027",
			'title'   => 'Icon headphones',
		),
		'icon_lifesaver'            => array(
			'content' => "\\e028",
			'title'   => 'Icon lifesaver',
		),
		'icon_piechart'             => array(
			'content' => "\\e029",
			'title'   => 'Icon piechart',
		),
		'icon_refresh'              => array(
			'content' => "\\e02a",
			'title'   => 'Icon refresh',
		),
		'icon_link_alt'             => array(
			'content' => "\\e02b",
			'title'   => 'Icon link_alt',
		),
		'icon_link'                 => array(
			'content' => "\\e02c",
			'title'   => 'Icon link',
		),
		'icon_loading'              => array(
			'content' => "\\e02d",
			'title'   => 'Icon loading',
		),
		'icon_blocked'              => array(
			'content' => "\\e02e",
			'title'   => 'Icon blocked',
		),
		'icon_archive_alt'          => array(
			'content' => "\\e02f",
			'title'   => 'Icon archive_alt',
		),
		'icon_heart_alt'            => array(
			'content' => "\\e030",
			'title'   => 'Icon heart_alt',
		),
		'icon_star_alt'             => array(
			'content' => "\\e031",
			'title'   => 'Icon star_alt',
		),
		'icon_star-half_alt'        => array(
			'content' => "\\e032",
			'title'   => 'Icon star-half_alt',
		),
		'icon_star'                 => array(
			'content' => "\\e033",
			'title'   => 'Icon star',
		),
		'icon_star-half'            => array(
			'content' => "\\e034",
			'title'   => 'Icon star-half',
		),
		'icon_tools'                => array(
			'content' => "\\e035",
			'title'   => 'Icon tools',
		),
		'icon_tool'                 => array(
			'content' => "\\e036",
			'title'   => 'Icon tool',
		),
		'icon_cog'                  => array(
			'content' => "\\e037",
			'title'   => 'Icon cog',
		),
		'icon_cogs'                 => array(
			'content' => "\\e038",
			'title'   => 'Icon cogs',
		),
		'arrow_up_alt'              => array(
			'content' => "\\e039",
			'title'   => 'Arrow up_alt',
		),
		'arrow_down_alt'            => array(
			'content' => "\\e03a",
			'title'   => 'Arrow down_alt',
		),
		'arrow_left_alt'            => array(
			'content' => "\\e03b",
			'title'   => 'Arrow left_alt',
		),
		'arrow_right_alt'           => array(
			'content' => "\\e03c",
			'title'   => 'Arrow right_alt',
		),
		'arrow_left-up_alt'         => array(
			'content' => "\\e03d",
			'title'   => 'Arrow left-up_alt',
		),
		'arrow_right-up_alt'        => array(
			'content' => "\\e03e",
			'title'   => 'Arrow right-up_alt',
		),
		'arrow_right-down_alt'      => array(
			'content' => "\\e03f",
			'title'   => 'Arrow right-down_alt',
		),
		'arrow_left-down_alt'       => array(
			'content' => "\\e040",
			'title'   => 'Arrow left-down_alt',
		),
		'arrow_condense_alt'        => array(
			'content' => "\\e041",
			'title'   => 'Arrow condense_alt',
		),
		'arrow_expand_alt3'         => array(
			'content' => "\\e042",
			'title'   => 'Arrow expand_alt3',
		),
		'arrow_carrot_up_alt'       => array(
			'content' => "\\e043",
			'title'   => 'Arrow carrot_up_alt',
		),
		'arrow_carrot-down_alt'     => array(
			'content' => "\\e044",
			'title'   => 'Arrow carrot-down_alt',
		),
		'arrow_carrot-left_alt'     => array(
			'content' => "\\e045",
			'title'   => 'Arrow carrot-left_alt',
		),
		'arrow_carrot-right_alt'    => array(
			'content' => "\\e046",
			'title'   => 'Arrow carrot-right_alt',
		),
		'arrow_carrot-2up_alt'      => array(
			'content' => "\\e047",
			'title'   => 'Arrow carrot-2up_alt',
		),
		'arrow_carrot-2dwnn_alt'    => array(
			'content' => "\\e048",
			'title'   => 'Arrow carrot-2dwnn_alt',
		),
		'arrow_carrot-2left_alt'    => array(
			'content' => "\\e049",
			'title'   => 'Arrow carrot-2left_alt',
		),
		'arrow_carrot-2right_alt'   => array(
			'content' => "\\e04a",
			'title'   => 'Arrow carrot-2right_alt',
		),
		'arrow_triangle-up_alt'     => array(
			'content' => "\\e04b",
			'title'   => 'Arrow triangle-up_alt',
		),
		'arrow_triangle-down_alt'   => array(
			'content' => "\\e04c",
			'title'   => 'Arrow triangle-down_alt',
		),
		'arrow_triangle-left_alt'   => array(
			'content' => "\\e04d",
			'title'   => 'Arrow triangle-left_alt',
		),
		'arrow_triangle-right_alt'  => array(
			'content' => "\\e04e",
			'title'   => 'Arrow triangle-right_alt',
		),
		'icon_minus_alt'            => array(
			'content' => "\\e04f",
			'title'   => 'Icon minus_alt',
		),
		'icon_plus_alt'             => array(
			'content' => "\\e050",
			'title'   => 'Icon plus_alt',
		),
		'icon_close_alt'            => array(
			'content' => "\\e051",
			'title'   => 'Icon close_alt',
		),
		'icon_check_alt'            => array(
			'content' => "\\e052",
			'title'   => 'Icon check_alt',
		),
		'icon_zoom-out'             => array(
			'content' => "\\e053",
			'title'   => 'Icon zoom-out',
		),
		'icon_zoom-in'              => array(
			'content' => "\\e054",
			'title'   => 'Icon zoom-in',
		),
		'icon_stop_alt'             => array(
			'content' => "\\e055",
			'title'   => 'Icon stop_alt',
		),
		'icon_menu-square_alt'      => array(
			'content' => "\\e056",
			'title'   => 'Icon menu-square_alt',
		),
		'icon_menu-circle_alt'      => array(
			'content' => "\\e057",
			'title'   => 'Icon menu-circle_alt',
		),
		'icon_document'             => array(
			'content' => "\\e058",
			'title'   => 'Icon document',
		),
		'icon_documents'            => array(
			'content' => "\\e059",
			'title'   => 'Icon documents',
		),
		'icon_pencil_alt'           => array(
			'content' => "\\e05a",
			'title'   => 'Icon pencil_alt',
		),
		'icon_folder'               => array(
			'content' => "\\e05b",
			'title'   => 'Icon folder',
		),
		'icon_folder-open'          => array(
			'content' => "\\e05c",
			'title'   => 'Icon folder-open',
		),
		'icon_folder-add'           => array(
			'content' => "\\e05d",
			'title'   => 'Icon folder-add',
		),
		'icon_folder_upload'        => array(
			'content' => "\\e05e",
			'title'   => 'Icon folder_upload',
		),
		'icon_folder_download'      => array(
			'content' => "\\e05f",
			'title'   => 'Icon folder_download',
		),
		'icon_info'                 => array(
			'content' => "\\e060",
			'title'   => 'Icon info',
		),
		'icon_error-circle'         => array(
			'content' => "\\e061",
			'title'   => 'Icon error-circle',
		),
		'icon_error-oct'            => array(
			'content' => "\\e062",
			'title'   => 'Icon error-oct',
		),
		'icon_error-triangle'       => array(
			'content' => "\\e063",
			'title'   => 'Icon error-triangle',
		),
		'icon_question_alt'         => array(
			'content' => "\\e064",
			'title'   => 'Icon question_alt',
		),
		'icon_comment'              => array(
			'content' => "\\e065",
			'title'   => 'Icon comment',
		),
		'icon_chat'                 => array(
			'content' => "\\e066",
			'title'   => 'Icon chat',
		),
		'icon_vol-mute'             => array(
			'content' => "\\e067",
			'title'   => 'Icon vol-mute',
		),
		'icon_volume-low'           => array(
			'content' => "\\e068",
			'title'   => 'Icon volume-low',
		),
		'icon_volume-high'          => array(
			'content' => "\\e069",
			'title'   => 'Icon volume-high',
		),
		'icon_quotations_alt'       => array(
			'content' => "\\e06a",
			'title'   => 'Icon quotations_alt',
		),
		'icon_clock'                => array(
			'content' => "\\e06b",
			'title'   => 'Icon clock',
		),
		'icon_lock'                 => array(
			'content' => "\\e06c",
			'title'   => 'Icon lock',
		),
		'icon_lock-open'            => array(
			'content' => "\\e06d",
			'title'   => 'Icon lock-open',
		),
		'icon_key'                  => array(
			'content' => "\\e06e",
			'title'   => 'Icon key',
		),
		'icon_cloud'                => array(
			'content' => "\\e06f",
			'title'   => 'Icon cloud',
		),
		'icon_cloud-upload'         => array(
			'content' => "\\e070",
			'title'   => 'Icon cloud-upload',
		),
		'icon_cloud-download'       => array(
			'content' => "\\e071",
			'title'   => 'Icon cloud-download',
		),
		'icon_lightbulb'            => array(
			'content' => "\\e072",
			'title'   => 'Icon lightbulb',
		),
		'icon_gift'                 => array(
			'content' => "\\e073",
			'title'   => 'Icon gift',
		),
		'icon_house'                => array(
			'content' => "\\e074",
			'title'   => 'Icon house',
		),
		'icon_camera'               => array(
			'content' => "\\e075",
			'title'   => 'Icon camera',
		),
		'icon_mail'                 => array(
			'content' => "\\e076",
			'title'   => 'Icon mail',
		),
		'icon_cone'                 => array(
			'content' => "\\e077",
			'title'   => 'Icon cone',
		),
		'icon_ribbon'               => array(
			'content' => "\\e078",
			'title'   => 'Icon ribbon',
		),
		'icon_bag'                  => array(
			'content' => "\\e079",
			'title'   => 'Icon bag',
		),
		'icon_cart'                 => array(
			'content' => "\\e07a",
			'title'   => 'Icon cart',
		),
		'icon_tag'                  => array(
			'content' => "\\e07b",
			'title'   => 'Icon tag',
		),
		'icon_tags'                 => array(
			'content' => "\\e07c",
			'title'   => 'Icon tags',
		),
		'icon_trash'                => array(
			'content' => "\\e07d",
			'title'   => 'Icon trash',
		),
		'icon_cursor'               => array(
			'content' => "\\e07e",
			'title'   => 'Icon cursor',
		),
		'icon_mic'                  => array(
			'content' => "\\e07f",
			'title'   => 'Icon mic',
		),
		'icon_compass'              => array(
			'content' => "\\e080",
			'title'   => 'Icon compass',
		),
		'icon_pin'                  => array(
			'content' => "\\e081",
			'title'   => 'Icon pin',
		),
		'icon_pushpin'              => array(
			'content' => "\\e082",
			'title'   => 'Icon pushpin',
		),
		'icon_map'                  => array(
			'content' => "\\e083",
			'title'   => 'Icon map',
		),
		'icon_drawer'               => array(
			'content' => "\\e084",
			'title'   => 'Icon drawer',
		),
		'icon_toolbox'              => array(
			'content' => "\\e085",
			'title'   => 'Icon toolbox',
		),
		'icon_book'                 => array(
			'content' => "\\e086",
			'title'   => 'Icon book',
		),
		'icon_contacts'             => array(
			'content' => "\\e087",
			'title'   => 'Icon contacts',
		),
		'icon_archive'              => array(
			'content' => "\\e088",
			'title'   => 'Icon archive',
		),
		'icon_heart'                => array(
			'content' => "\\e089",
			'title'   => 'Icon heart',
		),
		'icon_profile'              => array(
			'content' => "\\e08a",
			'title'   => 'Icon profile',
		),
		'icon_group'                => array(
			'content' => "\\e08b",
			'title'   => 'Icon group',
		),
		'icon_grid-2x2'             => array(
			'content' => "\\e08c",
			'title'   => 'Icon grid-2x2',
		),
		'icon_grid-3x3'             => array(
			'content' => "\\e08d",
			'title'   => 'Icon grid-3x3',
		),
		'icon_music'                => array(
			'content' => "\\e08e",
			'title'   => 'Icon music',
		),
		'icon_pause_alt'            => array(
			'content' => "\\e08f",
			'title'   => 'Icon pause_alt',
		),
		'icon_phone'                => array(
			'content' => "\\e090",
			'title'   => 'Icon phone',
		),
		'icon_upload'               => array(
			'content' => "\\e091",
			'title'   => 'Icon upload',
		),
		'icon_download'             => array(
			'content' => "\\e092",
			'title'   => 'Icon download',
		),
		'social_facebook'           => array(
			'content' => "\\e093",
			'title'   => 'Social facebook',
		),
		'social_twitter'            => array(
			'content' => "\\e094",
			'title'   => 'Social twitter',
		),
		'social_pinterest'          => array(
			'content' => "\\e095",
			'title'   => 'Social pinterest',
		),
		'social_googleplus'         => array(
			'content' => "\\e096",
			'title'   => 'Social googleplus',
		),
		'social_tumblr'             => array(
			'content' => "\\e097",
			'title'   => 'Social tumblr',
		),
		'social_tumbleupon'         => array(
			'content' => "\\e098",
			'title'   => 'Social tumbleupon',
		),
		'social_wordpress'          => array(
			'content' => "\\e099",
			'title'   => 'Social wordpress',
		),
		'social_instagram'          => array(
			'content' => "\\e09a",
			'title'   => 'Social instagram',
		),
		'social_dribbble'           => array(
			'content' => "\\e09b",
			'title'   => 'Social dribbble',
		),
		'social_vimeo'              => array(
			'content' => "\\e09c",
			'title'   => 'Social vimeo',
		),
		'social_linkedin'           => array(
			'content' => "\\e09d",
			'title'   => 'Social linkedin',
		),
		'social_rss'                => array(
			'content' => "\\e09e",
			'title'   => 'Social rss',
		),
		'social_deviantart'         => array(
			'content' => "\\e09f",
			'title'   => 'Social deviantart',
		),
		'social_share'              => array(
			'content' => "\\e0a0",
			'title'   => 'Social share',
		),
		'social_myspace'            => array(
			'content' => "\\e0a1",
			'title'   => 'Social myspace',
		),
		'social_skype'              => array(
			'content' => "\\e0a2",
			'title'   => 'Social skype',
		),
		'social_youtube'            => array(
			'content' => "\\e0a3",
			'title'   => 'Social youtube',
		),
		'social_picassa'            => array(
			'content' => "\\e0a4",
			'title'   => 'Social picassa',
		),
		'social_googledrive'        => array(
			'content' => "\\e0a5",
			'title'   => 'Social googledrive',
		),
		'social_flickr'             => array(
			'content' => "\\e0a6",
			'title'   => 'Social flickr',
		),
		'social_blogger'            => array(
			'content' => "\\e0a7",
			'title'   => 'Social blogger',
		),
		'social_spotify'            => array(
			'content' => "\\e0a8",
			'title'   => 'Social spotify',
		),
		'social_delicious'          => array(
			'content' => "\\e0a9",
			'title'   => 'Social delicious',
		),
		'social_facebook_circle'    => array(
			'content' => "\\e0aa",
			'title'   => 'Social facebook_circle',
		),
		'social_twitter_circle'     => array(
			'content' => "\\e0ab",
			'title'   => 'Social twitter_circle',
		),
		'social_pinterest_circle'   => array(
			'content' => "\\e0ac",
			'title'   => 'Social pinterest_circle',
		),
		'social_googleplus_circle'  => array(
			'content' => "\\e0ad",
			'title'   => 'Social googleplus_circle',
		),
		'social_tumblr_circle'      => array(
			'content' => "\\e0ae",
			'title'   => 'Social tumblr_circle',
		),
		'social_stumbleupon_circle' => array(
			'content' => "\\e0af",
			'title'   => 'Social stumbleupon_circle',
		),
		'social_wordpress_circle'   => array(
			'content' => "\\e0b0",
			'title'   => 'Social wordpress_circle',
		),
		'social_instagram_circle'   => array(
			'content' => "\\e0b1",
			'title'   => 'Social instagram_circle',
		),
		'social_dribbble_circle'    => array(
			'content' => "\\e0b2",
			'title'   => 'Social dribbble_circle',
		),
		'social_vimeo_circle'       => array(
			'content' => "\\e0b3",
			'title'   => 'Social vimeo_circle',
		),
		'social_linkedin_circle'    => array(
			'content' => "\\e0b4",
			'title'   => 'Social linkedin_circle',
		),
		'social_rss_circle'         => array(
			'content' => "\\e0b5",
			'title'   => 'Social rss_circle',
		),
		'social_deviantart_circle'  => array(
			'content' => "\\e0b6",
			'title'   => 'Social deviantart_circle',
		),
		'social_share_circle'       => array(
			'content' => "\\e0b7",
			'title'   => 'Social share_circle',
		),
		'social_myspace_circle'     => array(
			'content' => "\\e0b8",
			'title'   => 'Social myspace_circle',
		),
		'social_skype_circle'       => array(
			'content' => "\\e0b9",
			'title'   => 'Social skype_circle',
		),
		'social_youtube_circle'     => array(
			'content' => "\\e0ba",
			'title'   => 'Social youtube_circle',
		),
		'social_picassa_circle'     => array(
			'content' => "\\e0bb",
			'title'   => 'Social picassa_circle',
		),
		'social_googledrive_alt2'   => array(
			'content' => "\\e0bc",
			'title'   => 'Social googledrive_alt2',
		),
		'social_flickr_circle'      => array(
			'content' => "\\e0bd",
			'title'   => 'Social flickr_circle',
		),
		'social_blogger_circle'     => array(
			'content' => "\\e0be",
			'title'   => 'Social blogger_circle',
		),
		'social_spotify_circle'     => array(
			'content' => "\\e0bf",
			'title'   => 'Social spotify_circle',
		),
		'social_delicious_circle'   => array(
			'content' => "\\e0c0",
			'title'   => 'Social delicious_circle',
		),
		'social_facebook_square'    => array(
			'content' => "\\e0c1",
			'title'   => 'Social facebook_square',
		),
		'social_twitter_square'     => array(
			'content' => "\\e0c2",
			'title'   => 'Social twitter_square',
		),
		'social_pinterest_square'   => array(
			'content' => "\\e0c3",
			'title'   => 'Social pinterest_square',
		),
		'social_googleplus_square'  => array(
			'content' => "\\e0c4",
			'title'   => 'Social googleplus_square',
		),
		'social_tumblr_square'      => array(
			'content' => "\\e0c5",
			'title'   => 'Social tumblr_square',
		),
		'social_stumbleupon_square' => array(
			'content' => "\\e0c6",
			'title'   => 'Social stumbleupon_square',
		),
		'social_wordpress_square'   => array(
			'content' => "\\e0c7",
			'title'   => 'Social wordpress_square',
		),
		'social_instagram_square'   => array(
			'content' => "\\e0c8",
			'title'   => 'Social instagram_square',
		),
		'social_dribbble_square'    => array(
			'content' => "\\e0c9",
			'title'   => 'Social dribbble_square',
		),
		'social_vimeo_square'       => array(
			'content' => "\\e0ca",
			'title'   => 'Social vimeo_square',
		),
		'social_linkedin_square'    => array(
			'content' => "\\e0cb",
			'title'   => 'Social linkedin_square',
		),
		'social_rss_square'         => array(
			'content' => "\\e0cc",
			'title'   => 'Social rss_square',
		),
		'social_deviantart_square'  => array(
			'content' => "\\e0cd",
			'title'   => 'Social deviantart_square',
		),
		'social_share_square'       => array(
			'content' => "\\e0ce",
			'title'   => 'Social share_square',
		),
		'social_myspace_square'     => array(
			'content' => "\\e0cf",
			'title'   => 'Social myspace_square',
		),
		'social_skype_square'       => array(
			'content' => "\\e0d0",
			'title'   => 'Social skype_square',
		),
		'social_youtube_square'     => array(
			'content' => "\\e0d1",
			'title'   => 'Social youtube_square',
		),
		'social_picassa_square'     => array(
			'content' => "\\e0d2",
			'title'   => 'Social picassa_square',
		),
		'social_googledrive_square' => array(
			'content' => "\\e0d3",
			'title'   => 'Social googledrive_square',
		),
		'social_flickr_square'      => array(
			'content' => "\\e0d4",
			'title'   => 'Social flickr_square',
		),
		'social_blogger_square'     => array(
			'content' => "\\e0d5",
			'title'   => 'Social blogger_square',
		),
		'social_spotify_square'     => array(
			'content' => "\\e0d6",
			'title'   => 'Social spotify_square',
		),
		'social_delicious_square'   => array(
			'content' => "\\e0d7",
			'title'   => 'Social delicious_square',
		),
		'icon_printer'              => array(
			'content' => "\\e103",
			'title'   => 'Icon printer',
		),
		'icon_calulator'            => array(
			'content' => "\\e0ee",
			'title'   => 'Icon calulator',
		),
		'icon_building'             => array(
			'content' => "\\e0ef",
			'title'   => 'Icon building',
		),
		'icon_floppy'               => array(
			'content' => "\\e0e8",
			'title'   => 'Icon floppy',
		),
		'icon_drive'                => array(
			'content' => "\\e0ea",
			'title'   => 'Icon drive',
		),
		'icon_search-2'             => array(
			'content' => "\\e101",
			'title'   => 'Icon search-2',
		),
		'icon_id'                   => array(
			'content' => "\\e107",
			'title'   => 'Icon id',
		),
		'icon_id-2'                 => array(
			'content' => "\\e108",
			'title'   => 'Icon id-2',
		),
		'icon_puzzle'               => array(
			'content' => "\\e102",
			'title'   => 'Icon puzzle',
		),
		'icon_like'                 => array(
			'content' => "\\e106",
			'title'   => 'Icon like',
		),
		'icon_dislike'              => array(
			'content' => "\\e0eb",
			'title'   => 'Icon dislike',
		),
		'icon_mug'                  => array(
			'content' => "\\e105",
			'title'   => 'Icon mug',
		),
		'icon_currency'             => array(
			'content' => "\\e0ed",
			'title'   => 'Icon currency',
		),
		'icon_wallet'               => array(
			'content' => "\\e100",
			'title'   => 'Icon wallet',
		),
		'icon_pens'                 => array(
			'content' => "\\e104",
			'title'   => 'Icon pens',
		),
		'icon_easel'                => array(
			'content' => "\\e0e9",
			'title'   => 'Icon easel',
		),
		'icon_flowchart'            => array(
			'content' => "\\e109",
			'title'   => 'Icon flowchart',
		),
		'icon_datareport'           => array(
			'content' => "\\e0ec",
			'title'   => 'Icon datareport',
		),
		'icon_briefcase'            => array(
			'content' => "\\e0fe",
			'title'   => 'Icon briefcase',
		),
		'icon_shield'               => array(
			'content' => "\\e0f6",
			'title'   => 'Icon shield',
		),
		'icon_percent'              => array(
			'content' => "\\e0fb",
			'title'   => 'Icon percent',
		),
		'icon_globe'                => array(
			'content' => "\\e0e2",
			'title'   => 'Icon globe',
		),
		'icon_globe-2'              => array(
			'content' => "\\e0e3",
			'title'   => 'Icon globe-2',
		),
		'icon_target'               => array(
			'content' => "\\e0f5",
			'title'   => 'Icon target',
		),
		'icon_hourglass'            => array(
			'content' => "\\e0e1",
			'title'   => 'Icon hourglass',
		),
		'icon_balance'              => array(
			'content' => "\\e0ff",
			'title'   => 'Icon balance',
		),
		'icon_rook'                 => array(
			'content' => "\\e0f8",
			'title'   => 'Icon rook',
		),
		'icon_printer-alt'          => array(
			'content' => "\\e0fa",
			'title'   => 'Icon printer-alt',
		),
		'icon_calculator_alt'       => array(
			'content' => "\\e0e7",
			'title'   => 'Icon calculator_alt',
		),
		'icon_building_alt'         => array(
			'content' => "\\e0fd",
			'title'   => 'Icon building_alt',
		),
		'icon_floppy_alt'           => array(
			'content' => "\\e0e4",
			'title'   => 'Icon floppy_alt',
		),
		'icon_drive_alt'            => array(
			'content' => "\\e0e5",
			'title'   => 'Icon drive_alt',
		),
		'icon_search_alt'           => array(
			'content' => "\\e0f7",
			'title'   => 'Icon search_alt',
		),
		'icon_id_alt'               => array(
			'content' => "\\e0e0",
			'title'   => 'Icon id_alt',
		),
		'icon_id-2_alt'             => array(
			'content' => "\\e0fc",
			'title'   => 'Icon id-2_alt',
		),
		'icon_puzzle_alt'           => array(
			'content' => "\\e0f9",
			'title'   => 'Icon puzzle_alt',
		),
		'icon_like_alt'             => array(
			'content' => "\\e0dd",
			'title'   => 'Icon like_alt',
		),
		'icon_dislike_alt'          => array(
			'content' => "\\e0f1",
			'title'   => 'Icon dislike_alt',
		),
		'icon_mug_alt'              => array(
			'content' => "\\e0dc",
			'title'   => 'Icon mug_alt',
		),
		'icon_currency_alt'         => array(
			'content' => "\\e0f3",
			'title'   => 'Icon currency_alt',
		),
		'icon_wallet_alt'           => array(
			'content' => "\\e0d8",
			'title'   => 'Icon wallet_alt',
		),
		'icon_pens_alt'             => array(
			'content' => "\\e0db",
			'title'   => 'Icon pens_alt',
		),
		'icon_easel_alt'            => array(
			'content' => "\\e0f0",
			'title'   => 'Icon easel_alt',
		),
		'icon_flowchart_alt'        => array(
			'content' => "\\e0df",
			'title'   => 'Icon flowchart_alt',
		),
		'icon_datareport_alt'       => array(
			'content' => "\\e0f2",
			'title'   => 'Icon datareport_alt',
		),
		'icon_briefcase_alt'        => array(
			'content' => "\\e0f4",
			'title'   => 'Icon briefcase_alt',
		),
		'icon_shield_alt'           => array(
			'content' => "\\e0d9",
			'title'   => 'Icon shield_alt',
		),
		'icon_percent_alt'          => array(
			'content' => "\\e0da",
			'title'   => 'Icon percent_alt',
		),
		'icon_globe_alt'            => array(
			'content' => "\\e0de",
			'title'   => 'Icon globe_alt',
		),
		'icon_clipboard'            => array(
			'content' => "\\e0e6",
			'title'   => 'Icon clipboard',
		),
	);

}

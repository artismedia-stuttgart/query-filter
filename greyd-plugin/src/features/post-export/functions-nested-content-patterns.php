<?php

if ( ! function_exists( 'get_nested_post_patterns' ) ) {

	/**
	 * Get the patterns to replace post ids in post_content.
	 * 
	 * @deprecated since 2.0: Post_Export::regex_nested_posts()
	 *
	 * @param int     $post_id      The WP_Post ID.
	 * @param WP_Post $post         The WP_Post Object
	 *
	 * @return array[] with the following structure:
	 *
	 * @property array  search      Regex around the ID to look for.
	 *                              Implodes with: '([\da-z\-\_]+?)'
	 *                              Slashes are automatically set around the regex.
	 * @property array  replace     Strings around the ID to replace by.
	 *                              Implodes with: '{{'.$post_id.'}}'
	 * @property string post_type   (optional) Post type of the found ID.
	 *                              If set and a post_name is found, it looks
	 *                              for the post-ID by post_name & post_type.
	 * @property int    group       (optional) Number of regex-group that contains
	 *                              the ID. Default: 2
	 */
	function get_nested_post_patterns( $post_id = 0, $post = null ) {

		/**
		 * Filter to customize regex patterns for finding nested posts in post content.
		 * 
		 * This filter allows developers to add custom regex patterns for detecting
		 * and replacing nested post references in post content during export.
		 * It's useful for supporting custom block types or content structures.
		 * 
		 * @filter greyd_regex_nested_posts
		 * 
		 * @param array   $patterns     Array of regex pattern arguments for post detection.
		 * @param int     $post_id      The WP_Post ID being exported.
		 * @param WP_Post $post         The WP_Post Object being exported.
		 * 
		 * @return array                Modified array of regex patterns for post detection.
		 */
		return (array) apply_filters(
			'greyd_regex_nested_posts',
			array(

				/**
				 * ----------------- Posttypes -----------------
				 */
	
				/**
				 * Find nested Templates
				 */
				'greyd/dynamic' => array(
					'search'    => array( '<!-- wp:greyd\/dynamic {(.*?)\"template\":\"', '\"' ),
					'replace'   => array( '<!-- wp:greyd/dynamic {$1"template":"', '"' ),
					'post_type' => 'dynamic_template',
				),
				'greyd/dynamic/post' => array(
					'search'    => array( '<!-- wp:greyd\/dynamic {(.*?)\"postId\":\"', '\"' ),
					'replace'   => array( '<!-- wp:greyd/dynamic {$1"postId":"', '"' ),
					'post_type' => 'any',
				),
	
				/**
				 * Find nested GREYD.Forms
				 */
				'greyd/form' => array(
					'search'    => array( '<!-- wp:greyd\/form {(.*?)\"id\":\"', '\"' ),
					'replace'   => array( '<!-- wp:greyd/form {$1"id":"', '"' ),
					'post_type' => 'tp_forms',
				),
				'dynamic/greyd/form' => array(
					'search'    => array( '{\"dkey\":\"id\",\"dtype\":\"dropdown_forms\",(.*?),\"dvalue\":\"', '\"}' ),
					'replace'   => array( '{"dkey":"id","dtype":"dropdown_forms",$1,"dvalue":"', '"}' ),
					'post_type' => 'tp_forms',
				),
	
				/**
				 * Find nested GREYD.Popups
				 */
				'greyd/popup' => array(
					'search'    => array( '\"trigger\":{\"type\":\"popup\",\"params\":\"', '\"' ),
					'replace'   => array( '"trigger":{"type":"popup","params":"', '"' ),
					'post_type' => 'greyd_popup',
					'group'     => 1
				),
				'dynamic/greyd/popup' => array(
					'search'    => array( '{\"dkey\":\"trigger\",\"dtype\":\"textfield\",(.*?),\"dvalue\":\"%7B%22type%22%3A%22popup%22%2C%22params%22%3A%22', '%22%7D' ),
					'replace'   => array( '{"dkey":"trigger","dtype":"textfield",$1,"dvalue":"%7B%22type%22%3A%22popup%22%2C%22params%22%3A%22', '%22%7D' ),
					'post_type' => 'greyd_popup',
				),
				'greyd/inline/popup' => array(
					'search'    => array( 'data-type=\"popup\" data-params=\"&quot;', '&quot;\"' ),
					'replace'   => array( 'data-type="popup" data-params="&quot;', '&quot;"' ),
					'post_type' => 'greyd_popup',
					'group'     => 1
				),
	
				/**
				 * Find nested Reusables & patterns
				 */
				'core/reusable' => array(
					'search'    => array( '<!-- wp:block {(.*?)\"ref\":', '}' ),
					'replace'   => array( '<!-- wp:block {$1"ref":', '}' ),
					'post_type' => 'wp_block'
				),
				'core/pattern' => array(
					'search'    => array( '<!-- wp:pattern {(.*?)\"slug\":\"', '\"' ),
					'replace'   => array( '<!-- wp:pattern {$1"slug":"', '"' ),
					'post_type' => 'wp_block'
				),
	
				/**
				 * Find nested wp-template-parts
				 */
				'core/template-part' => array(
					'search'    => array( '<!-- wp:template-part {(.*?)\"slug\":\"', '\"' ),
					'replace'   => array( '<!-- wp:template-part {$1"slug":"', '"' ),
					'post_type' => 'wp_template_part'
				),
	
				/**
				 * Find nested wp_navigation (navigation menus)
				 */
				'core/navigation' => array(
					'search'    => array( '<!-- wp:navigation {(.*?)\"ref\":', '}' ),
					'replace'   => array( '<!-- wp:navigation {$1"ref":', '}' ),
					'post_type' => 'wp_navigation'
				),
				'core/navigation-with-attributes' => array(
					'search'    => array( '<!-- wp:navigation {(.*?)\"ref\":', ',' ),
					'replace'   => array( '<!-- wp:navigation {$1"ref":', ',' ),
					'post_type' => 'wp_navigation'
				),
				'core/navigation-deprecated' => array(
					'search'    => array( '<!-- wp:navigation {(.*?)\"navigationMenuId\":', '}' ),
					'replace'   => array( '<!-- wp:navigation {$1"navigationMenuId":', '}' ),
					'post_type' => 'wp_navigation'
				),
				'core/navigation-deprecated-with-attributes' => array(
					'search'    => array( '<!-- wp:navigation {(.*?)\"navigationMenuId\":', ',' ),
					'replace'   => array( '<!-- wp:navigation {$1"navigationMenuId":', ',' ),
					'post_type' => 'wp_navigation'
				),
	
	
				/**
				 * ----------------- Media files -----------------
				 */
	
				/**
				 * Find nested images in core blocks.
				 * 
				 * Supported blocks: image, cover, media-text, file
				 */
				'core/image' => array(
					'search'    => array( '<!-- wp:image {(.*?)\"id\":', ',' ),
					'replace'   => array( '<!-- wp:image {$1"id":', ',' ),
					'post_type' => 'attachment',
				),
				'core/image-class' => array(
					'search'    => array( 'class=\"([^\"]*?)wp-image-', '(\s|\")' ),
					'replace'   => array( 'class="$1wp-image-', '$2' ),
					'post_type' => 'attachment',
				),
				'core/cover' => array(
					'search'    => array( '<!-- wp:cover {(.*?)\"id\":', ',' ),
					'replace'   => array( '<!-- wp:cover {$1"id":', ',' ),
					'post_type' => 'attachment',
				),
				'core/media-text ' => array(
					'search'    => array( '<!-- wp:media-text {(.*?)\"mediaId\":', ',' ),
					'replace'   => array( '<!-- wp:media-text {$1"mediaId":', ',' ),
					'post_type' => 'attachment',
				),
				'core/file' => array(
					'search'    => array( '<!-- wp:file {(.*?)\"id\":', ',' ),
					'replace'   => array( '<!-- wp:file {$1"id":', ',' ),
					'post_type' => 'attachment',
				),
	
				/**
				 * Find nested videos in core blocks.
				 * 
				 * Supported blocks: video
				 */
				'core/video' => array(
					'search'    => array( '<!-- wp:video {([^}]*?)"id":', ',' ),
					'replace'   => array( '<!-- wp:video {$1"id":', ',' ),
					'post_type' => 'attachment',
				),
				
				/**
				 * Find nested images in greyd blocks.
				 * 
				 * Supported blocks: image, list, box, row
				 */
				'greyd/image' => array(
					'search'    => array( '<!-- wp:greyd\/image {(.*?)"image":{(.*?)"id":', ',' ),
					'replace'   => array( '<!-- wp:greyd/image {$1"image":{$2"id":', ',' ),
					'post_type' => 'attachment',
					'group'     => 3
				),
				// list
				'greyd/list' => array(
					'search'    => array( '<!-- wp:greyd\/list {(.*?)\"icon\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd/list {$1"icon":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				'greyd/list-item' => array(
					'search'    => array( '<!-- wp:greyd\/list-item {(.*?)\"override\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd/list-item {$1"override":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				// box- & row-backgrounds
				'greyd/background' => array(
					'search'    => array( '"background":{(.*?)"image":{"id":', ',' ),
					'replace'   => array( '"background":{$1"image":{"id":', ',' ),
					'post_type' => 'attachment'
				),
				// row background overlay custom pattern
				'greyd/background-pattern' => array(
					'search'    => array( '"pattern":{(.*?)"id":', ',' ),
					'replace'   => array( '"pattern":{$1"id":', ',' ),
					'post_type' => 'attachment',
				),
	
				// hotspot-wrapper
				'greyd/hotspot-wrapper' => array(
					'search'    => array( '<!-- wp:greyd\/hotspot-wrapper {(.*?)\"image\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd/hotspot-wrapper {$1"image":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				// hotspot
				'greyd/hotspot' => array(
					'search'    => array( '<!-- wp:greyd\/hotspot {(.*?)\"hotspotImage\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd/hotspot {$1"hotspotImage":{"id":', ',' ),
					'post_type' => 'attachment',
				),
	
				/**
				 * Find nested images in dynamic contents.
				 * 
				 * Supported blocks: core/image, greyd/image, box, row
				 */
				'dynamic/core/image' => array(
					'search'    => array( '{\"dkey\":\"id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),
				'dynamic/greyd/image' => array(
					'search'    => array( '{\"dkey\":\"image\",\"dtype\":\"textfield\",(.*?),\"dvalue\":\"%7B([^\"]*?)%22id%22%3A', '%2C%22' ),
					'replace'   => array( '{"dkey":"image","dtype":"textfield",$1,"dvalue":"%7B$2%22id%22%3A', '%2C%22' ),
					'post_type' => 'attachment',
					'group'     => 3
				),
				'dynamic/greyd/background' => array(
					'search'    => array( '{\"dkey\":\"background\/image\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"background/image/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),
				// {"dkey":"mediaId","dtype":"file_picker_image","dtitle":"why-media2","dvalue":"3173"}
				'dynamic/core/media-text' => array(
					'search'    => array( '{\"dkey\":\"mediaId\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"mediaId","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),
	
				/**
				 * Find nested images in GREYD.Forms.
				 * 
				 * Supported blocks: iconpanel
				 */
				'greyd-forms/iconpanel-normal' => array(
					'search'    => array( '<!-- wp:greyd-forms\/iconpanel {(.*?)\"normal\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd-forms/iconpanel {$1"normal":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				'greyd-forms/iconpanel-hover' => array(
					'search'    => array( '<!-- wp:greyd-forms\/iconpanel {(.*?)\"hover\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd-forms/iconpanel {$1"hover":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				'greyd-forms/iconpanel-active' => array(
					'search'    => array( '<!-- wp:greyd-forms\/iconpanel {(.*?)\"active\":{\"id\":', ',' ),
					'replace'   => array( '<!-- wp:greyd-forms/iconpanel {$1"active":{"id":', ',' ),
					'post_type' => 'attachment',
				),
				/**
				 * Downloads
				*/
				'greyd/trigger-download' => array(
					'search'    => array( '\"trigger\":{\"type\":\"file\",\"params\":\"', '\"' ),
					'replace'   => array( '"trigger":{"type":"file","params":"', '"' ),
					'post_type' => 'attachment',
					'group'     => 1
				),

				/**
				 * Find nested images in core/columns dynamic fields
				 */
				'dynamic/core/columns-background-image' => array(
					'search'    => array( '{\"dkey\":\"background\/image\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"background/image/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment'
				),
				'dynamic/core/columns-background-animation' => array(
					'search'    => array( '{\"dkey\":\"background\/anim\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"background/anim/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),

				/**
				 * Find nested images in core/group dynamic fields
				 */
				'dynamic/core/group-background-image' => array(
					'search'    => array( '{\"dkey\":\"style\/background\/backgroundImage\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"style/background/backgroundImage/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),

				/**
				 * Find nested animations in greyd/anim dynamic fields
				 */
				'dynamic/greyd/anim' => array(
					'search'    => array( '{\"dkey\":\"id\",\"dtype\":\"file_picker_json\",(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"id","dtype":"file_picker_json",$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),

				/**
				 * Find nested images in greyd/box dynamic fields
				 */
				'dynamic/greyd/box-background-image' => array(
					'search'    => array( '{\"dkey\":\"background\/image\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"background/image/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),
				'dynamic/greyd/box-background-animation' => array(
					'search'    => array( '{\"dkey\":\"background\/anim\/id\",\"dtype\":\"file_picker(.*?),\"dvalue\":\"', '\"' ),
					'replace'   => array( '{"dkey":"background/anim/id","dtype":"file_picker$1,"dvalue":"', '"' ),
					'post_type' => 'attachment',
				),
			),
			$post_id,
			$post
		);
	}
}

if ( ! function_exists( 'get_nested_string_patterns' ) ) {
	/**
	 * Get all the strings to replace inside post_content.
	 * 
	 * @deprecated since 2.0: Post_Export::regex_nested_strings()
	 *
	 * @param string $subject   The string to query (usually post content).
	 * @param int    $post_id   The post ID.
	 *
	 * @return string[]
	 *
	 * Default:
	 * @property string upload_url              http://website.de/wp-content/uploads
	 * @property string upload_url_enc          http%3A%2F%2Fwebsite.de%2Fwp-content%2Fuploads
	 * @property string upload_url_enc_twice    http%253A%252F%252Fwebsite.de%252Fwp-content%252Fuploads
	 * @property string site_url                http://website.de/
	 * @property string site_url_enc            http%3A%2F%2Fwebsite.de%2F
	 * @property string site_url_enc_twice      http%253A%252F%252Fwebsite.de%252F
	 */
	function get_nested_string_patterns( $subject, $post_id ) {
		$site_url       = get_site_url();
		$site_url_enc   = urlencode( $site_url );
		$upload_url     = wp_upload_dir()['baseurl'];
		$upload_url_enc = urlencode( $upload_url );

		/**
		 * Filter to customize string replacement patterns for post content export.
		 * 
		 * This filter allows developers to add custom string patterns that should
		 * be replaced with placeholders during export. It's useful for handling
		 * site-specific URLs, paths, or other dynamic content.
		 * 
		 * @filter greyd_regex_nested_strings
		 * 
		 * @param string[]  $strings   Array of strings to be replaced, keyed by placeholder name.
		 * @param string    $content   The post content being processed.
		 * @param int       $post_id   The ID of the post being exported.
		 * 
		 * @return string[]            Modified array of strings to be replaced.
		 */
		return apply_filters(
			'greyd_regex_nested_strings',
			array(
				'upload_url'           => $upload_url,
				'upload_url_enc'       => $upload_url_enc,
				'upload_url_enc_twice' => urlencode( $upload_url_enc ),
				'site_url'             => $site_url,
				'site_url_enc'         => $site_url_enc,
				'site_url_enc_twice'   => urlencode( $site_url_enc ),
				'theme'                => '"'.get_stylesheet().'"'
			),
			$subject,
			$post_id
		);
	}
}

if ( ! function_exists( 'get_nested_term_patterns' ) ) {
	/**
	 * Get the patterns to replace term ids in post_content.
	 * 
	 * @deprecated since 2.0: Post_Export::regex_nested_terms()
	 *
	 * @param int     $post_id      The WP_Post ID.
	 * @param WP_Post $post         The WP_Post Object
	 *
	 * @return array[] @see regex_nested_posts() for details.
	 */
	function get_nested_term_patterns( $post_id, $post ) {
		
		/**
		 * Filter to customize regex patterns for finding nested terms in post content.
		 * 
		 * This filter allows developers to add custom regex patterns for detecting
		 * and replacing nested taxonomy term references in post content during export.
		 * It's useful for supporting custom term-based content structures.
		 * 
		 * @filter greyd_regex_nested_terms
		 * 
		 * @param array   $patterns     Array of regex pattern arguments for term detection.
		 * @param int     $post_id      The WP_Post ID being exported.
		 * @param WP_Post $post         The WP_Post Object being exported.
		 * 
		 * @return array                Modified array of regex patterns for term detection.
		 */
		return (array) apply_filters(
			'greyd_regex_nested_terms',
			array(
				/**
				 * The taxQuery regex did not work.
				 * @since 2.8.0 taxQuery terms are processed in Post_Export::prepare_nested_terms()
				 */
				// 'taxQuery' => array(
				// 	'search'    => array( '\"taxQuery\":\{([^\}]+)(\[|,)', '(\]|,)' ),
				// 	'replace'   => array( '"taxQuery":{$1$2', '$4' ),
				// 	'group'     => 3
				// ),
			),
			$post_id,
			$post
		);
	}
}

if ( ! function_exists( 'get_nested_menu_patterns' ) ) {
	/**
	 * Get the patterns to replace menu ids in post_content.
	 * 
	 * @deprecated since 2.0: Post_Export::regex_nested_menus()
	 *
	 * @param int     $post_id      The WP_Post ID.
	 * @param WP_Post $post         The WP_Post Object
	 *
	 * @return array[] @see regex_nested_posts() for details.
	 */
	function get_nested_menu_patterns( $post_id, $post ) {
		
		/**
		 * Filter to customize regex patterns for finding nested menus in post content.
		 * 
		 * This filter allows developers to add custom regex patterns for detecting
		 * and replacing nested menu references in post content during export.
		 * It's useful for supporting custom menu structures or shortcodes.
		 * 
		 * @filter greyd_regex_nested_menus
		 * 
		 * @param array   $patterns     Array of regex pattern arguments for menu detection.
		 * @param int     $post_id      The WP_Post ID being exported.
		 * @param WP_Post $post         The WP_Post Object being exported.
		 * 
		 * @return array                Modified array of regex patterns for menu detection.
		 */
		return (array) apply_filters(
			'greyd_regex_nested_menus',
			array(
				'footer'   => array(
					'search'  => array( '\[vc_footernav(.+?) menu=\"', '\"' ),
					'replace' => array( '[vc_footernav$1 menu="', '"' ),
					'group'   => 2,
				),
				'dropdown' => array(
					'search'  => array( '\[vc_dropdownnav(.+?) menu=\"', '\"' ),
					'replace' => array( '[vc_dropdownnav$1 menu="', '' ),
					'group'   => 2,
				),
			),
			$post_id,
			$post
		);
	}
}

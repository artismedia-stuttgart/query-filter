<?php
/**
 * Register all block patterns.
 */
namespace greyd\blocks;

if( ! defined( 'ABSPATH' ) ) exit;

new Block_Patterns( $config );
class Block_Patterns {

	/**
	 * Holds the plugin config
	 */
	private $config;

	/**
	 * Constructor
	 */
	public function __construct($config) {

		$this->config = (object) $config;

		add_action( 'init', array( $this, 'register_block_pattern_categories' ) );
		add_action( 'greyd_block_pattern_plugins', array( $this, 'register_block_pattern_plugin' ) );

		if ( \greyd\blocks\enqueue::is_greyd_classic() ) {
			// register blockpatterns
			add_action( 'init', array($this, 'register_block_patterns'), 9 );
	
			// manage the wp schedule event
			add_action( 'init', array($this, 'register_pattern_fetch_event'), 91 );
	
			// fetch blockpatterns
			add_action( 'greyd_fetch_block_patterns_event', array($this, 'fetch_block_patterns'), 91 );
		}
	}

	/**
	 * Register block pattern categories.
	 *
	 * @since 1.9.7
	 */
	public function register_block_pattern_categories() {

		$pattern_categories = array(
			'greyd-hero' => array(
				'label' => __( 'Greyd Hero', 'greyd_hub' )
			),
			'greyd-posts' => array(
				'label' => __( 'Greyd Posts', 'greyd_hub' )
			),
			'greyd-pricing' => array(
				'label' => __( 'Greyd Pricing', 'greyd_hub' )
			),
			'greyd-tiles' => array(
				'label' => __( 'Greyd Tiles', 'greyd_hub' )
			),
			'greyd-sections' => array(
				'label' => __( "Greyd Sections", 'greyd_hub' )
			),
			'greyd-grids' => array(
				'label' => __( "Greyd Grids", 'greyd_hub' )
			),
			'greyd-cards' => array(
				'label' => __( 'Greyd Cards', 'greyd_hub' )
			),
			'greyd-dt-patterns' => array(
				'label' => __( 'Greyd Dynamic Template Patterns', 'greyd_hub' )
			),
			'greyd-footers' => array(
				'label' => __( "Greyd Footers", 'greyd_hub' )
			),
			'greyd-headers' => array(
				'label' => __( "Greyd Headers", 'greyd_hub' )
			),
			'greyd-popup-contents' => array(
				'label' => __( "Greyd Popup Contents", 'greyd_hub' )
			),
			// 'greyd-library' => array(
			// 	'label' => __( 'Greyd Pattern Library', 'greyd_hub' )
			// ),
			// 'greyd-header' => array(
			// 	'label' => __( 'Greyd Header', 'greyd_hub' )
			// ),
			// 'greyd-tiles' => array(
			// 	'label' => __( "Greyd Tiles", 'greyd_hub' )
			// ),
			// 'greyd-separators' => array(
			// 	'label' => __( "Greyd Separator", 'greyd_hub' )
			// ),
			// 'greyd-forms' => array(
			// 	'label' => __( 'Greyd.Forms', 'greyd_hub' )
			// ),
			// 'greyd-footer' => array(
			// 	'label' => __( 'Greyd Footer', 'greyd_hub' )
			// ),
			// 'greyd' => array(
			// 	'label' => __( 'Greyd', 'greyd_hub' )
			// )
		);
	
		foreach ( $pattern_categories as $name => $properties ) {
			register_block_pattern_category( $name, $properties );
		}
	}

	/**
	 * Register block patterns for a plugin.
	 *
	 * @since 1.9.7
	 *
	 * @param string $plugin_path The path to the plugin directory.
	 * @param string $text_domain The plugin text domain.
	 */
	public function register_block_pattern_plugin( $plugins ) {

		$plugins['greyd_blocks'] = array(
			'path'        => $this->config->plugin_path,
			'text_domain' => 'greyd_blocks',
		);

		return $plugins;
	}

	/**
	 * Register the patterns
	 */
	public function register_block_patterns() {

		// check if Gutenberg is active.
		if ( !function_exists('register_block_pattern') ) return;

		$block_patterns = array(
			// Standardinhalte
			'greyd-hero-header' => array(
				'title' => __( 'Hero Header', 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-header'),
				'content'     => 	'<!-- wp:columns {"backgroundColor":"color-33","className":"","row":{"type":"row_xxl"},"background":{"type":"image","opacity":100,"scroll":{"type":"scroll","parallax":30,"parallax_mobile":false},"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-ImgPh-light-wide.svg","size":"contain","repeat":"no-repeat","position":"top center"},"overlay":{"type":"gradient","opacity":30,"color":"","gradient":"color-31-to-color-63-s"}}} -->
									<div class="wp-block-columns has-color-33-background-color has-background"><!-- wp:column {"className":"col-12 wp-block-column ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:spacer {"height":80,"responsive":{"height":{"sm":"20%","md":"20%","lg":"50%"}}} -->
									<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:heading {"textAlign":"center","level":4,"className":"has-text-align-center"} -->
									<h4 class="has-text-align-center">This Is Your New Site</h4>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","level":1} -->
									<h1 class="has-text-align-center">with Greyd.Suite</h1>
									<!-- /wp:heading -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_SCHO4r","trigger":{"type":"scroll","params":"features"},"size":"big","content":"show me more","className":"is-style-prim"} -->
									<a role="trigger" class="button is-style-prim gs_SCHO4r big"><span style="flex:1">show me more</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:spacer {"height":140,"responsive":{"height":{"sm":"60%"}}} -->
									<div style="height:140px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-tile-with-headline-text-button' => array(
				'title' => __( "Tile with headline, text & button", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-8 col-lg-7 ","responsive":{"width":{"md":"col-md-8","lg":"col-lg-7","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-8 col-lg-7"><!-- wp:heading {"level":5,"className":""} -->
									<h5>Intuitive design</h5>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"level":3} -->
									<h3>Thanks to natively integrated Gutenberg Editor</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph -->
									<p>The Greyd.Suite is the only all-in-one solution that has fully integrated WordPress\' new editor. We have extended the native functions in many places and with strong Greyd.Suite features added. You design the design globally for the entire website in the customizer.</p>
									<!-- /wp:paragraph -->
									
									<!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"inline_css_id":"block-d9ba1941-f587-4586-a906-590d820e4649","greydClass":"gs_tkFAQJ","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"open Customizer","opensInNewTab":1}},"content":"open Customizer","icon":{"content":"icon_tool","position":"after","size":"100%","margin":"10px"},"className":"is-style-sec"} -->
									<a role="trigger" class="button is-style-sec gs_tkFAQJ "><span style="flex:1">open Customizer</span><span class="icon_tool" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1","responsive":{"width":{"xs":"col-10","md":"col-md-4","lg":"col-lg-5","sm":""},"offset":{"xs":"offset-1","sm":"offset-sm-3","md":"offset-md-0","lg":"offset-lg-0"}}} -->
									<div class="wp-block-column col-10 col-md-4 col-lg-5 offset-1 offset-sm-3 offset-md-0 offset-lg-0 push-lg-1"></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-tile-features' => array(
				'title' => __( "Feature tile", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:greyd/box {"greydClass":"gs_Jh6dDJ","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-dark.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
									<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
									<li><span class="list_icon"></span><span class="list_content"><p>Greyd.Popups</p></span></li>
									<!-- /wp:greyd/list-item --></ul>
									<!-- /wp:greyd/list -->
									
									<!-- wp:heading {"level":5} -->
									<h5>Automated pop-ups in your page\'s design</h5>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph -->
									<p>With the built-in pop-up builder, you can automatically design unique pop-ups in the design of your website. You have a wide variety of triggers, animations, rules and modules at your disposal.</p>
									<!-- /wp:paragraph -->
									
									<!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
									<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_hK4aoB","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"zu den Pop-ups","opensInNewTab":0}},"size":"small","content":"view popups","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
									<a role="trigger" class="button is-style-trd gs_hK4aoB small"><span style="flex:1">view popups</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:greyd/box -->',
			),
			'greyd-tile-features-highlighted' => array(
				'title' => __( "Feature tile highlighted", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:greyd/box {"greydClass":"gs_jCuC4s","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"color":"color-62","background":"color-11-to-color-12-se","boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-light.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
									<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
									<li><span class="list_icon"></span><span class="list_content"><p>Dynamic Templates</p></span></li>
									<!-- /wp:greyd/list-item --></ul>
									<!-- /wp:greyd/list -->
									
									<!-- wp:heading {"level":5} -->
									<h5>Use all the power of WordPress</h5>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph -->
									<p>Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.</p>
									<!-- /wp:paragraph -->
									
									<!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
									<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_9sd3JV","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=dynamic_template","title":"view templates","opensInNewTab":0}},"content":"view templates","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-sec"} -->
									<a role="trigger" class="button is-style-sec gs_9sd3JV "><span style="flex:1">view templates</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:greyd/box -->',
			),
			'greyd-tile-features-highlighted' => array(
				'title' => __( "Feature tile highlighted", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:greyd/box {"greydClass":"gs_jCuC4s","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"color":"color-62","background":"color-11-to-color-12-se","boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-light.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
									<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
									<li><span class="list_icon"></span><span class="list_content"><p>Dynamic Templates</p></span></li>
									<!-- /wp:greyd/list-item --></ul>
									<!-- /wp:greyd/list -->
									
									<!-- wp:heading {"level":5} -->
									<h5>Use all the power of WordPress</h5>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph -->
									<p>Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.</p>
									<!-- /wp:paragraph -->
									
									<!-- wp:spacer {"height":40,"responsive":{"height":{"sm":"50%","lg":"80%"}}} -->
									<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_9sd3JV","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=dynamic_template","title":"view templates","opensInNewTab":0}},"content":"view templates","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-sec"} -->
									<a role="trigger" class="button is-style-sec gs_9sd3JV "><span style="flex:1">view templates</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:greyd/box -->',
			),
			'greyd-four-tiles' => array(
				'title' => __( "Tile section", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles', 'greyd-sections'),
				'content'     => '<!-- wp:columns {"className":"row_xxl","row":{"type":"row_xxl"}} -->
				<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:columns {"verticalAlignment":"bottom","className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns are-vertically-aligned-bottom row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
				<div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_1pZENb","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
				<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-dark.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
				<li><span class="list_icon"></span><span class="list_content"><p>Greyd.Popups</p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list -->
				
				<!-- wp:heading {"level":5} -->
				<h5 class="wp-block-heading">Automated pop-ups in your page\'s design</h5>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph -->
				<p>With the built-in pop-up builder, you can automatically design unique pop-ups in the design of your website. You have a wide variety of triggers, animations, rules and modules at your disposal.</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:spacer {"height":"40px"} -->
				<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:greyd/buttons -->
				<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_fLj6wp","trigger":{"type":"link","params":{"url":"/wp-admin/customize.php","title":"zu den Pop-ups","opensInNewTab":0}},"size":"small","content":"view popups","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
				<a role="trigger" class="button is-style-trd gs_fLj6wp small"><span style="flex:1">view popups</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
				<div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0"><!-- wp:greyd/box {"greydClass":"gs_BP2UTB","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
				<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-dark.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
				<li><span class="list_icon"></span><span class="list_content"><p>Greyd.Hub</p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list -->
				
				<!-- wp:heading {"level":5} -->
				<h5 class="wp-block-heading">All your projects in one backend</h5>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph -->
				<p>No matter how many pages your installation contains: In Greyd. Hub you can centrally manage all your pages, import and export content, designs or entire pages with just one click, and manage backups clearly.</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:spacer {"height":"40px"} -->
				<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:greyd/buttons -->
				<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_vVLBhg","trigger":{"type":"link","params":{"url":"/wp-admin/admin.php?page=greyd_hub","title":"view dashboard","opensInNewTab":0}},"size":"small","content":"view dashboard","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
				<a role="trigger" class="button is-style-trd gs_vVLBhg small"><span style="flex:1">view dashboard</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"verticalAlignment":"top","className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns are-vertically-aligned-top row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
				<div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_Q34JUA","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"0px","left":"0px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"}},"md":{"padding":{"right":"30px","left":"30px"},"margin":{"right":"0px","left":"0px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"background":"color-63","hover":{"background":"color-23"}}} -->
				<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-dark.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
				<li><span class="list_icon"></span><span class="list_content"><p>Greyd.Forms</p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list -->
				
				<!-- wp:heading {"level":5} -->
				<h5 class="wp-block-heading">Create professional forms</h5>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph -->
				<p>Whether simple contact form with double opt-in, conversion-optimized lead form or complex multistep forms with CRM connection and mathematical calculations – with Greyd. Forms you don\'t need any additional plugins!</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:spacer {"height":"40px"} -->
				<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:greyd/buttons -->
				<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_ij1tuo","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=tp_forms","title":"zu den Formularen","opensInNewTab":0}},"size":"small","content":"zu den Formularen","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-trd"} -->
				<a role="trigger" class="button is-style-trd gs_ij1tuo small"><span style="flex:1">zu den Formularen</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder","responsive":{"width":{"md":"col-md-6","sm":""},"offset":{"sm":"offset-sm-0","md":"offset-md-0","lg":"offset-lg-0"}}} -->
				<div class="wp-block-column col-12 col-md-6 offset-sm-0 offset-md-0 offset-lg-0 boxborder"><!-- wp:greyd/box {"greydClass":"gs_tg6rPw","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"40px","left":"40px"},"margin":{"top":"15px","bottom":"15px","right":"30px","left":"30px"},"responsive":{"lg":{"padding":{"right":"40px","left":"40px"},"margin":{"right":"30px","left":"30px"}},"sm":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"15px","bottom":"15px","right":"20px","left":"20px"}}},"color":"color-62","background":"color-11-to-color-12-se","boxShadow":"0px+0px+50px+0px+color-22+100","hover":{"boxShadow":"0px+0px+30px+0px+color-22+100"}}} -->
				<div class="wp-block-greyd-box"><!-- wp:greyd/list {"type":"img","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"","url":"https://update.greyd.io/files/img/greyd-LogoPh-light.svg","id":-1,"color":"","size":"40px","margin":"15px","position":"left","align_y":"center","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"type":"img"} -->
				<li><span class="list_icon"></span><span class="list_content"><p>Dynamic Templates</p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list -->
				
				<!-- wp:heading {"level":5} -->
				<h5 class="wp-block-heading">Use all the power of WordPress</h5>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph -->
				<p>Dynamic Templates reduce the effort required to build and maintain your page massively and enable editors without WordPress knowledge to maintain content.</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:spacer {"height":"40px"} -->
				<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:greyd/buttons -->
				<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"greydClass":"gs_dOZD8j","trigger":{"type":"link","params":{"url":"/wp-admin/edit.php?post_type=dynamic_template","title":"view templates","opensInNewTab":0}},"content":"view templates","icon":{"content":"arrow_right","position":"after","size":"150%","margin":"10px"},"className":"is-style-sec"} -->
				<a role="trigger" class="button is-style-sec gs_dOZD8j "><span style="flex:1">view templates</span><span class="arrow_right" style="vertical-align:middle;font-size:150%;margin-left:10px" aria-hidden="true"></span></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:spacer {"responsive":{"height":{"sm":"50%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->',
			),
			'greyd-centered-quote-with-background' => array(
				'title' => __( "Centered quote with background", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections'),
				'content'     => 	'<!-- wp:columns {"gradient":"color-11-to-color-12-sw","className":"","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns has-color-11-to-color-12-sw-gradient-background has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:columns {"verticalAlignment":"middle","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-middle row_xxxl"><!-- wp:column {"className":"col-12 col-md-10 offset-sm-1 wp-block-column ","responsive":{"width":{"md":"col-md-10","sm":""},"offset":{"sm":"offset-sm-1"}}} -->
									<div class="wp-block-column col-12 col-md-10 offset-sm-1"><!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"textAlign":"center","level":3,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
									<h3 class="has-text-align-center has-color-62-color has-text-color">"It\'s not an experiment if you know it\'s going to work."</h3>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","level":6,"textColor":"color-62","className":"has-text-align-center has-color-62-color has-text-color"} -->
									<h6 class="has-text-align-center has-color-62-color has-text-color">Jeff Bezos</h6>
									<!-- /wp:heading -->
									
									<!-- wp:spacer {"height":120,"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:120px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-section-with-background-headlines-buttons' => array(
				'title' => __( "Section with background, headlines and buttons", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections'),
				'content'     => 	'<!-- wp:columns {"backgroundColor":"color-23","className":"","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns has-color-23-background-color has-background"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12">
									<!-- wp:spacer -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"textAlign":"center","level":5} -->
									<h5 class="has-text-align-center">Get started now</h5>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","level":4} -->
									<h4 class="has-text-align-center">Tutorials, Quick Learning Videos & FAQ can be found here:</h4>
									<!-- /wp:heading -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"inline_css_id":"block-89f0e20d-d661-4145-aebe-a572828453dc","greydClass":"gs_5RKela","trigger":{"type":"link","params":{"url":"https%3A%2F%docs.greyd.io","title":"view Documentation","opensInNewTab":1}},"content":"view Documentation","className":"is-style-prim"} -->
									<a role="trigger" class="button is-style-prim gs_5RKela "><span style="flex:1">view Documentation</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- wp:spacer {"height":50} -->
									<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			// Template 2
			'greyd-hero-header-2' => array(
				'title' => __( 'Hero Header 2', 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-header'),
				'content'     => 	'<!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"bottom","backgroundColor":"color-61","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-bottom row_xxxl has-color-61-background-color has-background"><!-- wp:column {"className":"col-12 inline_button centered","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12 inline_button centered"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 100px)","enable":true}]} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/box {"greydClass":"gs_fJGZu9","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:heading {"textAlign":"center","level":1,"className":"dyn ","fontSize":"h-1","dynamic_fields":[{"key":"content","title":"Headline (H1)","enable":true},{"key":"level","title":"Headline (H1) H-Tag","enable":true}]} -->
									<h1 class="has-text-align-center dyn has-h-1-font-size">We make your brand visible</h1>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","className":"dyn ","fontSize":"h-4","dynamic_fields":[{"key":"content","title":"Subline (H2)","enable":true},{"key":"level","title":"Subline (H2) H-Tag","enable":true}]} -->
									<h2 class="has-text-align-center dyn has-h-4-font-size">Online. Offline. Internationally</h2>
									<!-- /wp:heading -->
									
									<!-- wp:spacer {"className":""} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:greyd/box -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Anchor Button (Optional)","enable":true},{"key":"trigger","title":"Anchor Target","enable":true}],"inline_css":"\n","inline_css_id":"block-82df6503-d64e-4391-a427-af1bbcf2d92b","greydClass":"gs_f8uDOT","trigger":{"type":"scroll","params":""},"size":"big", "content":"Get in touch","className":"is-big dyn is-style-prim"} -->
									<a role="trigger" class="button is-big dyn is-style-prim gs_f8uDOT big"><span style="flex:1">Get in touch</span></a>
									<!-- /wp:greyd/button -->
									
									<!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Alternative Button (Optional) Text","enable":true},{"key":"trigger","title":"Alternative Button (Optional) Trigger","enable":true}],"greydClass":"gs_fgkpN1","size":"big","content":"Read more","className":"is-big dyn is-style-trd"} -->
									<a role="trigger" class="button is-big dyn is-style-trd gs_fgkpN1 big"><span style="flex:1">Read more</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 100px)","enable":true}],"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group -->',
			),
			'greyd-separator-headline-subline-with-cta' => array(
				'title' => __( "Separator - headline, subline with CTA", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-separators'),
				'content'     => 	'<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 inline_button centered","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12 inline_button centered"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":37.800000000000004}},"className":"","dynamic_fields":[{"key":"content","title":"Headline (H3)","enable":true},{"key":"level","title":"Headline (H3) H-Tag","enable":true}]} -->
									<h3 class="has-text-align-center" style="font-size:37.800000000000004px">Who we are</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.75em"}},"className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"line-height: 140%","inline_css_id":"block-d9e5ec33-329b-44e2-a6a9-7f123d674bda"} -->
									<p class="has-text-align-center" id="block-d9e5ec33-329b-44e2-a6a9-7f123d674bda" style="font-size:1.75em"><span class="has-fontsize-h4">We are a team of international marketing experts covering different marketing specialities. We support small and medium sized companies in making their brand visible – online, offline and across countries. We understand ourselves as partners of our clients, delivering not only great strategies but also helping our clients to implement, monitor and constantly improve them.</span></p>
									<!-- /wp:paragraph -->
									
									<!-- wp:spacer {"className":"","responsive":{"height":{"sm":"75%","md":"75%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Button (Optional) Text","enable":true},{"key":"trigger","title":"Primary Button (Optional) Trigger","enable":true}],"greydClass":"gs_QnLEg4","size":"big","content":"Read more","className":"is-big dyn is-style-prim"} -->
									<a role="trigger" class="button is-big dyn is-style-prim gs_QnLEg4 big"><span style="flex:1">Read more</span></a>
									<!-- /wp:greyd/button -->
									
									<!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Secondary Anchor Button (Optional)","enable":true},{"key":"trigger","title":"Anchor Target","enable":true}],"greydClass":"gs_dyDkn7","trigger":{"type":"scroll","params":""},"size":"big","content":"Pricing","className":"is-big dyn is-style-sec"} -->
									<a role="trigger" class="button is-big dyn is-style-sec gs_dyDkn7 big"><span style="flex:1">Pricing</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-tile-icon-text-with-link' => array(
				'title' => __( "Panel – icon + text with link", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_H2k5sm","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn "} -->
									<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"dyn ","dynamic_fields":[{"key":"id","title":"Icon","enable":true}],"inline_css":"\n","inline_css_id":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
									<div id="block-48472edc-10b9-40f2-a824-dda0b614fd13" class="wp-block-image dyn"><figure class="aligncenter size-large is-resized"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure></div>
									<!-- /wp:image -->
									
									<!-- wp:heading {"textAlign":"center","level":4,"className":"dyn ","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
									<h4 class="has-text-align-center dyn has-h-5-font-size">Please enter a Headline (H5)</h4>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"align":"center","className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
									<p class="has-text-align-center dyn" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">Please put your Text here and align it centered</p>
									<!-- /wp:paragraph -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}],"greydClass":"gs_va5NFn","className":"dyn is-style-link-prim"} -->
									<a role="trigger" class="link dyn is-style-link-prim gs_va5NFn "><span style="flex:1"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:greyd/box -->',
			),
			'greyd-section-with-icon-tiles' => array(
				'title' => __( "Section with info tiles", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-tiles'),
				'content'     => '<!-- wp:columns {"style":{"color":{"gradient":"linear-gradient(180deg, #f4f4f4 0%, #dbdbdb 100%)"}},"className":"row_xxl","row":{"type":"row_xxl"},"background":{"pattern":{"type":"pattern_custom","id":-1,"url":"","opacity":50,"size":"4px","scroll":"scroll"}}} -->
				<div class="wp-block-columns row_xxl has-background" style="background:linear-gradient(180deg, #f4f4f4 0%, #dbdbdb 100%)"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":37.800000000000004}},"className":"","dynamic_fields":[{"key":"content","title":"Headline (H3)","enable":true}]} -->
				<h3 class="has-text-align-center" style="font-size:37.800000000000004px">Our services</h3>
				<!-- /wp:heading -->
				
				<!-- wp:spacer {"height":"25px","className":""} -->
				<div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/box {"greydClass":"gs_7PqNJJ","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">Content Creation</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We create target group oriented texts</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_Sd3WEQ","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_Sd3WEQ "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 border","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6 border"><!-- wp:greyd/box {"greydClass":"gs_Bo2WYz","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"dyn ","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized dyn" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">Social Media</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We fill your social media channels&nbsp;</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_XZdq07","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_XZdq07 "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/box {"greydClass":"gs_ooh93D","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"dyn ","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized dyn" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">Website Creation</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We build corporate websites and landing pages</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_VSGas2","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_VSGas2 "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 border","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6 border"><!-- wp:greyd/box {"greydClass":"gs_IgeNWh","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"dyn ","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized dyn" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">SEO</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We make sure, search engines like your content</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_A1dNLe","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_A1dNLe "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"className":"row_xxxl","inline_css":"margin-bottom: 2em","inline_css_id":"block-4f944c82-c2fa-4071-b8a8-804070115793","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl" id="block-4f944c82-c2fa-4071-b8a8-804070115793"><!-- wp:column {"className":"col-12 col-md-6","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/box {"greydClass":"gs_M6sovg","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">Advertising</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We plan and execute campaigns for your brand</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_ocLdpL","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_ocLdpL "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 border","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6 border"><!-- wp:greyd/box {"greydClass":"gs_I2XzYD","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"background":"color-62","boxShadow":"2px+20px+34px+-21px+color-11+100"},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:image {"align":"center","id":602,"width":120,"sizeSlug":"large","linkDestination":"none","className":"dyn ","anchor":"block-48472edc-10b9-40f2-a824-dda0b614fd13"} -->
				<figure class="wp-block-image aligncenter size-large is-resized dyn" id="block-48472edc-10b9-40f2-a824-dda0b614fd13"><img src="https://update.greyd.io/files/img/greyd-block-pattern-placeholder-1-1.svg" alt="" class="wp-image-602" width="120"/></figure>
				<!-- /wp:image -->
				
				<!-- wp:heading {"textAlign":"center","level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-text-align-center has-h-5-font-size">Tool Support</h4>
				<!-- /wp:heading -->
				
				<!-- wp:paragraph {"align":"center","className":"","dynamic_fields":[{"key":"content","title":"Text","enable":true}],"inline_css":"margin-bottom: 2em\n","inline_css_id":"block-ea229209-5f1c-4ee5-bef9-3cd61765d295"} -->
				<p class="has-text-align-center" id="block-ea229209-5f1c-4ee5-bef9-3cd61765d295">We find the perfect tools for your business</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"greydClass":"gs_yiRQOh","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-link-prim","dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}]} -->
				<a role="trigger" class="link dyn is-style-link-prim gs_yiRQOh "></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"inline_css":"margin-top: 3em !important;\n","greydClass":"gs_DxXrTo","trigger":{"type":"scroll","params":""},"size":"big","content":"REQUEST AN OFFER","icon":{"content":"","position":"after","size":"100%","margin":"10px"},"className":"is-big dyn is-style-prim","dynamic_fields":[{"key":"content","title":"Primary Anchor Button (Optional)","enable":true},{"key":"trigger","title":"Anchor Target","enable":true}]} -->
				<a role="trigger" class="button is-big dyn is-style-prim gs_DxXrTo big">REQUEST AN OFFER</a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons -->
				
				<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->',
			),
			'greyd-tile-list' => array(
				'title' => __( "Tile with list", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-tiles'),
				'content'     => 	'<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_fQhA6K","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-31","background":"color-62","boxShadow":"2px+20px+34px+-21px+color-13+100","hover":{"color":"color-61","boxShadow":"2px+10px+14px+-12px+color-12+100"}},"className":"dyn "} -->
									<div class="wp-block-greyd-box dyn"><!-- wp:heading {"level":4,"className":"dyn ","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
									<h4 class="dyn has-h-5-font-size">Please enter a Headline (H5)</h4>
									<!-- /wp:heading -->

									<!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_plus","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"first","align_x":"start"}} -->
									<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p>Please enter a Bulletpoint</p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p>Please enter a Bulletpoint</p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p>Please enter a Bulletpoint</p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 04","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 05","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 06","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
									<!-- /wp:greyd/list-item -->

									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 07","enable":true}],"type":"icon","icon":"icon_plus"} -->
									<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
									<!-- /wp:greyd/list-item --></ul>
									<!-- /wp:greyd/list --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-section-headline-list-tiles' => array(
				'title' => __( "Separator with headline and list tiles", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-tiles'),
				'content'     => 	'<!-- wp:columns {"style":{"color":{"gradient":"linear-gradient(180deg, #f4f4f4 0%, #dbdbdb 100%)"}},"className":"row_xxl ","row":{"type":"row_xxl"},"background":{"pattern":{"type":"pattern_custom","id":-1,"url":"","opacity":50,"size":"4px","scroll":"scroll"}}} -->
				<div class="wp-block-columns row_xxl has-background" style="background:linear-gradient(180deg, #f4f4f4 0%, #dbdbdb 100%)"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":37.800000000000004}},"className":"","dynamic_fields":[{"key":"content","title":"Headline (H3)","enable":true}]} -->
				<h3 class="has-text-align-center" style="font-size:37.800000000000004px">How we support our clients</h3>
				<!-- /wp:heading -->
				
				<!-- wp:spacer {"height":25,"className":"","responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_qCjTX9","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-31","background":"color-62","boxShadow":"2px+20px+34px+-21px+color-13+100","hover":{"color":"color-61","boxShadow":"2px+10px+14px+-12px+color-12+100"}},"className":"dyn "} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:heading {"level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-h-5-font-size">24/7 Support</h4>
				<!-- /wp:heading -->
				
				<!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_plus","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"first","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Various possibilities to contact us</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Feedback within minutes</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Multi-language support</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 04","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 05","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 06","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 07","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 border","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6 border"><!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_nkK3xk","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-31","background":"color-62","boxShadow":"2px+20px+34px+-21px+color-13+100","hover":{"color":"color-61","boxShadow":"2px+10px+14px+-12px+color-12+100"}},"className":"dyn "} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:heading {"level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-h-5-font-size">Detailed reportings</h4>
				<!-- /wp:heading -->
				
				<!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_plus","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"first","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Weekly or monthly reportings</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Detailed activity reports</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Clear overview of numbers</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 04","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 05","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 06","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 07","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6"><!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_6qXMCj","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-31","background":"color-62","boxShadow":"2px+20px+34px+-21px+color-13+100","hover":{"color":"color-61","boxShadow":"2px+10px+14px+-12px+color-12+100"}},"className":"dyn "} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:heading {"level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-h-5-font-size">Constant improvement</h4>
				<!-- /wp:heading -->
				
				<!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_plus","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"first","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Ongoing monitoring</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Regular strategy review</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Adaptation of actions</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 04","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 05","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 06","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 07","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-6 border","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-6 border"><!-- wp:greyd/box {"dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}],"greydClass":"gs_kWCVeX","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-31","background":"color-62","boxShadow":"2px+20px+34px+-21px+color-13+100","hover":{"color":"color-61","boxShadow":"2px+10px+14px+-12px+color-12+100"}},"className":"dyn "} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:heading {"level":4,"className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline (H5)","enable":true},{"key":"level","title":"Headline (H5) H-Tag","enable":true}]} -->
				<h4 class="has-h-5-font-size">Personal contact</h4>
				<!-- /wp:heading -->
				
				<!-- wp:greyd/list {"type":"icon","web":{"style":"disc","position":"left","align_y":"start"},"icon":{"icon":"icon_plus","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"first","align_x":"start"}} -->
				<ul class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Dedicated contact person</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Individual strategies</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p><meta charset="utf-8"/>Weekly update calls</p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 04","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 05","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 06","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item -->
				
				<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 07","enable":true}],"type":"icon","icon":"icon_plus"} -->
				<li><span class="list_icon icon_plus"></span><span class="list_content"><p></p></span></li>
				<!-- /wp:greyd/list-item --></ul>
				<!-- /wp:greyd/list --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:greyd/buttons {"align":"center"} -->
				<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Anchor Button (Optional)","enable":true},{"key":"trigger","title":"Anchor Target","enable":true}],"inline_css":"margin-top: 3em !important;\n","greydClass":"gs_s4HFaQ","trigger":{"type":"scroll","params":""},"size":"big","className":"is-big dyn is-style-prim"} -->
				<a role="trigger" class="button is-big dyn is-style-prim gs_s4HFaQ big"><span style="flex:1"></span></a>
				<!-- /wp:greyd/button --></div>
				<!-- /wp:greyd/buttons -->
				
				<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->',
			),
			'greyd-section-price-tiles' => array(
				'title' => __( "Section with pricing tiles", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-tiles'),
				'content'     => '<!-- wp:columns {"backgroundColor":"color-61","className":"row_xxl","row":{"type":"row_xxl"}} -->
				<div class="wp-block-columns row_xxl has-color-61-background-color has-background"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":37.800000000000004}},"textColor":"color-62","className":"","dynamic_fields":[{"key":"content","title":"Headline (H3)","enable":true},{"key":"level","title":"Headline (H3) H-Tag","enable":true}]} -->
				<h3 class="has-text-align-center has-color-62-color has-text-color" style="font-size:37.800000000000004px">Tool Support</h3>
				<!-- /wp:heading -->
				
				<!-- wp:spacer {"height":"25px","className":""} -->
				<div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
				<!-- /wp:spacer -->
				
				<!-- wp:greyd/box {"greydClass":"gs_VVmls0","greydStyles":{"padding":{},"color":"color-62"},"className":""} -->
				<div class="wp-block-greyd-box"><!-- wp:columns {"className":"row_xxxl centered","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl centered"><!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_dRHrQP","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product A</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8">99 $</strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3 border","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3 border"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_YeQGKg","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product B</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_xSCOCx","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product C</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_JHhuuU","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product D</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->
				
				<!-- wp:columns {"className":"row_xxxl centered","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl centered"><!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_Bcm35K","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product E</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3 border","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3 border"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_ybGtZz","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product F</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_EzaMLA","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product G</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column -->
				
				<!-- wp:column {"className":"col-12 col-md-3","responsive":{"width":{"md":"col-md-3","sm":""}}} -->
				<div class="wp-block-column col-12 col-md-3"><!-- wp:columns {"className":"row_xxxl","row":{"type":"row_xxxl"}} -->
				<div class="wp-block-columns row_xxxl"><!-- wp:column {"className":"col-12","responsive":{"width":{"sm":""}}} -->
				<div class="wp-block-column col-12"><!-- wp:greyd/box {"greydClass":"gs_UfbOLm","greydStyles":{"padding":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"margin":{"top":"30px","bottom":"30px","right":"30px","left":"30px"},"responsive":{"sm":{"padding":{"top":"15px","bottom":"15px","right":"15px","left":"15px"},"margin":{"top":"15px","bottom":"15px","right":"15px","left":"15px"}}},"color":"color-62","border":{"top":"2px solid var(\u002d\u002dcolor62)","right":"2px solid var(\u002d\u002dcolor62)","bottom":"2px solid var(\u002d\u002dcolor62)","left":"2px solid var(\u002d\u002dcolor62)"},"hover":{"color":"color-11"}},"className":"dyn ","dynamic_fields":[{"key":"trigger","title":"Tile Link (Optional) Trigger","enable":true}]} -->
				<div class="wp-block-greyd-box dyn"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.25em"}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true}]} -->
				<p style="font-size:1.25em">Product H</p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"","dynamic_fields":[{"key":"content","title":"Price","enable":true}]} -->
				<p><strong><meta charset="utf-8"><strong>99 $</strong></strong></p>
				<!-- /wp:paragraph -->
				
				<!-- wp:paragraph {"className":"dyn ","dynamic_fields":[{"key":"content","title":"Text","enable":true}]} -->
				<p class="dyn"></p>
				<!-- /wp:paragraph --></div>
				<!-- /wp:greyd/box --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns --></div>
				<!-- /wp:greyd/box -->
				
				<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 50px)","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
				<!-- /wp:spacer --></div>
				<!-- /wp:column --></div>
				<!-- /wp:columns -->',
			),
			// Demo Template
			'greyd-hero-header-3' => array(
				'title' => __( 'Hero Header 3', 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-header'),
				'content'     => 	'<!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}}},"className":"col-12 col-sm-8 col-md-5 order-1 order-md-0 offset-sm-1 ","responsive":{"width":{"sm":"col-sm-8","md":"col-md-5"},"order":{"xs":"order-1","md":"order-md-0"},"offset":{"sm":"offset-sm-1"}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12 col-sm-8 col-md-5 order-1 order-md-0 offset-sm-1" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px"><!-- wp:spacer {"height":50,"className":"","responsive":{"height":{"sm":"0%","md":"0%","lg":"100%"}}} -->
									<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"level":1,"className":"","dynamic_fields":[{"key":"content","title":"Main Headline","enable":true},{"key":"level","title":"Main Headline H-Tag","enable":true}],"inline_css_id":"block-34f4c737-3c77-4631-a7b1-422ba8fc07e0","greydClass":"gs_crQSEP","greydStyles":{"maxWidth":"600px"}} -->
									<h1 id="block-34f4c737-3c77-4631-a7b1-422ba8fc07e0">Digital brand positioning made easy</h1>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"style":{"typography":{"fontWeight":"400"}},"textColor":"color-32","className":"","fontSize":"h-3","dynamic_fields":[{"key":"content","title":"Main Subline","enable":true},{"key":"level","title":"H-Tag","enable":true}],"inline_css_id":"block-0b077cc8-0b3a-4ba7-a439-8d1a2c67118b","greydClass":"gs_1XnOu2","greydStyles":{"maxWidth":"650px"}} -->
									<h2 class="has-color-32-color has-text-color has-h-3-font-size" id="block-0b077cc8-0b3a-4ba7-a439-8d1a2c67118b" style="font-weight:400"><meta charset="utf-8">Individual online marketing solutions that will make your business thrive</h2>
									<!-- /wp:heading -->
									
									<!-- wp:spacer {"height":40,"className":"","dynamic_fields":[]} -->
									<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Button","enable":true},{"key":"trigger","title":"Primary Trigger","enable":true}],"greydClass":"gs_3Gybpk","size":"big","content":"Get in touch","className":"is-big dyn "} -->
									<a role="trigger" class="button is-big dyn  gs_3Gybpk big"><span style="flex:1">Get in touch</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"sm":"","md":"col-md-6"}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:columns {"className":"row_xxxl ","row":{"type":"row_xxxl"},"background":{"type":"image","image":{"id":834,"url":"https://update.greyd.io/files/img/greyd-block-pattern-banner-1.svg","size":"cover","repeat":"no-repeat","position":"center left"}}} -->
									<div class="wp-block-columns row_xxxl"><!-- wp:column {"width":"100%","className":"col-12 col-sm-auto "} -->
									<div class="wp-block-column col-12 col-sm-auto" style="flex-basis:100%"><!-- wp:greyd/box {"greydClass":"gs_BXFN70","greydStyles":{"background":"","padding":{"top":"100px","right":"100px","bottom":"100px","left":"100px"},"responsive":{"sm":{"padding":{"top":"100px","left":"50px","right":"50px","bottom":"50px"}},"md":{"padding":{"top":"100px","left":"50px","right":"50px","bottom":"50px"}}},"minHeight":""},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:spacer {"height":50,"className":"","responsive":{"height":{"sm":"0%","md":"0%","lg":"100%"}}} -->
									<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:image {"align":"center","id":696,"width":520,"height":580,"sizeSlug":"full","linkDestination":"none","className":"dyn ","dynamic_fields":[{"key":"id","title":"Image","enable":true}]} -->
									<div class="wp-block-image dyn"><figure class="aligncenter size-full is-resized"><img src="https://update.greyd.io/files/img/greyd-block-pattern-phone.png" alt="" class="wp-image-696" width="520" height="580"/></figure></div>
									<!-- /wp:image --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-seperator-headline-subline-cta' => array(
				'title' => __( "Separator with headline, subline and CTA", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-separators'),
				'content'     => 	'<!-- wp:columns {"className":"row_xl ","row":{"type":"row_xl"}} -->
									<div class="wp-block-columns row_xl"><!-- wp:column {"className":"col-12 inline_button centered","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12 inline_button centered"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space around","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":37.800000000000004}},"className":"","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"H-Tag","enable":true}]} -->
									<h2 class="has-text-align-center" style="font-size:37.800000000000004px">Why us</h2>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontWeight":"400"}},"textColor":"color-32","className":"","fontSize":"h-3","dynamic_fields":[{"key":"content","title":"Subline","enable":true},{"key":"level","title":"H-Tag","enable":true}],"inline_css_id":"block-51c46e03-b13f-4f77-8c9c-2b7915c578c9","greydClass":"gs_ltRiuW","greydStyles":{"maxWidth":"1100px"}} -->
									<h3 class="has-text-align-center has-color-32-color has-text-color has-h-3-font-size" id="block-51c46e03-b13f-4f77-8c9c-2b7915c578c9" style="font-weight:400">We support companies at all stages of the sales funnel creating unique online experiences for your leads and customers. Take a look at our track record and services to get an impression of our work.</h3>
									<!-- /wp:heading -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space between","enable":true}]} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:greyd/buttons {"align":"center"} -->
									<div class="wp-block-greyd-buttons aligncenter"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Alternative Anchor Button","enable":true},{"key":"trigger","title":"Alternative Button Trigger","enable":true}],"inline_css_id":"block-618d3d47-d0c8-4879-aa69-1b7d66efa8fd","greydClass":"gs_SgAEO4","trigger":{"type":"scroll","params":""},"content":"Services","className":"dyn is-style-trd"} -->
									<a role="trigger" class="button dyn is-style-trd gs_SgAEO4 "><span style="flex:1">Services</span></a>
									<!-- /wp:greyd/button -->
									
									<!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Link Button","enable":true},{"key":"trigger","title":"Primary Link Trigger","enable":true}],"greydClass":"gs_6WYdGR","content":"Read more","className":"dyn "} -->
									<a role="trigger" class="button dyn  gs_6WYdGR "><span style="flex:1">Read more</span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space around","enable":true}],"responsive":{"height":{"sm":"75%","md":"75%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-conversion-list-form' => array(
				'title' => __( "Conversion section with list and placeholder for a form", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-forms'),
				'content'     => 	'<!-- wp:columns {"className":"row_xxxl dyn ","dynamic_fields":[{"key":"background/image/id","title":"background/image/id_d6rzj9","enable":true}],"row":{"type":"row_xxxl"},"background":{"type":"image","image":{"id":848,"url":"https://update.greyd.io/files/img/greyd-block-pattern-banner-1-conversion.svg"}}} -->
									<div class="wp-block-columns row_xxxl dyn"><!-- wp:column {"className":"col-12 col-sm-auto "} -->
									<div class="wp-block-column col-12 col-sm-auto"><!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Around","enable":true}]} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"textColor":"color-62","className":"col-12 col-sm-6 col-md-8 ","responsive":{"width":{"sm":"col-sm-6","md":"col-md-8"}}} -->
									<div class="wp-block-column col-12 col-sm-6 col-md-8 has-color-62-color has-text-color"><!-- wp:heading {"textColor":"color-62","className":"","fontSize":"h-1","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"H-Tag","enable":true}]} -->
									<h2 class="has-color-62-color has-text-color has-h-1-font-size">Get in touch now</h2>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"400"}},"textColor":"color-62","className":"","fontSize":"h-3","dynamic_fields":[{"key":"content","title":"Subline","enable":true},{"key":"level","title":"Subline H-Tag","enable":true}]} -->
									<h3 class="has-color-62-color has-text-color has-h-3-font-size" style="font-weight:400">'.__('Make yourself on the way to digital success. We\'ll be happy to accompany you.', 'greyd_hub').'</h3>
									<!-- /wp:heading -->
									
									<!-- wp:greyd/list {"inline_css":"margin-bottom: 3em;","inline_css_id":"block-ba765e9f-84a9-4a8d-a5c5-1e08f7806b1b","type":"icon","icon":{"icon":"icon_check_alt","url":"","id":-1,"color":"","size":"20px","margin":"10px","position":"left","align_y":"start","align_x":"start"}} -->
									<ul id="block-ba765e9f-84a9-4a8d-a5c5-1e08f7806b1b" class="wp-block-greyd-list"><!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 01","enable":true}],"type":"icon","icon":"icon_check_alt"} -->
									<li><span class="list_icon icon_check_alt"></span><span class="list_content"><p>Get an individual offer</p></span></li>
									<!-- /wp:greyd/list-item -->
									
									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 02","enable":true}],"type":"icon","icon":"icon_check_alt"} -->
									<li><span class="list_icon icon_check_alt"></span><span class="list_content"><p><meta charset="utf-8"/>Choose your contract term</p></span></li>
									<!-- /wp:greyd/list-item -->
									
									<!-- wp:greyd/list-item {"dynamic_fields":[{"key":"content","title":"Bullet 03","enable":true}],"type":"icon","icon":"icon_check_alt"} -->
									<li><span class="list_icon icon_check_alt"></span><span class="list_content"><p><meta charset="utf-8"/>Benefit from personal contacts</p></span></li>
									<!-- /wp:greyd/list-item --></ul>
									<!-- /wp:greyd/list --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-12 col-sm-6 col-md-4 ","responsive":{"width":{"sm":"col-sm-6","md":"col-md-4"}}} -->
									<div class="wp-block-column col-12 col-sm-6 col-md-4"><!-- wp:greyd/box {"greydClass":"gs_EIOjsC","greydStyles":{"background":"#ffffff","boxShadow":"0px+10px+15px+0px+color-31+25","padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:greyd/form {"dynamic_fields":[{"key":"id","title":"Form","enable":true}],"id":"-1","className":"dyn "} /--></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Around","enable":true}]} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			// multipurpose theme
			'greyd-hero-header-4' => array(
				'title' => __( 'Hero Header 4', 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-header'),
				'content'     => 	'<!-- wp:columns {"backgroundColor":"","className":"row_xxl dyn ","dynamic_fields":[{"key":"background/image/id","title":"Background Image","enable":true}],"row":{"type":"row_xxl"},"background":{"type":"image","image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-banner-2.jpeg"}}} -->
									<div class="wp-block-columns row_xxl dyn"><!-- wp:column {"className":"col-12 inline_button","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12 inline_button"><!-- wp:spacer {"height":250,"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 250px)","enable":true}],"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:250px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"level":1,"textColor":"color-62","className":"","fontSize":"h-2","dynamic_fields":[{"key":"content","title":"Headline (H1)","enable":true}],"greydClass":"gs_hMvlGA","greydStyles":{"maxWidth":"700px"}} -->
									<h1 class="has-color-62-color has-text-color has-h-2-font-size"><strong>Innovation is the key to success</strong></h1>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"400"}},"textColor":"color-62","className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline (H2)","enable":true},{"key":"level","title":"Subline (H2) H-Tag","enable":true}],"inline_css":"margin-bottom: 2em;\n","inline_css_id":"please-enter-a-subline-h2","greydClass":"gs_w2BDKr","greydStyles":{"maxWidth":"800px"}} -->
									<h2 class="has-color-62-color has-text-color has-h-6-font-size" style="font-style:normal;font-weight:400"><strong>We always have innovation at the top of our mind to constantly improve our products and services. Take a tour on our website to find out more about us.</strong></h2>
									<!-- /wp:heading -->
									
									<!-- wp:greyd/buttons -->
									<div class="wp-block-greyd-buttons"><!-- wp:greyd/button {"dynamic_fields":[{"key":"content","title":"Primary Anchor Button","enable":true},{"key":"trigger","title":"Primary Anchor Button Trigger","enable":true}],"greydClass":"gs_oldwwp","content":"\u003cstrong\u003eread more\u003c/strong\u003e","icon":{"content":"arrow_down","position":"after","size":"100%","margin":"10px"},"className":"dyn is-style-prim"} -->
									<a role="trigger" class="button dyn is-style-prim gs_oldwwp "><span style="flex:1"><strong>read more</strong></span><span class="arrow_down" style="vertical-align:middle;font-size:100%;margin-left:10px" aria-hidden="true"></span></a>
									<!-- /wp:greyd/button --></div>
									<!-- /wp:greyd/buttons -->
									
									<!-- wp:spacer {"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 100px)","enable":true}],"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:100px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->',
			),
			'greyd-section-with-icon-tiles' => array(
				'title' => __( "Section with info tiles 2", 'greyd_hub' ),
				'categories'  => array('greyd', 'greyd-sections', 'greyd-tiles'),
				'content'     => 	'<!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","inline_css":"margin-bottom: 3em !important;\n","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:spacer {"height":200,"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Top (Standard 200px)","enable":true}],"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:200px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer -->
									
									<!-- wp:heading {"textAlign":"center","level":6,"textColor":"color-11","className":"","dynamic_fields":[{"key":"content","title":"Descriptor (Optional)","enable":true},{"key":"level","title":"Descriptor (Optional) H-Tag","enable":true}]} -->
									<h6 class="has-text-align-center has-color-11-color has-text-color">Benefits</h6>
									<!-- /wp:heading -->
									
									<!-- wp:heading {"textAlign":"center","level":3,"className":"","fontSize":"h-3","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}],"inline_css":"margin-bottom: 2em","inline_css_id":"please-enter-a-headline"} -->
									<h3 class="has-text-align-center has-h-3-font-size">Benefit from our services</h3>
									<!-- /wp:heading --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_6GOzQ2","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_MSrgF9","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our First Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Increase your turnover by at least 10% with our First Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_LiwBWj","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_ShAkWt","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our Second Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Get more leads through improving your websites with our Second Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_rqQs5X","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_aXQAnp","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our Third Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Turn more leads into paying customers by using our Third Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_t2nHni","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_21LzjO","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our Fourth Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Improve your customer loyalty with the help of our Fourth Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_kDEIy7","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_Fx1CES","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our Fifth Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Shorten the time to solve your service tickets by using our Fifth Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"className":"col-12 col-md-6 ","responsive":{"width":{"md":"col-md-6","sm":""}}} -->
									<div class="wp-block-column col-12 col-md-6"><!-- wp:group {"className":""} -->
									<div class="wp-block-group"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxl"><!-- wp:column {"verticalAlignment":"center","className":"col-12 ","inline_css":"margin-bottom: 2em\n","inline_css_id":"block-3221b2b4-0529-4ded-8ac2-258e884a8743","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-12" id="block-3221b2b4-0529-4ded-8ac2-258e884a8743"><!-- wp:greyd/box {"greydClass":"gs_0YES05","greydStyles":{"padding":{"bottom":"30px"},"border":{"top":"0px solid rgba(121,126,224,0.15)","right":"0px solid rgba(121,126,224,0.15)","bottom":"1px solid rgba(121,126,224,0.15)","left":"0px solid rgba(121,126,224,0.15)"}},"className":""} -->
									<div class="wp-block-greyd-box"><!-- wp:columns {"verticalAlignment":"center","className":"row_xxxl ","inline_css":"margin-bottom: 2em !important;\n","row":{"type":"row_xxxl"}} -->
									<div class="wp-block-columns are-vertically-aligned-center row_xxxl"><!-- wp:column {"verticalAlignment":"center","className":"col-3 col-md-3 ","responsive":{"width":{"xs":"col-3","md":"col-md-3","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-3 col-md-3"><!-- wp:greyd/image {"dynamic_fields":[{"key":"image","title":"Icon","enable":true}],"image":{"id":1,"url":"https://update.greyd.io/files/img/greyd-block-pattern-icon-checkmark.svg","tag":"","type":"file"},"greydClass":"gs_4heIED","greydStyles":{"width":"75px"},"align":"center","className":"dyn "} /--></div>
									<!-- /wp:column -->
									
									<!-- wp:column {"verticalAlignment":"center","className":"col-9 col-md-9 ","responsive":{"width":{"xs":"col-9","md":"col-md-9","sm":""}}} -->
									<div class="wp-block-column is-vertically-aligned-center col-9 col-md-9"><!-- wp:heading {"level":3,"textColor":"color-31","className":"","fontSize":"h-5","dynamic_fields":[{"key":"content","title":"Headline","enable":true},{"key":"level","title":"Headline H-Tag","enable":true}]} -->
									<h3 class="has-color-31-color has-text-color has-h-5-font-size">Our Sixth Service</h3>
									<!-- /wp:heading -->
									
									<!-- wp:paragraph {"className":"","fontSize":"h-6","dynamic_fields":[{"key":"content","title":"Subline","enable":true}]} -->
									<p class="has-h-6-font-size" id="please-enter-a-subline-h6"><strong>Get more information from your reportings by using our Sixth Service</strong></p>
									<!-- /wp:paragraph --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:greyd/box --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns -->
									
									<!-- wp:columns {"className":"row_xxl ","row":{"type":"row_xxl"}} -->
									<div class="wp-block-columns row_xxl"><!-- wp:column {"className":"col-12 ","responsive":{"width":{"sm":""}}} -->
									<div class="wp-block-column col-12"><!-- wp:spacer {"height":150,"className":"dyn ","dynamic_fields":[{"key":"height","title":"Space Bottom (Standard 150px)","enable":true}],"responsive":{"height":{"sm":"50%"}}} -->
									<div style="height:150px" aria-hidden="true" class="wp-block-spacer dyn"></div>
									<!-- /wp:spacer --></div>
									<!-- /wp:column --></div>
									<!-- /wp:columns --></div>
									<!-- /wp:group -->',
			),
		);
		

		/**
		 * use fetched remote patterns from the remote_patterns.json
		 */
		$remote_block_patterns = $this->get_remote_block_patterns();
		if ( is_array($remote_block_patterns) && count($remote_block_patterns) ) {
			$block_patterns = array_merge($remote_block_patterns, $block_patterns);
		}
		
		foreach ($block_patterns as $title => $content) {
			register_block_pattern(
				$title,
				$content
			);
		}
	}

	/**
	 * Whether the Greyd pattern library is hidden.
	 * 
	 * @deprecated since 1.2.2
	 * 
	 * @return bool
	 */
	public function hide_pattern_library() {
		$settings = get_option('settings_site_greyd_tp', null);
		return (
			$settings && 
			is_array($settings) && 
			isset($settings['template_library']) && 
			isset($settings['template_library']['hide_pattern_library']) && 
			$settings['template_library']['hide_pattern_library'] == "true"
		);
	}

	/**
	 * Get remote block patterns. Fetch if not fetched already.
	 * 
	 * @return array
	 */
	public function get_remote_block_patterns() {
		$remote_block_patterns = $this->read_block_pattern_json();
		if ( is_array($remote_block_patterns) ) {
			return $remote_block_patterns;
		}
		else {
			$remote_block_patterns = $this->fetch_block_patterns();
			if ( is_array($remote_block_patterns) ) {
				return $remote_block_patterns;
			}
		}
		return array();
	}

	/**
	 * Register event to fetch the block patterns on a regular basis.
	 */
	public function register_pattern_fetch_event() {
		if ( ! wp_next_scheduled( 'greyd_fetch_block_patterns_event' ) ) {
			wp_schedule_event( time(), "weekly", "greyd_fetch_block_patterns_event", array()); 
		}
	}

	/**
	 * Fetch block patterns from endpoint.
	 * 
	 * @return array|false
	 */
	public function fetch_block_patterns() {

		if ( ! is_admin() ) return false;

		$response = wp_remote_get(
			"https://greyd-resources.de/wp-json/greyd/v1/template_library/patterns/",
			array(
				'timeout' => 10, //seconds
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( !$response || is_wp_error( $response ) || !is_array($response) ) {
			return false;
		}
		try {
			$raw_patterns = json_decode($response['body'])->data->responseData->posts;
		} catch (\Exception $e) {
			return false;
		}

		$patterns = array();
		foreach ($raw_patterns as $raw_pattern) {

			$name = $raw_pattern->name;
			$categories = array('greyd-library', 'greyd');

			// try to match to more categories
			if ( strpos($name, 'header') !== false ) {
				$categories[] = 'greyd-header';
			}
			if ( strpos($name, 'section') !== false || strpos($name, 'grid') !== false ) {
				$categories[] = 'greyd-sections';
			}
			if ( strpos($name, 'tile') !== false ) {
				$categories[] = 'greyd-tiles';
			}
			if ( strpos($name, 'separator') !== false ) {
				$categories[] = 'greyd-separators';
			}
			if ( strpos($name, 'form') !== false ) {
				$categories[] = 'greyd-forms';
			}
			if ( strpos($name, 'footer') !== false ) {
				$categories[] = 'greyd-footer';
			}

			$patterns['greyd-'.$name] = array(
				'title' 		=> $raw_pattern->title,
				'categories' 	=> $categories,
				'content' 		=> $raw_pattern->content
			);
		}

		$this->write_block_pattern_json( $patterns );

		return $patterns;
	}


	/**
	 * Write fetched block patterns to local JSON file.
	 * 
	 * @return bool
	 */
	public function write_block_pattern_json( $patterns ) {

		$path = wp_upload_dir()['basedir']."/greyd_patterns"; 

		if ( !file_exists($path) ) {
			mkdir($path, 0755, true);
		}

		return boolval( file_put_contents(
			$path."/remote_patterns.json",
			json_encode($patterns)
		) );
	}

	/**
	 * Get fetched block patterns from local JSON file.
	 * 
	 * @return array|false
	 */
	public function read_block_pattern_json() {

		$json_path = wp_upload_dir()['basedir']."/greyd_patterns/remote_patterns.json";

		if ( !file_exists($json_path) ) return false;

		try {
			$contents = file_get_contents($json_path);
			$patterns = json_decode( $contents, true );
		} catch (\Exception $e) {
			return false;
		}
		return $patterns;
	}
}
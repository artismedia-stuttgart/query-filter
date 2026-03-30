<?php
/*
Feature Name:   Blocks
Description:    Register Greyd Blocks.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       2
Forced:         true
*/
namespace greyd\blocks;
// namespace Greyd\Block;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if greyd_blocks <= 1.14.0 is still active
if ( !\Greyd\Features::load_blocks_features() ) return;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\blocks\blocks' ) ) return;

// keep version constant for compatibility
if ( !defined( 'GREYD_BLOCKS_VERSION' ) ) {
	define( 'GREYD_BLOCKS_VERSION', GREYD_VERSION );
}

// includes
require_once __DIR__.'/blocks.php';
require_once __DIR__.'/enqueue.php';
require_once __DIR__.'/data.php';
require_once __DIR__.'/helper.php';
require_once __DIR__.'/render.php';
require_once __DIR__.'/meta.php';
require_once __DIR__.'/patterns.php';

// blocks
require_once __DIR__.'/blocks/image/init.php';
require_once __DIR__.'/blocks/button/init.php';
require_once __DIR__.'/blocks/accordion/init.php';
require_once __DIR__.'/blocks/list/init.php';
require_once __DIR__.'/blocks/popover/init.php';
require_once __DIR__.'/blocks/form/init.php';
require_once __DIR__.'/blocks/conditional-content/init.php';
require_once __DIR__.'/blocks/search/init.php';
require_once __DIR__.'/blocks/tabs/init.php';
require_once __DIR__.'/blocks/hotspot/init.php';
require_once __DIR__.'/blocks/menu/init.php';
require_once __DIR__.'/blocks/navigation/init.php';
require_once __DIR__.'/blocks/iframe/init.php';
require_once __DIR__.'/blocks/anchor/init.php';
require_once __DIR__.'/blocks/woocommerce/init.php';

// JSX Blocks
require_once __DIR__.'/jsx-blocks/init.php';

// Core Blocks
require_once __DIR__.'/core-blocks/image/init.php';
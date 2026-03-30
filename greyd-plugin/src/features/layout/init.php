<?php
/*
Feature Name:   Layout
Description:    Advanced Layout Features.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Requires Features: blocks
Forced:         true
*/

/**
 * Advanced Layout Features:
 * - core/columns extensions
 * - core/column extensions
 * - greyd/box block
 * - Background feature
 * - Grid
 */
namespace greyd\blocks\layout;
// namespace Greyd\Layout;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if greyd_blocks <= 1.14.0 is still active
if ( !\Greyd\Features::load_blocks_features() ) return;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\blocks\layout\Enqueue' ) ) return;

// includes
require_once __DIR__.'/enqueue.php';
require_once __DIR__.'/render.php';

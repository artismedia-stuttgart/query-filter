<?php
/*
Feature Name:   Animations
Description:    Animate almost any website element, choosing from a wide variety of effects and triggers.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Requires Features: blocks
*/
namespace greyd\animations;
// namespace Greyd\Animations;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if greyd_blocks <= 1.14.0 is still active
if ( !\Greyd\Features::load_blocks_features() ) return;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\animations\Enqueue' ) ) return;

require_once __DIR__.'/enqueue.php';
require_once __DIR__.'/render.php';

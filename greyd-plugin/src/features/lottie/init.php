<?php
/*
Feature Name:   Lottie
Description:    Integrate SVG animations from LottieFiles as images or backgrounds.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Requires Features: blocks
*/
namespace greyd\blocks\lottie;
// namespace Greyd\Lottie;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if greyd_blocks <= 1.14.0 is still active
if ( !\Greyd\Features::load_blocks_features() ) return;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\blocks\lottie\Manage' ) ) return;

// includes
require_once __DIR__.'/manage.php';

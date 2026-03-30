<?php
/*
Feature Name:   Trigger
Description:    Use buttons or boxes to trigger custom events and build your own accordions, tabs, modals, etc.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       98
Forced:         true
*/
namespace greyd\blocks\trigger;
// namespace Greyd\Trigger;

if ( !defined( 'ABSPATH' ) ) exit;

// escape if greyd_blocks <= 1.14.0 is still active
if ( !\Greyd\Features::load_blocks_features() ) return;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\blocks\trigger\Manage' ) ) return;

// includes
require_once __DIR__.'/manage.php';
require_once __DIR__.'/render.php';

<?php
/*
Feature Name:   Aria Attributes
Description:    Add ARIA labels and attributes to blocks for better accessibility.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        2.17.0
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Requires Features: blocks
*/
namespace greyd\aria_attributes;

if ( ! defined( 'ABSPATH' ) ) exit;

// escape if feature still runs in other plugin
if ( class_exists( 'greyd\aria_attributes\Enqueue' ) ) return;

require_once __DIR__.'/enqueue.php';
require_once __DIR__.'/render.php';

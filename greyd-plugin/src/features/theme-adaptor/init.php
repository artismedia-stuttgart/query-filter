<?php
/*
Feature Name:   Theme Adaptor
Description:    This feature ensures consistent styling for essential CSS elements like accordions, buttons, and tabs, even when the GREYD theme isn’t active. Designers and developers can customize styles by copying stylesheets into their own themes.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        1.0.0
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Forced:         true
*/

if ( !defined( 'ABSPATH' ) ) exit;

require_once __DIR__.'/enqueue.php';

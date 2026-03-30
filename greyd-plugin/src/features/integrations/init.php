<?php
/*
Feature Name:   Integrations
Description:    Third-party plugin integrations for Greyd
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        1.0
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Forced:         true
*/
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/polylang/filters.php';
require_once __DIR__ . '/wpml/filters.php';
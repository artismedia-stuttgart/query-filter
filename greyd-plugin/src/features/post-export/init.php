<?php
/*
Feature Name:   Post Import & Export
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        0.9
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       10
Forced:         true
*/
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/functions-nested-content-patterns.php';
require_once __DIR__ . '/class-preparred-post.php';
require_once __DIR__ . '/class-post-export-helper.php';
require_once __DIR__ . '/class-post-export.php';
require_once __DIR__ . '/class-post-import.php';

if ( is_admin() ) {
	require_once __DIR__ . '/class-post-export-admin.php';
	require_once __DIR__ . '/theme-export/init.php';

	require_once __DIR__ . '/deprecated/vc-nested-content-patterns.php';
}
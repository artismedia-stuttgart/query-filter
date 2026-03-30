<?php
/**
 * Features initialization file.
 * 
 * This file loads all features-related functionality.
 */
namespace Greyd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the main Features class first (contains all utility functions and API)
require_once __DIR__ . '/features.php';

// Load admin functionality (rendering, menu functions, and enqueue functionality)
require_once __DIR__ . '/admin.php';

// Load AJAX handling (save processing and future AJAX endpoints)
require_once __DIR__ . '/ajax.php';

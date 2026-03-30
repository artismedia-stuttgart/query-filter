<?php
/**
 * Main Template for dynamic system templates.
 * Used by @filter template_include in dynamic.php
 */

if (!defined('ABSPATH')) exit;

get_header();
    do_action('dynamic_hook');
get_footer();

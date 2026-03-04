<?php

/**
 * Plugin Name:       Query Loop Filters (modified)
 * Description:       Filter blocks for the query loop utilising the interactivity API.
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Version:           0.2.6
 * Author:            Human Made Limited and Kaith Menken
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       query-filter
 *
 * @package           query-filter
 */

namespace HM\Query_Loop_Filter;

const PLUGIN_FILE = __FILE__;
const ROOT_DIR = __DIR__;

require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$queryFilterUpdateChecker = PucFactory::buildUpdateChecker(
  'https://github.com/artismedia-stuttgart/query-filter',
  __FILE__,
  'query-filter'
);

//Set the branch that contains the stable release.
$queryFilterUpdateChecker->setBranch('main');

require_once __DIR__ . '/inc/namespace.php';

bootstrap();

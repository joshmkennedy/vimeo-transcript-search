<?php
/**
 * Plugin Name:     Vimeo Transcript Search
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     vimeo-transcript-search
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Vimeo_Transcript_Search
 */

// Your code starts here.

require_once __DIR__ . '/vendor/autoload.php';

\Sentry\init([
  'dsn' => 'https://7d5ba03eb477f2dc37c4e00ae13a6a6f@o4510275695804416.ingest.us.sentry.io/4510290964971520',
]);

use Jk\Vts\Plugin;

$plugin = new Plugin(
	__FILE__
);
$plugin->run();

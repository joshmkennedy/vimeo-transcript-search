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

use Jk\Vts\Plugin;

$plugin = new Plugin(
	__FILE__
);
$plugin->run();

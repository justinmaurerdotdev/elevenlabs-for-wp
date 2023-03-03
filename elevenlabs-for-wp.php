<?php

/*
Plugin Name: Elevenlabs For Wp
Plugin URI: https://justinmaurer.dev
Description: A WordPress plugin to allow creation of audio readings for posts
Version: 1.0.0
Author: Justin Maurer
Author URI: https://justinmaurer.dev
License: MIT
*/

use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;
use WebUsUp\ElevenLabsForWp\FileHelper;

require 'vendor/autoload.php';
const WUU_ELEVENLABS_VERSION = '1.0.0';
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$GLOBALS['wuu_elevenlabs_filehelper'] = new FileHelper(__FILE__);
$plugin = new ElevenLabsForWp();
register_activation_hook(__FILE__, [$plugin, 'activation']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivation']);
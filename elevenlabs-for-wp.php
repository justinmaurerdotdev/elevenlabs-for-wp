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

require 'vendor/autoload.php';
const WUU_ELEVENLABS_VERSION = '1.0.0';
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
$template_dir_path = dirname(__FILE__) .'/templates';
$styles_dir_url = plugins_url('/', __FILE__) .'/styles';

$plugin = new ElevenLabsForWp($template_dir_path, $styles_dir_url);
register_activation_hook(__FILE__, [$plugin, 'activation']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivation']);
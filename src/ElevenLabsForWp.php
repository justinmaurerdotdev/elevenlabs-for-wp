<?php

namespace WebUsUp\ElevenLabsForWp;

use ElevenLabs\V1\SDK\ElevenLabsAPIClient;
use WebUsUp\ElevenLabsForWp\Admin\MainSettingsPage;
use WebUsUp\ElevenLabsForWp\Admin\PostTools;

/**
 * This is the main plugin class
 */
class ElevenLabsForWp {

	public string $plugin_version = WUU_ELEVENLABS_VERSION;
	public ElevenLabsAPIClient $api_client;
	public string $uploads_relative_dir = 'wuu-elevenlabs';
	public string $uploads_absolute_dir;

	public string $template_dir_path;
	public string $styles_dir_url;

	public function __construct($template_dir_path, $styles_dir_url) {
		$this->template_dir_path = $template_dir_path;
		$this->styles_dir_url = $styles_dir_url;
		add_action('init', ['WebUsUp\ElevenLabsForWp\FileHelper', 'make_global_filesystem_object'], 10 );
		add_action('init', [$this, 'setup_uploads_dir'], 20 );
		$creds = constant('ELEVENLABS_API_KEY');
		if (!$creds) {
			add_action( 'admin_notices', [$this, 'missing_credentials_warning'] );
		} else {
			$this->api_client = new ElevenLabsAPIClient();
		}


		$this->bootstrap_admin();
	}
	public function activation() {
		// set up filesystem

		// set up DB tables
	}

	public function deactivation() {
		// possibly delete files

		// possibly delete DB tables
	}

	public function setup_uploads_dir() {
		$uploads_dir_info = wp_upload_dir();
		$path = $uploads_dir_info['basedir'] . '/' . $this->uploads_relative_dir;
		$this->uploads_absolute_dir = $path;
		FileHelper::init_file_dir($path);
	}

	public function missing_credentials_warning() {
		echo '<div class="notice notice-warning is-dismissible">
	      <p>Missing credentials for ElevenLabs audio generation. Please set the ELEVENLABS_API_KEY constant in wp-config.php.</p>
      	</div>';
	}

	public function bootstrap_admin() {
		$settings_page = new MainSettingsPage($this);
		$post_tools = new PostTools($this);
	}
}
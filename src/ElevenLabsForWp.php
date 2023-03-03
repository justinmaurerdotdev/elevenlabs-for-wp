<?php

namespace WebUsUp\ElevenLabsForWp;

use AmpProject\Validator\Spec\Tag\Em;
use ElevenLabs\V1\SDK\ElevenLabsAPIClient;
use WebUsUp\ElevenLabsForWp\Admin\AuthorProfile;
use WebUsUp\ElevenLabsForWp\Admin\MainSettingsPage;
use WebUsUp\ElevenLabsForWp\Admin\PostTools;
use WebUsUp\ElevenLabsForWp\FrontEnd\Embed;

/**
 * This is the main plugin class
 */
class ElevenLabsForWp {
	public ElevenLabsAPIClient $api_client;

	public function __construct() {

		$creds = constant('ELEVENLABS_API_KEY');
		if (!$creds) {
			add_action( 'admin_notices', [$this, 'missing_credentials_warning'] );
		} else {
			$this->api_client = new ElevenLabsAPIClient();
		}

		$this->bootstrap_admin();
		$this->bootstrap_front_end();
	}
	public function activation() {
		// set up filesystem

		// set up DB tables
	}

	public function deactivation() {
		// possibly delete files

		// possibly delete DB tables
	}



	public function missing_credentials_warning() {
		echo '<div class="notice notice-warning is-dismissible">
	      <p>Missing credentials for ElevenLabs audio generation. Please set the ELEVENLABS_API_KEY constant in wp-config.php.</p>
      	</div>';
	}

	public function bootstrap_admin() {
		$settings_page = new MainSettingsPage($this->api_client);
		$post_tools = new PostTools($this->api_client);
		$author_profile = new AuthorProfile($this->api_client);
	}

	public function bootstrap_front_end() {
		$embed = new Embed();
	}
}
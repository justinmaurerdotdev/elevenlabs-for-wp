<?php

namespace WebUsUp\ElevenLabsForWp\Admin;

use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;
use WebUsUp\ElevenLabsForWp\WUUElevenLabsVoice;

class MainSettingsPage {
	private string $minimum_capability = 'manage_options';
	private string $title = 'ElevenLabs Audio';
    public string $slug = 'wuu-elevenlabs-settings';
	private ElevenLabsForWp $plugin;
	private AdminAPITools $api_tools;

	public function __construct(ElevenLabsForWp $plugin) {
		$this->plugin = $plugin;
		$this->api_tools = new AdminAPITools($this->plugin->api_client);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_menu', [$this, 'add_menu_page']);
	}

    public function enqueue() {
        $url = $this->plugin->styles_dir_url . '/admin/main-settings-page.css';
        wp_enqueue_style('wuu-main-settings', $url, [], $this->plugin->plugin_version);
    }

	public function add_menu_page() {
		add_submenu_page(
			'options-general.php',
			$this->title,
			$this->title,
			$this->minimum_capability,
			$this->slug,
			[$this, 'render_settings_page']
		);
	}

	public function render_settings_page() {
		// check user capabilities
		if (!current_user_can($this->minimum_capability)) {
			return;
		}

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'wuu_settings_messages', 'wuu_message', __( 'Settings Saved', 'wuuel' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'wuu_settings_messages' );

		include_once $this->plugin->template_dir_path .'/admin/main-settings-page.php';
	}

	public function register_settings() {
		register_setting( 'wuu_global', 'wuu_preferred_voice' );

		add_settings_section(
			'wuu_section_global_prefs',
			__( 'Settings', 'wuuel' ),
			[$this, 'render_voice_prefs_section_header'],
			'wuu-elevenlabs'
		);
		// Register a new field in the "wporg_section_developers" section, inside the "wporg" page.
		add_settings_field(
			'wuu_preferred_voice_field', // As of WP 4.6 this value is used only internally.
			// Use $args' label_for to populate the id inside the callback.
			__( 'Global Preferred Voice', 'wuuel' ),
			[$this, 'render_voice_prefs_field'],
			'wuu-elevenlabs',
			'wuu_section_global_prefs',
			array(
				'label_for'         => 'wuu_field_global_voice_pref',
				'class'             => 'wuu-voicelist-voice',
				'wporg_custom_data' => 'custom',
			)
		);

		global $pagenow;
		if ($pagenow === 'options-general.php' && $_GET['page'] === $this->slug) {
			add_action('admin_enqueue_scripts', [$this, 'enqueue']);
		}
	}

	public function render_voice_prefs_section_header() {
		?>
		<h2>Global Voice Preference</h2>
		<?php
	}

	public function render_voice_prefs_field() {
		$global_preferred_voice = get_option('wuu_preferred_voice');
        var_dump($global_preferred_voice);
		$voices = $this->api_tools->get_voices();
        $voice_count = 1;
		foreach ($voices as $voice) {
			?>
            <div class="wuu-voicelist-voice">
                <input type="radio" id="voice-<?= $voice_count ?>" value="<?= $voice->voice_id ?>" name="wuu_preferred_voice" <?= $global_preferred_voice === $voice->voice_id ? 'checked' : '' ?>>
                <label class="wuu-voicelist-voicetitle" for="voice-<?= $voice_count ?>"><?= $voice->name ?></label>
                <p class="wuu-voicelist-voicecategory">Type: <?= $voice->category ?></p>
                <div class="wuu-voicelist-sample">
                    <?php echo do_shortcode('[audio src=' . $voice->preview_url . ']'); ?>
                </div>
            </div>
			<?php
            ++$voice_count;
		}
	}
}
<?php

namespace WebUsUp\ElevenLabsForWp\Admin;

use ElevenLabs\V1\SDK\ElevenLabsAPIClient;
use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;

class AuthorProfile {
	private AdminAPITools $api_tools;

	public function __construct(ElevenLabsAPIClient $api_client) {
		$this->api_tools = new AdminAPITools($api_client);

		// add the field to user's own profile editing screen
		add_action( 'edit_user_profile', [$this, 'render_profile_settings'] );
		// add the field to user profile editing screen
		add_action( 'show_user_profile', [$this, 'render_profile_settings'] );

		add_action( 'edit_user_profile_update', [$this, 'save_voice_prefs'] );
		add_action( 'personal_options_update', [$this, 'save_voice_prefs'] );

        add_action('admin_init', [$this, 'init']);
	}

    public function init() {
	    global $pagenow;
	    if ($pagenow === 'user-edit.php' || $pagenow === 'profile.php') {
		    add_action('admin_enqueue_scripts', [$this, 'enqueue']);
	    }
    }

	public function enqueue() {
        global $wuu_elevenlabs_filehelper;
		$url = $wuu_elevenlabs_filehelper->styles_dir_url . '/admin/user-profile-page.css';
		wp_enqueue_style('wuu-user-profile', $url, [], WUU_ELEVENLABS_VERSION);
	}

	public function render_profile_settings($user) {
		if (!current_user_can('edit_user', $user->ID) || !current_user_can('edit_users')) {
			return false;
		}
		?>
		<table class="form-table">
			<h3>ElevenLabs Audio Preferences</h3>
			<?php $this->render_voice_prefs_field($user->ID); ?>
		</table>
		<?php
	}

	public function render_voice_prefs_field($user_id) {
		$author_preferred_voice = get_user_meta($user_id, 'wuu_author_preferred_voice', true);
		$voices = $this->api_tools->get_voices();
		$voice_count = 1;
		foreach ($voices as $voice) {
			?>
			<div class="wuu-voicelist-voice">
				<input type="radio" id="voice-<?= $voice_count ?>" value="<?= $voice->voice_id ?>" name="wuu_author_preferred_voice" <?= checked( $voice->voice_id,$author_preferred_voice ) ?>>
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

    public function save_voice_prefs($user_id) {
        if ( isset($_POST['wuu_author_preferred_voice']) ) {
            $preferred_voice_id = sanitize_text_field($_POST['wuu_author_preferred_voice']);
            update_user_meta($user_id, 'wuu_author_preferred_voice', $preferred_voice_id);
        }
    }
}
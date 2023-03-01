<?php

namespace WebUsUp\ElevenLabsForWp\Admin;

use \Exception;
use WebUsUp\ElevenLabsForWp\APIException;
use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;
use WebUsUp\ElevenLabsForWp\FileHelper;

class PostTools {
	private ElevenLabsForWp $plugin;
	private AdminAPITools $api_tools;
	public function __construct($plugin) {
        $this->plugin = $plugin;
		$this->api_tools = new AdminAPITools($this->plugin->api_client);
		add_action( 'add_meta_boxes', [$this, 'add_meta_box'] );
		add_action( 'save_post', [$this, 'save_meta_box'] );
        add_action('transition_post_status', [$this, 'generate_reading_on_publish'], 10, 3);
        add_action('wuu_elevenlabs_post_cron', [$this, 'text_to_speech']);
	}

	public function add_meta_box() {
		$post_types = get_post_types(['public' => true]);
		foreach ($post_types as $post_type) {
			add_meta_box(
				'wuu_elevenlabs_reading',
				'ElevenLabs Reading',
				[$this, 'render_meta_box'],
				$post_type
			);
		}
	}

	public function render_meta_box(\WP_Post $post) {
		$reading = (bool) get_post_meta($post->ID, 'wuu_elevenlabs_reading', true);
        $show_reading = (bool) get_post_meta($post->ID, 'wuu_elevenlabs_show_reading', true);
		?>

		<input type="checkbox" id="wuu_elevenlabs_reading_toggle" name="wuu_elevenlabs_reading" value="1" <?php checked($reading) ?>>
		<label for="wuu_elevenlabs_reading_toggle">Generate a reading for this post, when published?</label><br>
        <input type="checkbox" id="wuu_elevenlabs_show_reading_toggle" name="wuu_elevenlabs_show_reading" value="1" <?php checked($show_reading) ?> <?php disabled($reading, false) ?>>
        <label for="wuu_elevenlabs_show_reading_toggle">Show the reading on the front end?</label>
        <script>
            jQuery("#wuu_elevenlabs_reading_toggle").change(function() {
                console.log(jQuery(this));
                if (jQuery(this).is(":checked")) {
                    jQuery("#wuu_elevenlabs_show_reading_toggle").attr('disabled', false).prop('checked', true);
                } else {
                    jQuery("#wuu_elevenlabs_show_reading_toggle").attr('disabled', true).prop('checked', false);
                }
            })
        </script>
        <?php
	}

	public function save_meta_box($post_id) {
		if ( array_key_exists( 'wuu_elevenlabs_reading', $_POST ) ) {
			$reading = (bool) $_POST['wuu_elevenlabs_reading'];
			update_post_meta($post_id, 'wuu_elevenlabs_reading', $reading);
		} else {
			update_post_meta($post_id, 'wuu_elevenlabs_reading', false);
		}

        if ( array_key_exists( 'wuu_elevenlabs_show_reading', $_POST ) ) {
			$reading = (bool) $_POST['wuu_elevenlabs_show_reading'];
			update_post_meta($post_id, 'wuu_elevenlabs_show_reading', $reading);
		} else {
			update_post_meta($post_id, 'wuu_elevenlabs_show_reading', false);
		}
	}

    public function generate_reading_on_publish(string $new_status, string $old_status, \WP_Post $post) {
        if ($new_status === 'publish') {
            $reading = get_post_meta($post->ID, 'wuu_elevenlabs_reading', true);
            $reading_data = get_post_meta($post->ID, 'wuu_elevenlabs_reading_data', true);
            // if $reading_data exists, a reading has already been generated for this post. don't generate another one.
            if ($reading && !$reading_data) {
                // TODO: Should generate the reading asynchronously, so the post can be published without waiting for the API calls
                try {
                    $this->text_to_speech($post->ID);
                } catch (APIException $e) {
                    error_log(var_export($e, true), true);
                }
            }
        }
    }

    public function get_voice_for_post($post_id) {
	    $global_preferred_voice = get_option('wuu_preferred_voice');
        $voice_for_post = $global_preferred_voice;
        // TODO: if the post author has a preferred voice, use that. (what to do about CoAuthors?)
        return $voice_for_post;
    }

    public function get_text_for_post($post_id) {
        $post = get_post($post_id);
        $content = wp_strip_all_tags( $post->post_content );
        return $content;
    }

	/**
	 * @throws \Exception
	 */
	public function text_to_speech($post_id) {
        $voice_id = $this->get_voice_for_post($post_id);
        if (!$voice_id) {
            $voices = $this->api_tools->get_voices();
            if ( isset($voices[0]) ) {
                $voice_id = $voices[0]->voice_id;
            } else {
                throw new APIException("No voices returned from the API");
            }
        }
        $content = $this->get_text_for_post($post_id);
        if ($content) {
            $body = new \ElevenLabs\V1\SDK\Model\BodyTextToSpeechV1TextToSpeechVoiceIdPost();
            $body = $body->setText($content);
            // TODO: What should I do with this audio?
            $audio = $this->plugin->api_client->text_to_speech($voice_id, $body);
            error_log(var_export($audio, true));
            $post_type = get_post_type($post_id);
            if ($audio) {
                FileHelper::put_content($this->plugin->uploads_absolute_dir . '/'.'reading-'.$post_type . '-' . $post_id .'.mp3', $audio);
            }
        }
    }
}
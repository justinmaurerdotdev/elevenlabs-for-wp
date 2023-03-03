<?php

namespace WebUsUp\ElevenLabsForWp\Admin;

use ElevenLabs\V1\SDK\ElevenLabsAPIClient;
use \Exception;
use WebUsUp\ElevenLabsForWp\APIException;
use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;
use WebUsUp\ElevenLabsForWp\FilesystemException;
use WebUsUp\ElevenLabsForWp\PluginSettingsException;
use WebUsUp\ElevenLabsForWp\Reading;

class PostTools {
	private AdminAPITools $api_tools;
	private ElevenLabsAPIClient $api_client;

	public function __construct( ElevenLabsAPIClient $api_client ) {
        $this->api_client = $api_client;
		$this->api_tools = new AdminAPITools( $this->api_client );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
		add_action( 'transition_post_status', [ $this, 'generate_reading_on_publish' ], 10, 3 );
//		add_action( 'wuu_elevenlabs_post_cron', [ $this, 'text_to_speech' ] );
	}

	public function add_meta_box() {
		$post_types = get_post_types( [ 'public' => true ] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wuu_elevenlabs_reading',
				'ElevenLabs Reading',
				[ $this, 'render_meta_box' ],
				$post_type
			);
		}
	}

	public function render_meta_box( \WP_Post $post ) {
		$reading      = (bool) get_post_meta( $post->ID, 'wuu_elevenlabs_reading', true );
		$show_reading = (bool) get_post_meta( $post->ID, 'wuu_elevenlabs_show_reading', true );
		?>

        <input type="checkbox" id="wuu_elevenlabs_reading_toggle" name="wuu_elevenlabs_reading"
               value="1" <?php checked( $reading ) ?>>
        <label for="wuu_elevenlabs_reading_toggle">Generate a reading for this post, when published?</label><br>
        <input type="checkbox" id="wuu_elevenlabs_show_reading_toggle" name="wuu_elevenlabs_show_reading"
               value="1" <?php checked( $show_reading ) ?> <?php disabled( $reading, false ) ?>>
        <label for="wuu_elevenlabs_show_reading_toggle">Show the reading on the front end?</label>
        <script>
            jQuery("#wuu_elevenlabs_reading_toggle").change(function () {
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

	public function save_meta_box( $post_id ) {
		if ( array_key_exists( 'wuu_elevenlabs_reading', $_POST ) ) {
			$reading = (bool) $_POST['wuu_elevenlabs_reading'];
			update_post_meta( $post_id, 'wuu_elevenlabs_reading', $reading );
		} else {
			update_post_meta( $post_id, 'wuu_elevenlabs_reading', false );
		}

		if ( array_key_exists( 'wuu_elevenlabs_show_reading', $_POST ) ) {
			$show_reading = (bool) $_POST['wuu_elevenlabs_show_reading'];
			update_post_meta( $post_id, 'wuu_elevenlabs_show_reading', $show_reading );
		} else {
			update_post_meta( $post_id, 'wuu_elevenlabs_show_reading', false );
		}
	}

	public function generate_reading_on_publish( string $new_status, string $old_status, \WP_Post $post ) {
		if ( $new_status === 'publish' ) {
			$reading      = get_post_meta( $post->ID, 'wuu_elevenlabs_reading', true );
            if ($reading) {
                // TODO: Should generate the reading asynchronously (or at least in a cron job), so the post can be published without waiting for the API calls
                try {
                    $this->text_to_speech( $post->ID );
                } catch ( APIException $e ) {
                    // TODO: add an alert to the post edit page
                    error_log( var_export( $e, true ), true );
                }
            }
		}
	}

	/**
	 * @throws PluginSettingsException
	 */
	public function get_voice_for_post( $post_id ) {
		$global_preferred_voice = get_option( 'wuu_preferred_voice' );
		$author_id = get_post_field ('post_author', $post_id);
        $author_preferred_voice = get_user_meta($author_id, 'wuu_author_preferred_voice', true);
		$voice_for_post         = $author_preferred_voice ?: $global_preferred_voice;
        if (!$voice_for_post) {
            throw new PluginSettingsException("No voice preference has been set globally, or for the post's author. Please, select a voice and try again.");
        }
		// TODO: if the post author has a preferred voice, use that. (what to do about CoAuthors?)
		return $voice_for_post;
	}

	public static function get_text_for_post( $post_id ): string {
		$post    = get_post( $post_id );
		$content = wp_strip_all_tags( $post->post_content );

		return $content;
	}

	/**
	 * @throws APIException
	 * @throws Exception
	 */
	public function text_to_speech( $post_id ) {
		$reading = Reading::get_reading_for_post( $post_id);

        $voice_id = $this->get_voice_for_post( $post_id );
		if ( ! $voice_id ) {
			$voices = $this->api_tools->get_voices();
			if ( isset( $voices[0] ) ) {
				$voice_id = $voices[0]->voice_id;
			} else {
				throw new APIException( "No voices returned from the API" );
			}
		}
		$content = self::get_text_for_post( $post_id );

		// TODO: this needs to account for the 2500/5000 character limit for generation. Is it possible to render multiple, and then combine them?
		if ( $content ) {
            // Don't regenerate the text if it is the same as the last attempt
            $hashed_content = md5($content);
            if ( !$reading || (isset($reading->hash) && $hashed_content !== $reading->hash) ) {
	            $body = new \ElevenLabs\V1\SDK\Model\BodyTextToSpeechV1TextToSpeechVoiceIdPost();
	            $body = $body->setText( $content );
	            $audio     = $this->api_client->text_to_speech( $voice_id, $body );
	            if ( $audio && is_string($audio) ) {
		            // store the URL of the file
		            $reading_data = ['hash' => $hashed_content, 'post_id' => $post_id];
                    try {
                        Reading::new_reading($reading_data, $audio);
                    } catch (FilesystemException $exception) {
                        // An issue with storing the file
                        // TODO: set an alert at the top of the post edit page
                    } catch (Exception $e) {
                        // No Post ID provided. Probably not using this the right way.
                    }
	            }
            }
		}
	}
}
<?php

namespace WebUsUp\ElevenLabsForWp\Admin;

use ElevenLabs\V1\SDK\ElevenLabsAPIClient;
use WebUsUp\ElevenLabsForWp\WUUElevenLabsVoice;

class AdminAPITools {

	private ElevenLabsAPIClient $client;

	public function __construct(ElevenLabsAPIClient $client) {
		$this->client = $client;
	}

	/**
	 * @return WUUElevenLabsVoice[]
	 */
	public function get_voices(): array {
		if (! $wuu_voices = get_transient('wuu_elevenlabs_voices')) {
			error_log('DOING API CALL');
			$voices_response = $this->client->get_voices();
			$voices = $voices_response->getVoices();
			$wuu_voices = array_map(fn($voice) => new WUUElevenLabsVoice($voice), $voices);

			set_transient('wuu_elevenlabs_voices', $wuu_voices, 60*60*24);
		}
		return $wuu_voices;
	}
}
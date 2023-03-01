<?php

namespace WebUsUp\ElevenLabsForWp;

use ElevenLabs\V1\SDK\Model\VoiceResponseModel;

class WUUElevenLabsVoice {
	public string $voice_id;
	public string $name;
	public string $category;
	public string $preview_url;

	/**
	 * @param VoiceResponseModel[] $voices
	 */
	public function __construct(VoiceResponseModel $voice) {
		$this->voice_id = $voice->getVoiceId();
		$this->name = $voice->getName();
		$this->category = $voice->getCategory();
		$this->preview_url = $voice->getPreviewUrl();
	}
}
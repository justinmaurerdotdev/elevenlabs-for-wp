<?php

namespace WebUsUp\ElevenLabsForWp;

use Exception;

class Reading {
	public string $filename;
	public string $path;
	public string $url;
	public string $hash;
	public int $post_id;

	/**
	 * @throws Exception
	 * @throws FilesystemException
	 */
	public function __construct( $reading_data, $audio = null) {
		foreach ($reading_data as $key => $val) {
			if (property_exists($this, $key)) {
				$this->{$key} = $val;
			}
		}
		if (!$this->post_id) {
			throw new Exception('\'post_id\' is a required property for the $reading_data array');
		}

		if ($audio) {
			$this->store_file($audio);
			$this->save();
		}
	}

	/**
	 * @param int $post_id
	 *
	 * @return false|Reading
	 * @throws Exception
	 */
	public static function get_reading_for_post( int $post_id ) {
		$reading_data = get_post_meta($post_id, 'wuu_elevenlabs_reading_data', true);
		if ($reading_data) {
			if (!isset($reading_data['post_id'])) {
				$reading_data['post_id'] = $post_id;
			}
			return new self($reading_data);
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public static function new_reading( $reading_data, $audio): Reading {
		return new self($reading_data, $audio);
	}

	public function save() {
		$reading_data = (array) $this;
		unset($reading_data['post_id']);
		update_post_meta($this->post_id, 'wuu_elevenlabs_reading_data', $reading_data);
	}

	/**
	 * @throws FilesystemException
	 */
	public function store_file($audio) {
		$versions = 1;
		$post_type = get_post_type( $this->post_id );
		$filename = 'reading-' . $post_type . '-' . $this->post_id . '.mp3';
		global $wuu_elevenlabs_filehelper;
		while(file_exists($wuu_elevenlabs_filehelper->uploads_absolute_dir . '/' . $filename)) {
			$versions++;
			$filename = 'reading-' . $post_type . '-' . $this->post_id . '-'.$versions.'.mp3';
		}
		$this->filename = $filename;
		$this->path = $wuu_elevenlabs_filehelper->uploads_absolute_dir . '/' . $filename;

		$upload_dir = wp_upload_dir();
		$this->url = $upload_dir['baseurl'] .'/'. $wuu_elevenlabs_filehelper->uploads_relative_dir . '/' . $filename;
		FileHelper::put_content( $this->path, $audio );
	}
}
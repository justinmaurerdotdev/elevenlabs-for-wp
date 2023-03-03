<?php

namespace WebUsUp\ElevenLabsForWp\FrontEnd;

use WebUsUp\ElevenLabsForWp\ElevenLabsForWp;
use WebUsUp\ElevenLabsForWp\Reading;

class Embed {
	public function __construct() {
		add_action('the_post', [$this, 'maybe_render']);
	}

	public function maybe_render() {
		if (is_main_query()) {

			$id = get_queried_object_id();
			$post = get_post($id);
			if ($post instanceof \WP_Post) {

				$reading = (bool) get_post_meta($id, 'wuu_elevenlabs_reading');
				$show_reading = (bool) get_post_meta($id, 'wuu_elevenlabs_show_reading');

				if ($reading && $show_reading) {
					add_filter('the_content', [$this, 'render_embed_article_start']);
				}
			}

		}
	}
	public function render_embed_article_start($content) {
		if ( is_singular() && in_the_loop() && is_main_query() ) {
			$id = get_the_ID();
			$reading = Reading::get_reading_for_post($id);
			if ($reading->url) {
				$embed = do_shortcode('[audio src=' . $reading->url . ']');
				return $embed . $content;
			}
		}
		return $content;
	}

}
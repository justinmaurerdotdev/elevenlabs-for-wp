# ElevenLabs for WP
https://yoast.com/developer-blog/safely-using-php-dependencies-in-the-wordpress-ecosystem/
https://github.com/humbug/php-scoper

What I want to do with this plugin:
- As an admin, I want to be able to set my default voice, or choose "random"
- I want each author to have an option default voice that would override the global setting
- As an admin, I want to be able to view my subscription and account info
- As an author, I want to be able to add a ElevenLabs block to a post (only one!) and have it automatically generate the audio for the post, and a clean, skinnable audio player
- As an editor, I want to be able to view the recordings in the post edit area, and then take actions (re-run the generation?, change voice?)
- I want to make a custom voice for each of my authors, guest authors (Co-Authors Plus), or possibly just arbitrary voices.

# To-Do
- [ ] create a directory in /wp-content somewhere to store the audio files
- [ ] store a list of voice names and IDs (maybe refresh weekly, with optional manual sync)
- [ ] create a settings page to show subscription and set default voice (show a list of voices with samples)
- [ ] add the voice selection UI to the author pages
- [ ] build a block with a "generate" button that turns into an audio player when it's completed
- [ ] if the block is present, the audio should auto-generate (if it hasn't already been generated) upon the post being published
- [ ] connect to S3, use the stream version of the text-to-speech endpoint to stream the file to s3 directly, bypassing local storage

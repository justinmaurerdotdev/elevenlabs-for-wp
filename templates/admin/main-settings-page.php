<?php
/**
 * @var \WebUsUp\ElevenLabsForWp\WUUElevenLabsVoice[]|array $voices
 */
?>
<div class="wuu-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "wporg"
        settings_fields( 'wuu_global' );
        // output setting sections and their fields
        // (sections are registered for "wporg", each field is registered to a specific section)
        do_settings_sections( 'wuu-elevenlabs' );
        // output save settings button
        submit_button( 'Save Settings' );
        ?>
    </form>
</div>

<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

global $license_manager
?>
<div class="wdm-vertical-tabs-content">
    <div class='wrapper'>
        <h4 class='head_div'>This extension is part of WPSOLR PRO</h4>

        <div class="wdm_note">
            WPSOLR FREE is free, and delivers the powers of advanced search engines, including AI, to WordPress search.
            <br/><br/>
            If your project requires more features, or need to integrate with other plugins, you can buy them all with
            <a href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) ); ?>"
               target="_blank">WPSOLR
                PRO</a>.
            <br/><br/>
        </div>
    </div>
    <div class="clear"></div>
</div>
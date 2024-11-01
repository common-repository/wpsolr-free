<?php

use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WPSOLR_Events;


WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_IMPORT_EXPORT, true );

$extension_options_name = WPSOLR_Option::OPTION_IMPORT_EXPORT;
$settings_fields_name   = 'extension_import_export_opt';

$options          = WPSOLR_Service_Container::getOption()->get_option_import_export();
$is_plugin_active = WPSOLR_Extension::is_plugin_active( WPSOLR_Extension::OPTION_IMPORT_EXPORT );

$views_option   = WPSOLR_Service_Container::getOption()->get_option_view_views();
$indexes_option = WPSOLR_Service_Container::getOption()->get_option_indexes()[ WPSOLR_Option::OPTION_INDEXES_INDEXES ] ?? [];


/**
 * Delete options selected
 */
if ( ! empty( $_POST['wpsolr_action'] ) && ( 'wpsolr_action_delete_settings' === $_POST['wpsolr_action'] ) && ! empty( $_POST['wdm_import_export'] ) ) {
	$exports = [];
	foreach ( $_POST['wdm_import_export'] as $option_name => $option_data ) {
		delete_option( $option_name );
	}
}

// Import
$wpsolr_data_to_import_string = '';
if ( ! empty( $_POST['wpsolr_action'] ) && ( 'wpsolr_action_import_settings' === $_POST['wpsolr_action'] ) ) {

	// Remove escaped quotes added by the POST
	$wpsolr_data_to_import_string = ! empty( $_POST['wpsolr_data_to_import'] ) ? stripslashes( $_POST['wpsolr_data_to_import'] ) : '';
	if ( ! empty( $wpsolr_data_to_import_string ) ) {

		$wpsolr_data_to_import = json_decode( $wpsolr_data_to_import_string, true );

		foreach ( $wpsolr_data_to_import as $option_name => $option_data ) {
			if ( ! empty( $option_data ) ) {
				// Save the option
				update_option( $option_name, $option_data );
			} else {
				delete_option( $option_name );
			}
		}
	}
}

/**
 * Calculate options to display
 */
$option_views_to_export = [];
foreach ( wp_load_alloptions() as $option_name => $option_data ) {
	if ( ( ( 0 === strpos( $option_name, 'wdm_', 0 ) ) || ( 0 === strpos( $option_name, 'wpsolr_', 0 ) ) ) &&
	     get_option( $option_name ) ) {

		if ( ! ( WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name ]['is_exported'] ?? true ) ) {
			// Option is not to be exported
			continue;
		}


		$option_name_uuid         = WPSOLR_Regexp::extract_last_separator( $option_name, '_' );
		$option_name_without_uuid = substr( $option_name, 0, strlen( $option_name ) - strlen( '_' . $option_name_uuid ) );
		$view                     = $views_option[ $option_name_uuid ] ?? [];
		$index                    = $indexes_option[ $option_name_uuid ] ?? [];
		$label                    = 'all views and indexes';
		if ( WPSOLR_Service_Container::getOption()->get_is_option_type_view( $option_name_without_uuid ) ) {
			if ( isset( $view[ WPSOLR_Option::OPTION_VIEW_LABEL ] ) ) {
				$label = sprintf( 'view "%s"', $view[ WPSOLR_Option::OPTION_VIEW_LABEL ] );
			} else {
				$label = 'All views';
			}
		} elseif ( WPSOLR_Service_Container::getOption()->get_is_option_type_index( $option_name_without_uuid ) ) {
			if ( isset( $index['index_name'] ) ) {
				$label = sprintf( 'index "%s"', $index['index_name'] );
			} else {
				$label = 'All indexes';
			}
		}

		$description = WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name ]['description'] ??
		               WPSOLR_Option::OPTIONS_DEFINITIONS[ $option_name_without_uuid ]['description'] ??
		               sprintf( 'Missing: %s', $option_name );

		$option_views_to_export[ $label ][ $option_name ] = [
			'description' => $description,
			'data'        => get_option( $option_name ),
		];

	}
}

// Export
$exports = [];
foreach ( $option_views_to_export as $view_name => $option_views ) {
	foreach ( $option_views as $option_name => $option ) {

		if ( empty( $options ) || isset( $options[ $option_name ] ) ) {
			// Export options selected

			$exports[ $option_name ] = $option['data'];
		}
	}
}

?>

<style>
    .wpsolr_export_col {
        float: left;
        width: 200px;
        margin-bottom: 7px;
    }

    .wpsolr-export {
        margin-top: 20px;
    }
</style>

<?php if ( ! empty( $option_views_to_export ) ) { ?>
    <script>
        jQuery(document).ready(function ($) {

            $('form').on('submit', function (e) {

                if ('settings_form_id' === $(this).attr('id')) {

                    $('#wpsolr_delete_error').text('');

                    if (0 === $('.wpsolr-export .wpsolr_checked:checked').length) {
                        $('#wpsolr_delete_error').text('Please select at least one setting to delete.');
                        return false;
                    }
                }

            });

        });
    </script>
    <div id="export-options" class="wpdm-vertical-tabs-content">
        <form action="options.php" method="POST" id='settings_form_id'>
            <input type="hidden" name="wpsolr_action" value=""/>
			<?php settings_fields( $settings_fields_name ); ?>

            <div class='wpsolr-indexing-option wrapper'>
                <h4 class='wpsolr-head-div'>Export configuration</h4>

                <div class="wdm_note">

                    Choose the WPSOLR settings that you want to export to a file.
                </div>

				<?php foreach ( $option_views_to_export as $view_name => $view_option ) { ?>
                    <div class="wdm_row">
                        <div class='col_left'>
                            Select the settings to export for <?php WPSOLR_Escape::echo_esc_html( $view_name ); ?><br/>
							<?php
							if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
								require $file_to_include;
							}
							?>
                        </div>
                        <div class='col_right'>

                            <div class="clear"></div>

                            <div class="wpsolr-export">
								<?php foreach ( $view_option as $option_name => $option ) { ?>
                                    <div class="wpsolr_export_col">
                                        <input type='checkbox' class="wpsolr_checked"
                                               name='<?php WPSOLR_Escape::echo_esc_attr( $extension_options_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>]'
                                               value='1' <?php checked( '1', isset( $options[ $option_name ] ) ? $options[ $option_name ] : '' ); ?>>
										<?php WPSOLR_Escape::echo_esc_html( $option['description'] ); ?>
                                    </div>
								<?php } ?>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
				<?php } ?>


                <div class="wdm_row">
                    <div class='col_left'>
                        Settings exported<br/>
                        Copy that settings to your target WPSOLR import text area
                    </div>
                    <div class='col_right'>
					<textarea name="wpsolr_data_exported" rows="10"
                              style="width: 100%"><?php WPSOLR_Escape::echo_escaped( ! empty( $exports ) ? json_encode( $exports, JSON_PRETTY_PRINT ) : '' ); ?></textarea>
                    </div>
                    <div class="clear"></div>
                </div>
                <div class='wdm_row'>

                    <div class="submit">
                        <input name="save_selected_importexport_options_form"
                               type="submit"
                               class="button-primary wpsolr-save" value="Generate data to export"/>
                    </div>

                    <script>
                        jQuery(document).ready(function ($) {
                            $(document).on('click', '#wpsolr_delete_button', function (e) {
                                $(this).closest('form').attr('action', location.href);
                                $(this).closest('form').find('input[name="wpsolr_action"]').val('wpsolr_action_delete_settings');
                                $(this).closest('form').submit();
                            });
                        });
                    </script>

                    <div class="submit">
                        <input id="wpsolr_delete_button"
                               name="import"
                               type="delete"
                               class="button-primary wpsolr-save"
                               disabled
                               style="background-color:red;"
                               value="Delete selected data"
                        />

                        <input type="checkbox"
                               id="wpsolr_confirm_delete"
                               onclick="jQuery('#wpsolr_delete_button').prop('disabled', !jQuery('#wpsolr_delete_button').prop('disabled') );"
                        />
                        Click to confirm delete
                    </div>
                    <span id="wpsolr_delete_error" class="wpsolr_err"></span>

                </div>
        </form>
    </div>
<?php } else { ?>
    <div class='wpsolr-indexing-option wrapper'>
        <h4 class='wpsolr-head-div'>Export configuration</h4>
        <div class="wdm_note">
            There are no settings saved in database.
        </div>
    </div>
<?php } ?>

<form method="POST" id='import_form'>
    <input type="hidden" name="wpsolr_action" value="wpsolr_action_import_settings"/>
	<?php
	settings_fields( $extension_options_name );
	?>

    <div class='wpsolr-indexing-option wrapper'>
        <h4 class='wpsolr-head-div'>Import configuration</h4>

        <div class="wdm_note">

            Paste here, from the source WPSOLR, the data to import.
        </div>

        <div class="wdm_row">
            <div class='col_left'>
                Data to import
            </div>
            <div class='col_right'>
					<textarea name="wpsolr_data_to_import" rows="20"
                              style="width: 100%"><?php WPSOLR_Escape::echo_escaped( ! empty( $wpsolr_data_to_import_string ) ? $wpsolr_data_to_import_string : '' ); ?></textarea>
            </div>
            <div class="clear"></div>
        </div>
        <div class='wdm_row'>
            <div class="submit">
                <input id="wpsolr_import_button"
                       name="import"
                       type="submit"
                       class="button-primary wpsolr-save" value="Import generated data"/>
            </div>
        </div>

    </div>
</form>


</div>

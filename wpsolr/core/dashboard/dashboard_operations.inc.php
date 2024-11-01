<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\models\post\WPSOLR_Model_Meta_Type_Post;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
$option_indexes_object = new WPSOLR_Option_Indexes();

// Create the tabs from the Solr indexes already configured
$subtabs = [];
foreach ( $option_indexes_object->get_indexes() as $index_indice => $index ) {
	$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
}

if ( empty( $subtabs ) ) {
	WPSOLR_Escape::echo_escaped( "Please create a search engine index configuration first." );

	return;
}

// Create subtabs on the left side
$current_index_indice = wpsolr_admin_sub_tabs( $subtabs );
if ( ! $option_indexes_object->has_index( $current_index_indice ) ) {
	$current_index_indice = key( $subtabs );
}
$current_index_name = $subtabs[ $current_index_indice ];


try {

	$search_engine = WPSOLR_AbstractIndexClient::create( $current_index_indice );

} catch ( Exception $e ) {

	WPSOLR_Escape::echo_escaped( sprintf( '<b>An error occured while trying to connect to the Solr server:</b> <br>%s', WPSOLR_Escape::esc_html( htmlentities( $e->getMessage() ) ) ) );

	return;
}

$delete_confirmation_message = '';


?>

<div id="solr-operations-tab"
     class="wdm-vertical-tabs-content">
    <form action="options.php" method='post' id='solr_actions'>
        <input type='hidden' id='solr_index_indice' name='wdm_solr_operations_data[solr_index_indice]'
               value="<?php WPSOLR_Escape::echo_esc_attr( $current_index_indice ); ?>">
		<?php

		settings_fields( 'solr_operations_options' );

		$search_engine_operations_options = WPSOLR_Service_Container::getOption()->get_option_operations();

		$operation_index_post_types = WPSOLR_Service_Container::getOption()->get_option_operations_index_post_types( $current_index_indice );

		$batch_size = empty( $search_engine_operations_options['batch_size'][ $current_index_indice ] ) ? '100' : $search_engine_operations_options['batch_size'][ $current_index_indice ];

		$locked_post_types = WPSOLR_Service_Container::getOption()->get_option_locking_index_models( $current_index_indice );

		$model_types = $search_engine->get_models();
		?>
        <div class='wrapper'>
            <h4 class='head_div'>Content of the index "<?php WPSOLR_Escape::echo_esc_html( $current_index_name ); ?>
                "</h4>

            <div class="wdm_note">
                <div>
					<?php
					try {
						$nb_documents_in_index = $search_engine->get_count_documents();
						WPSOLR_Escape::echo_escaped( sprintf( "<b>A total of %s documents are currently in your index \"%s\"</b>", WPSOLR_Escape::esc_html( $nb_documents_in_index ), WPSOLR_Escape::esc_html( $current_index_name ) ) );
					} catch ( Exception $e ) {
						if ( false === strpos( $e->getMessage(), 'Not implemented' ) ) {
							WPSOLR_Escape::echo_escaped( sprintf( '<span class="solr_error"><br>Please check your hosting, an exception occured while calling your search server: <br><br>%s</span>', WPSOLR_Escape::esc_html( htmlentities( $e->getMessage() ) ) ) );
						} else {
							WPSOLR_Escape::echo_escaped( 'This engine does not provide a way to count documents in your index.' );
						}
					}
					?>
                </div>

                <ul class="wdm_row">
                    <div class="clear"></div>
                    <div>
                        <span class='solr_error wpsolr_post_types_err'></span>
                        <div style="float: right">
                            <a href="javascript:void();" class="wpsolr_checker">All</a> |
                            <a href="javascript:void();" class="wpsolr_unchecker">None</a>
                        </div>
                        <div class="clear"></div>
                    </div>

					<?php if ( empty( $model_types ) ) { ?>
                        <span>
                            Please select some post types to index in screen
                            <a href="/wp-admin/admin.php?page=solr_settings&tab=solr_option&subtab=index_opt">2.2 Data</a>
                        </span>
					<?php } ?>

					<?php foreach ( $model_types as $model_type ) {
						$count_nb_documents_to_be_indexed = $search_engine->get_count_nb_documents_to_be_indexed( $model_type );
						$operation_post_type              = $model_type->get_type();
						?>
                        <li>
                            <input type='checkbox'
                                   name='wdm_solr_operations_data[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_OPERATIONS_POST_TYPES ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $current_index_indice ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $operation_post_type ); ?>]'
                                   class="wpsolr_index_post_types wpsolr_checked"
                                   data-wpsolr-index-post-type="<?php WPSOLR_Escape::echo_esc_attr( $operation_post_type ); ?>"
                                   value="y"
								<?php checked( empty( $operation_index_post_types ) || isset( $operation_index_post_types[ $operation_post_type ] ) ); ?>
                            />

							<?php if ( empty( $count_nb_documents_to_be_indexed ) ) { ?>
                                <img src="<?php WPSOLR_Escape::echo_esc_url( plugins_url( 'images/success.png', WPSOLR_PLUGIN_FILE ) ); ?>"
                                     class="wpsolr_premium_text_class" style="display:inline">
								<?php WPSOLR_Escape::echo_escaped( sprintf( 'All %s are indexed', WPSOLR_Escape::esc_html( $model_type->get_label() ) ) ); ?>.
							<?php }
							if ( $count_nb_documents_to_be_indexed > 0 ) { ?>
                                <img src="<?php WPSOLR_Escape::echo_esc_url( plugins_url( 'images/warning.png', WPSOLR_PLUGIN_FILE ) ); ?>"
                                     class="wpsolr_premium_text_class" style="display:inline">
								<?php WPSOLR_Escape::echo_escaped( sprintf( '%s %s', $count_nb_documents_to_be_indexed, WPSOLR_Escape::esc_html( $model_type->get_label() ) ) ); ?>
                                not indexed yet. Click on the
                                button "synchronize" to
                                index
                                them.
							<?php }
							if ( ( $model_type instanceof WPSOLR_Model_Meta_Type_Post ) && ( ( $count_blacklisted_ids = $search_engine->get_count_blacklisted_ids( $model_type ) ) > 0 ) ) {
								?>
								<?php WPSOLR_Escape::echo_escaped( sprintf( 'Except <b>%s</b>', WPSOLR_Escape::esc_html( $count_blacklisted_ids ) ) ); ?>
                                from the 2.2 exclusion list or from the
                                wpsolr metabox "do not search".
								<?php
							}
							?>

							<?php
							if ( ! empty( $locked_post_types[ $model_type->get_type() ] )
							     && ( WPSOLR_AbstractIndexClient::STOP_INDEXING_ID !== $locked_post_types[ $model_type->get_type() ] )
							) {
								$locking_process_id = $locked_post_types[ $model_type->get_type() ];
								$process_label      = $locking_process_id;

								$current_user = wp_get_current_user();
								if ( ( $current_user instanceof WP_User ) && ( $process_label !== $current_user->user_email ) ) {
									// Show the lock only if the current user is not the locker
									?>

                                    <div style="display: inline-block">
                                        <input type="button"
                                               data-wpsolr-post_type="<?php WPSOLR_Escape::echo_esc_attr( $model_type->get_type() ); ?>"
                                               data-wpsolr-process-id="<?php WPSOLR_Escape::echo_esc_attr( $locking_process_id ); ?>"
                                               data-wpsolr-process-label="<?php WPSOLR_Escape::echo_esc_attr( $process_label ); ?>"
                                               class="wpsolr_unlock_process button-primary"
                                               value="Stop the indexing started by <?php WPSOLR_Escape::echo_esc_html( $process_label ); ?>"/>
                                    </div>

									<?php
								}
							}
							?>
                        </li>
						<?php
					}
					?>
                </ul>
            </div>
            <div class="wdm_row">
                <p>The indexing is <b>incremental</b>: only documents updated after the last operation
                    are sent to the index.</p>

                <p>So, the first operation will index all documents, by batches of
                    <b><?php WPSOLR_Escape::echo_esc_html( $batch_size ); ?></b> documents.</p>

                <p>If a <b>timeout</b> occurs, you just have to click on the button again: the process
                    will restart from where it stopped.</p>

                <p>If you need to reindex all again, delete the index first.</p>
            </div>
            <div class="wdm_row">
                <div class='col_left'>Number of documents sent to the index as a single commit.<br>
                    You can change this number to control indexing's performance.
                </div>
                <div class='col_right'>
                    <input type='text' id='batch_size'
                           name='wdm_solr_operations_data[batch_size][<?php WPSOLR_Escape::echo_esc_attr( $current_index_indice ); ?>]'
                           placeholder="Enter a Number"
                           value="<?php WPSOLR_Escape::echo_esc_attr( $batch_size ); ?>">
                    <span class='res_err'></span><br>
                </div>
                <div class="clear"></div>

				<?php
				if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_BATCH_DEBUG ) ) ) {
					require $file_to_include;
				}
				?>

				<?php
				if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_BATCH_MODE_REPLACE ) ) ) {
					require $file_to_include;
				}
				?>

            </div>
            <div class="wdm_row">
                <div class="submit">
                    <input name="solr_start_index_data" type="submit" class="button-primary wdm-save"
                           id='solr_start_index_data'
                           value="Index selected post types in index '<?php WPSOLR_Escape::echo_esc_attr( $current_index_name ); ?>' "/>
                    <input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
                           id='solr_stop_index_data' value="Click to stop indexing"
                           style="visibility: hidden;"/>
                    <span class='img-load'></span>

                    <input name="solr_delete_index" type="submit" class="button-primary wdm-save"
                           id="solr_delete_index"
                           value="Delete selected post types from index '<?php WPSOLR_Escape::echo_esc_attr( $current_index_name ); ?>'"
						<?php WPSOLR_Escape::echo_escaped( empty( $delete_confirmation_message ) ? '' : sprintf( 'data-wpsolr-confirmation="%s"', WPSOLR_Escape::esc_attr( $delete_confirmation_message ) ) ); ?>
                    />

                    <input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
                           id='solr_stop_delete_data' value="Click to stop deleting"
                           style="visibility: hidden;"/>
                    <span class='img-load'></span>

                    <span class='status_index_message'></span>
                    <span class='status_debug_message'></span>
                    <span class='status_del_message'></span>
                </div>
            </div>
        </div>
    </form>
</div>

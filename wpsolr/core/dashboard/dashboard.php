<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\extensions\WPSOLR_Extension;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;
use wpsolr\core\classes\utilities\WPSOLR_Zip_Generator;
use wpsolr\core\classes\WPSOLR_Events;

define( 'WPSOLR_DASHBOARD_NONCE_SELECTOR', 'WPSOLR_DASHBOARD_NONCE_SELECTOR' );
define( 'WPSOLR_NONCE_FOR_DASHBOARD', 'wpsolr_nonce_for_dashboard' );
define( 'WPSOLR_DASHBOARD_WPSOLR_LICENSE_DATA', 'WPSOLR_DASHBOARD_WPSOLR_LICENSE_DATA' );

$url_view_uuid = WPSOLR_Option_View::get_url_view_uuid();
WPSOLR_Option_View::set_current_view_uuid( $url_view_uuid );

$url_index_uuid = WPSOLR_Option_View::get_url_index_uuid();
if ( empty( $url_index_uuid ) && ( isset( $_REQUEST['subtab'] ) && ( 'index_opt' != $_REQUEST['subtab'] ) ) ) {
	$url_index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid();
}
WPSOLR_Option_View::set_current_index_uuid( $url_index_uuid );


/**
 * Build the admin header
 * @return string
 */
function wpsolr_admin_header() {
	global $license_manager;

	$admin_header = '';


	// Add nonce in all admin screens, for all wpsolr admin ajax calls.
	$admin_header .= sprintf(
		'<input type="hidden" id="%s" value="%s" >',
		esc_attr( WPSOLR_DASHBOARD_NONCE_SELECTOR ),
		esc_attr( wp_create_nonce( WPSOLR_NONCE_FOR_DASHBOARD ) )
	);

	return $admin_header;
}

/**
 * Build the admin version
 * @return string
 */
function wpsolr_admin_version() {

	$footer_version = sprintf( '%s %s', WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_VERSION );

	return $footer_version;
}

/**
 * GEt the class of an extension license tab.
 *
 * @param $license_code
 * @param $entension
 *
 * @return string
 */
function wpsolr_get_extension_tab_class( $license_code, $extension ) {
	$activated_licenses_titles = OptionLicenses::get_activated_licenses_links( $license_code );

	$result = empty( $activated_licenses_titles ) ? 'wpsolr_tab_inactive' : 'wpsolr_tab_active';

	$result .= ! defined( 'WPSOLR_PLUGIN_DIR' ) && WPSOLR_Extension::get_option_is_pro( $extension ) ? ' wpsolr_is_not_available' : ' wpsolr_is_available';

	return $result;
}

const WPSOLR_ADMIN_MENU_FACETS = 'tab=solr_option&subtab=facet_opt';

/**
 * Return menus link html
 *
 * @param string $menu
 * @param string $text
 *
 * @return string
 */
function wpsolr_get_menu_html( $menu, $text, $is_new_target = false ) {
	$wpsolr_menu = 'page=solr_settings';

	return sprintf( '<a href="?%s&%s" target="%s">%s</a>', $wpsolr_menu, $menu, $is_new_target ? '_blank' : '_self', $text );
}


add_filter( 'init', function () {
	global $google_recaptcha_site_key, $google_recaptcha_token, $response_object1;

	/**
	 * Download zip examples manager
	 */
	new WPSOLR_Zip_Generator();

	/*
	 *  Route to controllers
	 */
	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_MANAGED_SOLR_SERVERS, true );
	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );

	switch ( isset( $_POST['wpsolr_action'] ) ? $_POST['wpsolr_action'] : '' ) {
		case 'wpsolr_admin_action_form_temporary_index':
			unset( $response_object );

			if ( isset( $_POST['submit_button_form_temporary_index'] ) ) {
				wpsolr_admin_action_form_temporary_index( $response_object1 );
			}

			if ( isset( $_POST['submit_button_form_temporary_index_select_managed_solr_service_id'] ) ) {

				$form_data = WPSOLR_Extension::extract_form_data( true, [
						'managed_solr_service_id' => [ 'default_value' => '', 'can_be_empty' => false ]
					]
				);

				$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
				$response_object1    = $managed_solr_server->call_rest_create_google_recaptcha_token();

				if ( isset( $response_object1 ) && OptionManagedSolrServer::is_response_ok( $response_object1 ) ) {
					$google_recaptcha_site_key = OptionManagedSolrServer::get_response_result( $response_object1, 'siteKey' );
					$google_recaptcha_token    = OptionManagedSolrServer::get_response_result( $response_object1, 'token' );
				}

			}

			break;

	}
} );

/**
 * @param $response_object
 */
function wpsolr_admin_action_form_temporary_index( &$response_object ) {


	// recaptcha response
	$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_POST['g-recaptcha-response'] ) : '';

	// A recaptcha response must be set
	if ( empty( $g_recaptcha_response ) ) {

		return;
	}

	$form_data = WPSOLR_Extension::extract_form_data( true, array(
			'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
		)
	);

	$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
	$response_object     = $managed_solr_server->call_rest_create_solr_index( $g_recaptcha_response );

	if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		$option_indexes_object = new WPSOLR_Option_Indexes();

		$index_uuid = $option_indexes_object->create_managed_index(
			$managed_solr_server->get_search_engine(),
			$managed_solr_server->get_id(),
			WPSOLR_Option_Indexes::STORED_INDEX_TYPE_MANAGED_TEMPORARY,
			OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			'Test index from ' . $managed_solr_server->get_label(),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlScheme' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlDomain' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPort' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPath' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'key' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'secret' )
		);

		if ( count( $option_indexes_object->get_indexes() ) === 1 ) {
			// Redirect automatically to Solr options if it is the first solr index created

			$redirect_location = '?page=solr_settings&tab=solr_option';
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
			exit;
		} else {
			// Redirect to the index defineition tab
			$redirect_location = sprintf( '?page=solr_settings&tab=solr_indexes&subtab=%s', $index_uuid );
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
		}
	}

}

function wpsolr_admin_init() {

	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
	WPSOLR_Option_View::register_setting_view( WPSOLR_Option_Indexes::get_option_name( WPSOLR_Extension::OPTION_INDEXES ), WPSOLR_Option_Indexes::get_option_name( WPSOLR_Extension::OPTION_INDEXES ) );

	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_LICENSES, true );
	WPSOLR_Option_View::register_setting_view( WPSOLR_Option_Indexes::get_option_name( WPSOLR_Extension::OPTION_LICENSES ), OptionLicenses::get_option_name( WPSOLR_Extension::OPTION_LICENSES ) );

	WPSOLR_Option_View::register_setting_index( 'solr_form_options', WPSOLR_Option::OPTION_INDEX );
	WPSOLR_Option_View::register_setting_view( 'solr_res_options', WPSOLR_Option::OPTION_SEARCH );
	WPSOLR_Option_View::register_setting_view( 'solr_facet_options', WPSOLR_Option::OPTION_FACET );
	WPSOLR_Option_View::register_setting_view( 'solr_search_field_options', WPSOLR_Option::OPTION_SEARCH_FIELDS );
	WPSOLR_Option_View::register_setting_view( 'solr_sort_options', WPSOLR_Option::OPTION_SORTBY );
	WPSOLR_Option_View::register_setting_view( 'solr_localization_options', 'wdm_solr_localization_data' );
	WPSOLR_Option_View::register_setting_view( 'solr_operations_options', WPSOLR_Option::OPTION_OPERATIONS );
	WPSOLR_Option_View::register_setting_view( 'extension_premium_opt', WPSOLR_Option::OPTION_PREMIUM );
	WPSOLR_Option_View::register_setting_view( 'extension_import_export_opt', WPSOLR_Option::OPTION_IMPORT_EXPORT );
	WPSOLR_Option_View::register_setting_view( 'extension_suggestions_opt', WPSOLR_Option::OPTION_SUGGESTIONS );
	WPSOLR_Option_View::register_setting_view( 'extension_views_opt', WPSOLR_Option::OPTION_VIEW );


}

function fun_add_solr_settings() {


	$img_url = plugins_url( '../images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_SHORT_NAME, 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );

	// Load scripts and css only in WPSOLR admin pages
	if ( false !== strpos( $_SERVER['REQUEST_URI'], 'solr_settings' ) ) {

		$depends_on_js = [ 'jquery' ];


		wp_enqueue_style( 'dashboard_style', plugins_url( '../css/dashboard_css.css', __FILE__ ), [], WPSOLR_PLUGIN_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		$depends_on_js[] = 'jquery-ui-sortable';
		wp_enqueue_script( 'dashboard_js1', plugins_url( '../js/dashboard.js', __FILE__ ),
			$depends_on_js,
			WPSOLR_PLUGIN_VERSION
		);

		wp_localize_script( 'dashboard_js1', 'wpsolr_localize_script_dashboard',
			[
				'ajax_url'                        => admin_url( 'admin-ajax.php' ),
				'wpsolr_dashboard_nonce_selector' => ( '#' . WPSOLR_DASHBOARD_NONCE_SELECTOR ),
			]
		);

		$plugin_vals = [ 'plugin_url' => plugins_url( '../images/', __FILE__ ) ];
		wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );

		// Google api recaptcha - Used for temporary indexes creation
		wp_enqueue_script( 'google-api-recaptcha', '//www.google.com/recaptcha/api.js', [], WPSOLR_PLUGIN_VERSION );
	}

}

function fun_set_solr_options() {
	global $license_manager;


	// Button Index
	if ( isset( $_POST['solr_index_data'] ) ) {

		$solr = WPSOLR_IndexSolariumClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->index_data( false, 'default', null );

			if ( count( $val ) == 1 || $val == 1 ) {
				WPSOLR_Escape::echo_escaped( "<script type='text/javascript'>
                jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('wpsolr_success');
                });
            </script>" );
			} else {
				WPSOLR_Escape::echo_escaped(
					"<script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('wpsolr_warning');
                });
            </script>"
				);
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			WPSOLR_Escape::echo_escaped( "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_index_message').removeClass('loading');
               jQuery('.status_index_message').addClass('wpsolr_warning');
               jQuery('.wdm_note').html(sprintf('<b>Error: <p>%s</p></b>', WPSOLR_Escape::esc_html($errorMessage)));
            });
            </script>" );

		}

	}

	// Button delete
	if ( isset( $_POST['solr_delete_index'] ) ) {
		$solr = WPSOLR_IndexSolariumClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->delete_documents();

			if ( $val == 0 ) {
				WPSOLR_Escape::echo_escaped( "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
               jQuery('.status_del_message').addClass('wpsolr_success');
            });
            </script>" );
			} else {
				WPSOLR_Escape::echo_escaped( "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
                              jQuery('.status_del_message').addClass('wpsolr_warning');
            });
            </script>" );
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			WPSOLR_Escape::echo_escaped( "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
               jQuery('.status_del_message').addClass('wpsolr_warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            })
            </script>" );
		}
	}


	?>
    <div class="wdm-wrap">
        <div class="wpsolr-page-header">
            <div class="wpsolr-page-title">
				<?php WPSOLR_Escape::echo_escaped( wpsolr_admin_header() ); ?>
            </div>
            <div class="wpsolr-page-version">
				<?php WPSOLR_Escape::echo_escaped( wpsolr_admin_version() ); ?>
            </div>
        </div>

        <input type='hidden' id='adm_path'
               value='<?php WPSOLR_Escape::echo_escaped( apply_filters( WPSOLR_Events::WPSOLR_FILTER_UAT_TEST_ADMIN_URL, admin_url() ) ); ?>'>
        <!-- for ajax -->

		<?php
		if ( isset ( $_GET['tab'] ) ) {
			wpsolr_admin_tabs( WPSOLR_Sanitize::sanitize_text_field( $_GET['tab'] ) );
		} else {
			wpsolr_admin_tabs( 'solr_presentation' );
		}

		if ( isset ( $_GET['tab'] ) ) {
			$tab = WPSOLR_Sanitize::sanitize_text_field( $_GET['tab'] );
		} else {
			$tab = 'solr_presentation';
		}

		$wpsolr_view_uuid  = WPSOLR_Option_View::get_current_view_uuid();
		$wpsolr_view_label = sprintf( 'for %s', WPSOLR_Option_View::get_view_label() );
		$wpsolr_index_uuid = WPSOLR_Service_Container::getOption()->get_view_index_uuid( $wpsolr_view_uuid );

		switch ( $tab ) {
			case 'solr_presentation' :
				include( 'dashboard_presentation.inc.php' );
				break;

			case 'solr_indexes' :
				WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_INDEXES );
				break;

			case 'solr_option':
				include( 'dashboard_settings.inc.php' );
				break;

			case 'solr_themes':
				include( 'dashboard_themes.inc.php' );
				break;

			case 'solr_plugins':
				include( 'dashboard_plugins.inc.php' );
				break;

			case 'solr_operations':
				include( 'dashboard_operations.inc.php' );
				break;

			case 'wpsolr_licenses' :
				WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_LICENSES );
				break;

			case 'solr_import_export':
				WPSOLR_Extension::require_once_wpsolr_extension_admin_options( WPSOLR_Extension::OPTION_IMPORT_EXPORT );
				break;

			case 'solr_feedback':
				include( 'dashboard_feedbacks.inc.php' );
				break;
		}

		?>

    </div>
	<?php


}

function wpsolr_admin_tabs( $current = 'solr_indexes' ) {

	// Get default search solr index indice
	WPSOLR_Extension::require_once_wpsolr_extension( WPSOLR_Extension::OPTION_INDEXES, true );
	$option_indexes            = new WPSOLR_Option_Indexes();
	$default_search_solr_index = $option_indexes->get_default_search_solr_index();

	$nb_indexes        = count( $option_indexes->get_indexes() );
	$are_there_indexes = ( $nb_indexes >= 0 );

	$tabs                      = [];
	$tabs['solr_presentation'] = 'What is WPSOLR ?';
	$tabs['solr_indexes']      = $are_there_indexes ? '0. Connect your indexes' : '0. Connect your index';

	if ( defined( 'WPSOLR_PLUGIN_DIR' ) ) {
		$tabs['solr_plugins'] = '1. Activate extensions';
		$tabs['solr_option']        = sprintf( "2. Define your search with '%s'",
			! isset( $default_search_solr_index )
				? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
				: $option_indexes->get_index_name( $default_search_solr_index ) );
		$tabs['solr_operations']    = '3. Send your data';
		$tabs['solr_import_export'] = '4. Import / Export settings';
	} else {
		$tabs['solr_option']     = sprintf( "1. Define your search with '%s'",
			! isset( $default_search_solr_index )
				? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
				: $option_indexes->get_index_name( $default_search_solr_index ) );
		$tabs['solr_operations'] = '2. Send your data';
		$tabs['solr_plugins']    = '3. Activate extensions';
		$tabs['solr_themes']     = '3a. Activate themes';
	}

	//$tabs['solr_feedback'] = 'Feedback';

	WPSOLR_Escape::echo_escaped( '<div id="icon-themes" class="icon32"><br></div>' );
	WPSOLR_Escape::echo_escaped( '<h2 class="nav-tab-wrapper wpsolr-tour-navigation-tabs">' );
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		$url   = WPSOLR_Option_View::add_menus_url_query_view( "admin.php?page=solr_settings&tab=$tab" );
		WPSOLR_Escape::echo_escaped( sprintf( "<a class='nav-tab%s' href='%s'>%s</a>",
			WPSOLR_Escape::esc_attr( $class ), WPSOLR_Escape::esc_url( $url ), WPSOLR_Escape::esc_escaped( $name ) ) );

	}
	WPSOLR_Escape::echo_escaped( '</h2>' );
}


function wpsolr_admin_sub_tabs( $subtabs ) {

	// Tab selected by the user
	$tab = isset( $_GET['tab'] ) ? WPSOLR_Sanitize::sanitize_text_field( $_GET['tab'] ) : 'solr_presentation';

	if ( isset ( $_GET['subtab'] ) ) {

		$current_subtab = WPSOLR_Sanitize::sanitize_text_field( $_GET['subtab'] );

	} else {
		// No user selection: use the first subtab in the list
		$current_subtab = key( $subtabs );
	}

	WPSOLR_Escape::echo_escaped( '<div id="icon-themes" class="icon32"><br></div>' );
	WPSOLR_Escape::echo_escaped( '<div class="nav-tab-wrapper wdm-vertical-tabs wpsolr-col-3">' );
	WPSOLR_Escape::echo_escaped( '<ul>' );
	foreach ( $subtabs as $subtab_indice => $subtab ) {
		$extra_class = '';
		$title       = '';
		$subtitle    = '';
		if ( is_array( $subtab ) ) {
			$name        = $subtab['name'];
			$extra_class = $subtab['class'] ?? '';
			$title       = $subtab['title'] ?? '';
			$subtitle    = $subtab['subtitle'] ?? '';
		} else {
			$name = $subtab;
		}
		$class = ( $subtab_indice == $current_subtab ) ? ' nav-tab-active' : '';

		if ( false === strpos( $name, 'wpsolr_premium_class' ) ) {
			$url = WPSOLR_Option_View::add_menus_url_query_view( "admin.php?page=solr_settings&tab=$tab&subtab=$subtab_indice" );
			WPSOLR_Escape::echo_escaped( sprintf( "<li>%s<a class='nav-tab%s %s' href='%s'>%s</a>%s</li>",
				WPSOLR_Escape::esc_html( $title ),
				WPSOLR_Escape::esc_attr( $class ), WPSOLR_Escape::esc_attr( $extra_class ),
				WPSOLR_Escape::esc_url( $url ),
				WPSOLR_Escape::esc_html( $name ), WPSOLR_Escape::esc_html( $subtitle ) ) );
		} else {
			WPSOLR_Escape::echo_esc_html( $name );
		}

	}

	WPSOLR_Escape::echo_escaped( '</ul></div>' );

	return $current_subtab;
}

<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\extensions\view\WPSOLR_Option_View;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Sanitize;

function wpsolr_event_tracking_template_redirect() {
	global $post;

	if ( is_singular() &&
	     ! empty( $_REQUEST[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_EVENT_TRACKING_NAME ] ?? '' ) &&
	     ! empty( $view_uuid = $_REQUEST[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_VIEW_UUID ] ?? '' )
	) {
		// Tracking parameters are present in the url: send it to the API

		try {

			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			$event = [];
			foreach (
				[
					WPSOLR_Query_Parameters::SEARCH_PARAMETER_EVENT_TRACKING_NAME,
					WPSOLR_Query_Parameters::SEARCH_PARAMETER_RESULTS_QUERY_ID,
					WPSOLR_Query_Parameters::SEARCH_PARAMETER_RESULTS_POSITION,
				] as $event_property_name
			) {
				$event[ $event_property_name ] = WPSOLR_Sanitize::sanitize_text_field( $_REQUEST[ $event_property_name ] ?? '' );
			}

			$event[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_USER_TOKEN ] = 'user-id';

			WPSOLR_AbstractIndexClient::create()->transform_event_tracking( $post, $event, true );

		} catch ( Exception $e ) {
			// Do nothing. Just prevent errors from appearing.
		}

	}
}

add_action( 'template_redirect', 'wpsolr_event_tracking_template_redirect' );

/*
function wpsolr_event_tracking() {

	if (
		( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], 'nonce_for_autocomplete' ) )
	) {

		$event = $_POST['event'] ?? [];

		$view_uuid = WPSOLR_Sanitize::sanitize_text_field( $event[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_VIEW_UUID ] ?? '' );

		if ( ! empty( $view_uuid ) && ! empty( $event ) ) {

			WPSOLR_Option_View::set_current_view_uuid( $view_uuid );

			try {

				WPSOLR_AbstractIndexClient::create()->send_event_tracking( $event );

			} catch ( Exception $e ) {
				WPSOLR_Escape::echo_esc_escaped(  wpsolr_json_encode_suggestions( sprintf( '<li>Error while searching for "%s": %s</li>', $input, $e->getMessage()) ) );
			}
		}
	}

	die();
}

add_action( 'wp_ajax_' . WPSOLR_AJAX_EVENT_TRACKING_ACTION, WPSOLR_AJAX_EVENT_TRACKING_ACTION );
add_action( 'wp_ajax_nopriv_' . WPSOLR_AJAX_EVENT_TRACKING_ACTION, WPSOLR_AJAX_EVENT_TRACKING_ACTION );
*/
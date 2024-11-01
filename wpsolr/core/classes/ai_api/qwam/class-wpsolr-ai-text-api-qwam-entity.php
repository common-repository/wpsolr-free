<?php

namespace wpsolr\core\classes\ai_api\qwam;

use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Please email your request to info@qwamci.com. A sales representative will contact you to evaluate your needs and provide you a pricing.
 * You will be given an IP controlled endpoint to add to your configuration.
 * A comprehensive study can be provided to consider special requests needing customization.
 */
class WPSOLR_AI_Text_Api_Qwam_Entity extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_qwam_entity';

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return static::TEXT_SERVICE_EXTRACTION_ENTITY['label'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'http://en.qwamci.com/qwam-text-analytics/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/qwam-text-analytics/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_text() {
		return <<<'TAG'
Please email your request to <a href="mailto:info@qwamci.com">info@qwamci.com</a>.
A sales representative will contact you to evaluate your needs and provide you a pricing.
You will be given an IP controlled endpoint to add to your configuration.
A comprehensive study can be provided to consider special requests needing customization.
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_QWAM;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			static::FIELD_NAME_FIELDS_URL_DEFAULT,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			'Person',
			'Address',
			'Email',
			'Phone',
			'Date',
			'PersonTopic',
			'PersonUncategorized',
			'Location',
			'LocationTopic',
			'Company',
			'Media',
			'Organization',
			'Organonoff',
			'Event',
			'Product',
			'Object',
			'Concept',
			'Sentiment',
			'Suggestion',
			'Intention',
			//'Relation',
			'Version',
			'City',
			'Region',
			'Country',
			'CountryTopicCode',
			'Company',
			'CompanyTopic',
			'CompanyUncategorized',
			'Spatial',
		];
	}

	/**
	 * @inheritDoc
	 *
	 * @return array|\WP_Error
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		$default_args = array(
			'method'  => 'POST',
			'body'    => $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ],
			'timeout' => 60,
			//'verify'  => true,
			'headers' => [
				'Content-Type' => 'text/plain',
				'Expect'       => '',
				// https://wordpress.stackexchange.com/questions/301451/wp-remote-post-doesnt-work-with-more-than-1024-bytes-in-the-body
			],
		);

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		return wp_remote_post(
			$this->_replace_url_parameters( $this->ai_api[ static::FIELD_NAME_FIELDS_URL ] ),
			//'https://httpbin.org/post', //(to debug the curl content)
			array_merge( $default_args, $args )
		);

	}

	/**
	 * @inheritDoc
	 *
	 * @return mixed
	 */
	protected function _create_api_client() {
		// No need here. Plain http call.
		return null;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array|\WP_Error $raw_service_response
	 *
	 * @return array
	 */
	protected function _decode_api_results( $raw_service_response ) {

		if ( is_wp_error( $raw_service_response ) ) {

			throw new \Exception( $raw_service_response->get_error_message() );
		}

		if ( ( 200 !== $raw_service_response['response']['code'] ) ||
		     ( 'OK' !== $raw_service_response['response']['message'] )
		) {
			throw new \Exception( $raw_service_response['body'] );
		}

		return json_decode( $raw_service_response['body'], true );
	}

	/**
	 * @param array|\WP_Error $raw_service_response
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _convert_api_results( $raw_service_response ) {

		// Convert indexed to associative array
		$results = [];
		foreach ( $raw_service_response['annotations'] as $annotation_type => $annotations ) {
			foreach ( $annotations as $annotation ) {
				if ( $this->_is_above_treshold_score( $annotation ) ) {
					$results[] = [
						'type'  => $annotation_type,
						'value' => $annotation['replacewith'] ?? $annotation['string'],
					];
				}
			}
		}

		return $this->_group_api_entities_by_type( $results, 'type', 'value' );;
	}

	/**
	 * Replace url parameters
	 *
	 * @param $url
	 *
	 * @return string|string[]
	 */
	protected function _replace_url_parameters( $url ) {

		$url_params     = parse_url( $url, PHP_URL_QUERY );
		$new_url_params = http_build_query( [ 'in' => 'text', 'out' => 'jsonfull' ] );
		$url            = empty( $url_params ) ? sprintf( '%s?%s', $url, $new_url_params ) : str_replace( $url_params, $new_url_params, $url );

		return $url;
	}

	/**
	 * @param array $annotation
	 *
	 * @inheridoc
	 */
	protected function _get_api_results_score( $annotation ) {
		// confidence is in [0-1]. Sometimes missing.
		return isset( $annotation['confidence'] ) ? $annotation['confidence'] : static::CONST_DEFAULT_SCORE_IF_NOT_FOUND;
	}

}

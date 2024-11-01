<?php

namespace wpsolr\core\classes\ai_api\google;

use Google\Cloud\Language\Annotation;
use Google\Cloud\Language\LanguageClient;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_AI_Text_Api_Google_Entity extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_google_entity';

	/**
	 * @inheritDoc
	 */
	public function get_is_disabled() {
		return false;
	}

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
		return 'https://cloud.google.com/natural-language';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/google-natural-language/';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return <<<'TAG'
Natural Language uses machine learning to reveal the structure and meaning of text. You can extract information about people, places, and events, and better understand social media sentiment and customer conversations. Natural Language enables you to analyze text and also integrate it with your document storage on Cloud Storage.
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_GOOGLE;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY_JSON => [
					self::FIELD_NAME_LABEL                 => 'Service account JSON key of the Google Project you authorized the Natural Language API',
					self::FIELD_NAME_PLACEHOLDER           => 'Service account JSON key',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your service account JSON key',
					],
				],
			],
		];

	}

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			'PERSON',
			'LOCATION',
			'ORGANIZATION',
			'EVENT',
			'WORK_OF_ART',
			'CONSUMER_GOOD',
			'OTHER',
			'PHONE_NUMBER',
			'ADDRESS',
			'DATE',
			'NUMBER',
			'PRICE',
			'UNKNOWN',
		];
	}

	/**
	 * @param LanguageClient $api_client
	 *
	 * @inheritDoc
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		// Generate a Key: https://googleapis.github.io/google-cloud-php/#/docs/google-cloud/v0.131.0/guides/authentication
		return $api_client->analyzeEntities( $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] );
	}

	/**
	 * @inheritDoc
	 *
	 * @return LanguageClient
	 */
	protected function _create_api_client() {
		return new LanguageClient( [
			'keyFile'               => json_decode( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY_JSON ], true ),
			'suppressKeyFileNotice' => true,
		] );
	}

	/**
	 * @param Annotation $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {
		return $this->_group_api_entities_by_type( $raw_service_response->entities(), 'type', 'name' );
	}

}

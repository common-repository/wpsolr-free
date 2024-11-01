<?php

namespace wpsolr\core\classes\ai_api\rosette;

use rosette\api\Api;
use rosette\api\DocumentParameters;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_AI_Text_Api_Rosette_Entity extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_rosette_entity';

	/**
	 * @inheritDoc
	 */
	public function get_is_disabled() {
		return true;
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
		return 'https://developer.rosette.com/features-and-functions#entity-extraction-and-linking-introduction';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/rosette-text-analytics/';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return <<<'TAG'
The Rosette entity extraction endpoint uses statistical or deep neural network based models, patterns, and exact matching to identify entities in documents. An entity refers to an object of interest such as a person, organization, location, date, or email address. Identifying entities can help you classify documents and the kinds of data they contain.
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_ROSETTE;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY => [
					self::FIELD_NAME_LABEL                 => 'User Key',
					self::FIELD_NAME_PLACEHOLDER           => 'User Key found in your Rosette developer account',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your User Key',
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
			'PRODUCT',
			'TITLE',
			'NATIONALITY',
			'RELIGION',
			'IDENTIFIER:CREDIT_CARD_NUM',
			'IDENTIFIER:EMAIL',
			'IDENTIFIER:MONEY',
			'IDENTIFIER:PERSONAL_ID_NUM',
			'IDENTIFIER:PHONE_NUMBER',
			'IDENTIFIER:URL',
			'TEMPORAL:DATE',
			'TEMPORAL:TIME',
			'IDENTIFIER:DISTANCE',
			'IDENTIFIER:LATITUDE_LONGITUDE',
		];
	}

	/**
	 * @param Api $api_client
	 *
	 * @inheritDoc
	 * @throws \rosette\api\RosetteException
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		$params = new DocumentParameters();
		$params->set( 'content', $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] );

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		return $api_client->entities( $params );
	}

	/**
	 * @inheritDoc
	 *
	 * @return Api
	 */
	protected function _create_api_client() {
		return new Api( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY ] );
	}

	/**
	 * @param array $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {
		return $this->_group_api_entities_by_type( $raw_service_response['entities'], 'type', 'mention' );
	}

}

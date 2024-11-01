<?php

namespace wpsolr\core\classes\ai_api\aws;

use Aws\Comprehend\ComprehendClient;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_AI_Text_Api_Amazon_Entity extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_aws_entity';

	// Limit of the api
	protected const MAX_TEXT_UTF8_SIZE_IN_BYTES = 5000;

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
		return 'https://docs.aws.amazon.com/comprehend/index.html';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/amazon-comprehend/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_text() {
		return <<<'TAG'
Amazon Comprehend uses natural language processing (NLP) to extract insights about the content of documents without the need of any special preprocessing. Amazon Comprehend processes any text files in UTF-8 format. It develops insights by recognizing the entities, key phrases, language, sentiments, and other common elements in a document. 
TAG
		       . sprintf( '<br><br>Due to <a href="%s" target="_new">API limits</a>, each document text is truncated to %s bytes in UTF-8.',
				'https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-comprehend-2017-11-27.html#detectentities',
				static::MAX_TEXT_UTF8_SIZE_IN_BYTES );
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_AMAZON_COMPREHEND;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY => [
					self::FIELD_NAME_LABEL                 => 'AWS access key ID',
					self::FIELD_NAME_PLACEHOLDER           => 'AWS access key ID',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS access key ID',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_SERVICE_SECRET => [
					self::FIELD_NAME_LABEL                 => 'AWS secret access key',
					self::FIELD_NAME_PLACEHOLDER           => 'AWS secret access key',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS secret access key',
					],
				],
			],
			self::FIELD_NAME_FIELDS_SERVICE_AWS_REGION_DEFAULT,

			[
				self::FIELD_NAME_FIELDS_SERVICE_LANGUAGE => [
					self::FIELD_NAME_LABEL                 => 'Language',
					self::FIELD_NAME_INSTRUCTION           => sprintf( 'Possible  <a href="%s" target="_new">values</a>: %s',
						'https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-comprehend-2017-11-27.html#detectentities',
						'en es fr de it pt ar hi ja ko zh zh-TW' ),
					self::FIELD_NAME_DEFAULT_VALUE         => 'en',
					self::FIELD_NAME_PLACEHOLDER           => 'The language of your documents',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your language',
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
			'COMMERCIAL_ITEM',
			'EVENT',
			'DATE',
			'QUANTITY',
			'TITLE',
			'OTHER',
		];
	}

	/**
	 * @param ComprehendClient $api_client
	 *
	 * @inheritDoc
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		if ( empty( $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] ) ) {
			// Nothing to do: quit
			return;
		}

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		return $api_client->detectEntities( [
			'Text'         => mb_strcut( $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ], 0, self::MAX_TEXT_UTF8_SIZE_IN_BYTES, "UTF-8" ),
			// 5000 UTF-8 bytes maximum
			'LanguageCode' => trim( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_LANGUAGE ] ),
		] );

	}

	/**
	 * @inheritDoc
	 *
	 * @return ComprehendClient
	 */
	protected function _create_api_client() {
		return new ComprehendClient( [
			'credentials' => [
				'key'    => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY ],
				'secret' => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_SECRET ],
			],
			'region'      => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_AWS_REGION ],
			'version'     => 'latest',
		] );
	}

	/**
	 * @param array $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {
		return $this->_group_api_entities_by_type( $raw_service_response['Entities'], 'Type', 'Text' );
	}

	/**
	 * @param array $annotation
	 *
	 * @inheridoc
	 */
	protected function _get_api_results_score( $annotation ) {
		// https://docs.aws.amazon.com/comprehend/latest/dg/get-started-api-entities.html#get-started-api-entities-cli
		// Score is in [0-1].
		return $annotation['Score'];
	}

}

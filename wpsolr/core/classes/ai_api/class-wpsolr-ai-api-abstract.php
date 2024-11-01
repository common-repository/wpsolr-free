<?php

namespace wpsolr\core\classes\ai_api;

use wpsolr\core\classes\ai_api\aws\WPSOLR_AI_Image_Api_Amazon_Detect_Celebrities;
use wpsolr\core\classes\ai_api\aws\WPSOLR_AI_Image_Api_Amazon_Detect_Faces;
use wpsolr\core\classes\ai_api\aws\WPSOLR_AI_Image_Api_Amazon_Detect_Labels;
use wpsolr\core\classes\ai_api\aws\WPSOLR_AI_Image_Api_Amazon_Detect_Texts;
use wpsolr\core\classes\ai_api\aws\WPSOLR_AI_Text_Api_Amazon_Entity;
use wpsolr\core\classes\ai_api\google\WPSOLR_AI_Image_Api_Google_Annotate;
use wpsolr\core\classes\ai_api\google\WPSOLR_AI_Text_Api_Google_Entity;
use wpsolr\core\classes\ai_api\meaningcloud\WPSOLR_AI_Text_Api_Meaningcloud_Entity;
use wpsolr\core\classes\ai_api\qwam\WPSOLR_AI_Text_Api_Qwam_Entity;
use wpsolr\core\classes\ai_api\rosette\WPSOLR_AI_Text_Api_Rosette_Entity;
use wpsolr\core\classes\ai_api\rosette\WPSOLR_AI_Text_Api_Rosette_KeyPhrase;
use wpsolr\core\classes\ai_api\rosette\WPSOLR_AI_Text_Api_Rosette_Sentiment;
use wpsolr\core\classes\ai_api\rosette\WPSOLR_AI_Text_Api_Rosette_Topic;
use wpsolr\core\classes\ai_api\systran\WPSOLR_AI_Text_Api_Systran;
use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Core;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Escape;
use wpsolr\core\classes\utilities\WPSOLR_Option;

abstract class WPSOLR_AI_Api_Abstract {

	protected const CONST_DEFAULT_SCORE_IF_NOT_FOUND = 0;

	/**
	 * AI Providers
	 */
	const TEXT_PROVIDER_AMAZON_COMPREHEND = [
		'id'                             => 'amazon_comprehend',
		'label'                          => 'Amazon Comprehend',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Amazon_Entity::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const IMAGE_PROVIDER_AMAZON_REKOGNITION = [
		'id'                             => 'amazon_rekognition',
		'label'                          => 'Amazon Rekognition',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Image_Api_Amazon_Detect_Labels::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_IMAGE,
	];
	const TEXT_PROVIDER_GOOGLE = [
		'id'                             => 'google',
		'label'                          => 'Google Natural Language',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Google_Entity::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const IMAGE_PROVIDER_GOOGLE = [
		'id'                             => 'google_vision',
		'label'                          => 'Google Vision',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Image_Api_Google_Annotate::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_IMAGE,
	];
	const VIDEO_PROVIDER_GOOGLE = [
		'id'                             => 'google_video',
		'label'                          => 'Google Video Intelligence',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Image_Api_Google_Annotate::API_ID,
		'is_disabled'                    => true,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_VIDEO,
	];
	const TEXT_PROVIDER_MEANINGCLOUD = [
		'id'                             => 'meaningcloud',
		'label'                          => 'MeaningCloud',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Meaningcloud_Entity::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const TEXT_PROVIDER_QWAM = [
		'id'                             => 'qwam',
		'label'                          => 'Qwam Text Analytics',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Qwam_Entity::API_ID,
		'is_disabled'                    => false,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const TEXT_PROVIDER_ROSETTE = [
		'id'                             => 'rosette',
		'label'                          => 'Rosette Text Analytics',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Rosette_Entity::API_ID,
		'is_disabled'                    => true,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const TEXT_PROVIDER_SYSTRAN = [
		'id'                             => 'systran',
		'label'                          => 'Systran Natural Language',
		self::FIELD_NAME_DEFAULT_SERVICE => WPSOLR_AI_Text_Api_Systran::API_ID,
		'is_disabled'                    => true,
		self::FIELD_NAME_SERVICE_TYPE    => self::SERVICE_TYPE_TEXT,
	];
	const AI_PROVIDERS = [
		self::TEXT_PROVIDER_AMAZON_COMPREHEND['id']   => self::TEXT_PROVIDER_AMAZON_COMPREHEND,
		self::IMAGE_PROVIDER_AMAZON_REKOGNITION['id'] => self::IMAGE_PROVIDER_AMAZON_REKOGNITION,
		self::TEXT_PROVIDER_GOOGLE['id']              => self::TEXT_PROVIDER_GOOGLE,
		self::IMAGE_PROVIDER_GOOGLE['id']             => self::IMAGE_PROVIDER_GOOGLE,
		self::VIDEO_PROVIDER_GOOGLE['id']             => self::VIDEO_PROVIDER_GOOGLE,
		self::TEXT_PROVIDER_MEANINGCLOUD['id']        => self::TEXT_PROVIDER_MEANINGCLOUD,
		self::TEXT_PROVIDER_QWAM['id']                => self::TEXT_PROVIDER_QWAM,
		self::TEXT_PROVIDER_ROSETTE['id']             => self::TEXT_PROVIDER_ROSETTE,
		self::TEXT_PROVIDER_SYSTRAN['id']             => self::TEXT_PROVIDER_SYSTRAN,
	];

	/**
	 * AI services
	 */
	const IMAGE_SERVICE_ANNOTATION = [
		'id'          => 'image_annotation',
		'label'       => 'Image Annotation',
		'is_disabled' => false,
	];
	const TEXT_SERVICE_EXTRACTION_ENTITY = [
		'id'          => 'entities',
		'label'       => 'Entities extraction',
		'is_disabled' => false,
	];
	const TEXT_SERVICE_EXTRACTION_SENTIMENT = [
		'id'          => 'sentiments',
		'label'       => 'Sentiments',
		'is_disabled' => true,
	];
	const TEXT_SERVICE_EXTRACTION_KEY_PHRASES = [
		'id'          => 'key_phrases',
		'label'       => 'Key phrases',
		'is_disabled' => true,
	];
	const TEXT_SERVICE_EXTRACTION_TOPIC = [
		'id'          => 'topics',
		'label'       => 'Topics',
		'is_disabled' => true,
	];
	const TEXT_SERVICES = [
		self::TEXT_SERVICE_EXTRACTION_ENTITY,
		self::TEXT_SERVICE_EXTRACTION_SENTIMENT,
		self::TEXT_SERVICE_EXTRACTION_KEY_PHRASES,
		self::TEXT_SERVICE_EXTRACTION_TOPIC,
	];

	/**
	 * Services types
	 */
	protected const SERVICE_TYPE_TEXT = 'text';
	protected const SERVICE_TYPE_IMAGE = 'image';
	protected const SERVICE_TYPE_VIDEO = 'video';

	/**
	 * AI apis which are active and shown in select box
	 */
	/** @var WPSOLR_AI_Api_Abstract[] */
	static protected $SERVICE_APIS = [
		/** Amazon */
		WPSOLR_AI_Text_Api_Amazon_Entity::class,
		WPSOLR_AI_Image_Api_Amazon_Detect_Celebrities::class,
		WPSOLR_AI_Image_Api_Amazon_Detect_Faces::class,
		WPSOLR_AI_Image_Api_Amazon_Detect_Labels::class,
		WPSOLR_AI_Image_Api_Amazon_Detect_Texts::class,
		/** Google */
		WPSOLR_AI_Text_Api_Google_Entity::class,
		WPSOLR_AI_Image_Api_Google_Annotate::class,
		/** MeaningCloud */
		WPSOLR_AI_Text_Api_Meaningcloud_Entity::class,
		/** Qwam */
		WPSOLR_AI_Text_Api_Qwam_Entity::class,
		/** Rosette */
		WPSOLR_AI_Text_Api_Rosette_Entity::class,
		WPSOLR_AI_Text_Api_Rosette_KeyPhrase::class,
		WPSOLR_AI_Text_Api_Rosette_Sentiment::class,
		WPSOLR_AI_Text_Api_Rosette_Topic::class,
		/** Systran.io */
		WPSOLR_AI_Text_Api_Systran::class,
	];

	const API_ID = 'AI API to be defined';

	/**
	 * Field names used in javascript
	 */
	const FIELD_NAME_PROVIDERS = 'engines';
	const FIELD_NAME_SERVICES = 'providers';
	const FIELD_NAME_DEFAULT_SERVICE = 'default_service';
	const FIELD_NAME_PROVIDER = 'provider';
	const FIELD_NAME_SERVICE_TYPE = 'service_type';
	const FIELD_NAME_URL = 'url';
	const FIELD_NAME_DOCUMENTATION_URL = 'documentation_url';
	const FIELD_NAME_DOCUMENTATION_TEXT = 'documentation_text';
	const FIELD_NAME_LABEL = 'label';
	const FIELD_NAME_INSTRUCTION = 'instruction';
	const FIELD_NAME_DEFAULT_VALUE = 'default';
	const FIELD_NAME_FIELDS = 'fields';
	const FIELD_NAME_PLACEHOLDER = 'placeholder';
	const FIELD_NAME_FORMAT_ERROR_LABEL = 'wpsolr_err';
	const FIELD_NAME_FORMAT = 'format';
	const FIELD_NAME_FORMAT_DISPLAY = 'format_display';
	const FIELD_NAME_FORMAT_DISPLAY_INPUT_TEXT = 'input_text';
	const FIELD_NAME_FORMAT_DISPLAY_SELECT = 'select';
	const FIELD_NAME_FORMAT_DISPLAY_CHECKBOX = 'checkbox';
	const FIELD_NAME_FORMAT_DISPLAY_TEXTAREA = 'textarea';
	const FIELD_NAME_FORMAT_TYPE = 'format_type';
	const FIELD_NAME_FORMAT_TYPE_OPTIONAL = 'format_type_optional';
	const FIELD_NAME_FORMAT_TYPE_MANDATORY = 'format_type_mandatory';
	const FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY = 'format_type_not_mandatory';
	const FIELD_NAME_FORMAT_TYPE_FLOAT_BETWEEN_0_1 = 'format_type_float_between_0_1';
	const FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_2_DIGITS = 'format_type_integer_2_digits';
	const FIELD_NAME_FORMAT_TYPE_INTEGER_MINIMUM_POSITIVE = 'format_type_integer_positive';
	const FIELD_NAME_FORMAT_ERROR_LABEL_MANDATORY = '';
	const FIELD_NAME_FORMAT_IS_CREATE_ONLY = 'format_is_create_only';
	const FIELD_NAME_FORMAT_IS_UPDATE_ONLY = 'format_is_update_only';

	const FIELD_NAME_FIELDS_INTERNAL_IMAGE_SEND_URL = 'service_internal_image_send_url';
	const FIELD_NAME_FIELDS_INTERNAL_IMAGE_SEND_URL_DEFAULT = [
		self::FIELD_NAME_FIELDS_INTERNAL_IMAGE_SEND_URL => [
			self::FIELD_NAME_LABEL                 => 'Send internal images url instead of content',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_EXTERNAL_IMAGE_SEND_CONTENT = 'service_external_image_send_content';
	const FIELD_NAME_FIELDS_EXTERNAL_IMAGE_SEND_CONTENT_DEFAULT = [
		self::FIELD_NAME_FIELDS_EXTERNAL_IMAGE_SEND_CONTENT => [
			self::FIELD_NAME_LABEL                 => 'Send external images content instead of url',
			self::FIELD_NAME_PLACEHOLDER           => 'By default, external images url are sent to the AI Api. You can decide here to send the images content instead.',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_TEXT = 'service_image_type_text';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_TEXT_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_TEXT => [
			self::FIELD_NAME_LABEL                 => 'Extract texts from images (OCR)',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL = 'service_image_type_label';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL => [
			self::FIELD_NAME_LABEL                 => 'Extract labels from images',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE = 'service_image_type_label_translate';

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LANDMARK = 'service_image_type_landmark';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LANDMARK_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LANDMARK => [
			self::FIELD_NAME_LABEL                 => 'Extract landmarks from images',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LOGO = 'service_image_type_logo';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LOGO_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LOGO => [
			self::FIELD_NAME_LABEL                 => 'Extract logos from images',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_FACE = 'service_image_type_face';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_FACE_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_FACE => [
			self::FIELD_NAME_LABEL                 => 'Extract faces from images',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_PROPERTY = 'service_image_type_property';
	const FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_PROPERTY_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_PROPERTY => [
			self::FIELD_NAME_LABEL                 => 'Extract colors from images (RGB)',
			self::FIELD_NAME_PLACEHOLDER           => '',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_URL = 'service_url';
	const FIELD_NAME_FIELDS_URL_DEFAULT = [
		self::FIELD_NAME_FIELDS_URL => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your service url',
			],
			self::FIELD_NAME_LABEL                 => 'Url',
			self::FIELD_NAME_PLACEHOLDER           => 'The url for this service',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_KEY = 'service_key';
	const FIELD_NAME_FIELDS_SERVICE_KEY_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_KEY => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Key',
			self::FIELD_NAME_PLACEHOLDER           => 'Key',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_SECRET = 'service_secret';
	const FIELD_NAME_FIELDS_SERVICE_SECRET_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_SECRET => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_NOT_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::ERROR_LABEL_EMPTY,
			],
			self::FIELD_NAME_LABEL                 => 'Secret/Password',
			self::FIELD_NAME_PLACEHOLDER           => 'Secret/Password',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_AWS_REGION = 'service_aws_region';
	const FIELD_NAME_FIELDS_SERVICE_AWS_REGION_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_AWS_REGION => [
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS Region here',
			],
			self::FIELD_NAME_LABEL                 => 'AWS Region',
			self::FIELD_NAME_PLACEHOLDER           => 'AWS Region',
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		],
	];

	const FIELD_NAME_FIELDS_SERVICE_MIN_CONFIDENCE = 'service_min_confidence';
	const FIELD_NAME_FIELDS_INDEX_SERVICE_MIN_CONFIDENCE_DEFAULT = [
		self::FIELD_NAME_FIELDS_SERVICE_MIN_CONFIDENCE => [
			self::FIELD_NAME_LABEL                 => 'Minimum confidence treshold',
			self::FIELD_NAME_PLACEHOLDER           => 'Numeric between 0 an 1. For instance 0, 0.5, 0.75, 0.95, 1',
			self::FIELD_NAME_DEFAULT_VALUE         => '0.7',
			self::FIELD_NAME_INSTRUCTION           => <<<'TAG'
All information come with a confidence level between 0 and 1. The lower the confidence, the less reliable is the information.
Enter a minimum confidence to filter out information with a confidence below the treshold.
For instance, 0.7 to filter out information with a confidence level below 70%.
TAG
			,
			self::FIELD_NAME_FORMAT                => [
				self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_FLOAT_BETWEEN_0_1,
				self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'a minimum confidence between 0 an 1. For instance 0, 0.5, 0.75, 0.95, 1',
			],
			self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
		]
	];

	const FIELD_NAME_FIELDS_SERVICE_LANGUAGE = 'service_language';
	const FIELD_NAME_FIELDS_SERVICE_KEY_JSON = 'service_key_json';

	const PLEASE_ENTER = 'Please enter ';
	const PLEASE_COPY = 'Please copy ';
	const ERROR_LABEL_EMPTY = '';

	/*
	const FIELD_NAME_FIELDS_ = '';
	const _DEFAULT = [ self:: => [] ];
	*/

	protected const UNIQUE_SERVICE_FIELD_NAME_TEMPLATE = 'wpsolr_%s_%s';


	/** @var array */
	protected static $ai_apis, $ai_apis_by_engine;
	/** @var array */
	protected static $all_ui_fields, $all_ui_fields_flatten;

	/** @var mixed */
	protected $_api_client;

	/**
	 * @var array
	 */
	protected $ai_api;

	/**
	 * Return which UI fields are displayed for each hosting api ID
	 *
	 * @return array
	 */
	static function get_all_ui_fields() {

		if ( ! isset( self::$all_ui_fields ) ) {

			self::$all_ui_fields[ self::FIELD_NAME_PROVIDERS ] = static::AI_PROVIDERS;

			foreach ( self::get_ai_api_services() as $ai_api ) {

				self::$all_ui_fields[ self::FIELD_NAME_SERVICES ][ $ai_api->get_id() ] = $ai_api->_get_ui_fields();
			}

		}

		return self::$all_ui_fields;
	}

	/**
	 * @return array
	 */
	static function get_all_ui_fields_flatten() {

		if ( ! isset( self::$all_ui_fields_flatten ) ) {
			$all_fields = self::get_all_ui_fields();

			foreach ( $all_fields[ self::FIELD_NAME_SERVICES ] as $ai_api_id => $ai_api_def ) {
				foreach ( $ai_api_def[ self::FIELD_NAME_FIELDS ] as $field_label => $field_def ) {

					self::$all_ui_fields_flatten[] = [ $field_label => $field_def ];
				}
			}
		}

		return self::$all_ui_fields_flatten;
	}

	protected function _get_ui_fields() {
		$result = [];

		// Add the engine
		$result [ self::FIELD_NAME_PROVIDER ] = $this->get_provider_id();

		// Add the name
		$result [ self::FIELD_NAME_LABEL ] = $this->get_label();

		// Add the url
		$result [ self::FIELD_NAME_URL ] = $this->get_url();

		// Add the documentation url
		$result [ self::FIELD_NAME_DOCUMENTATION_URL ] = $this->get_documentation_url();

		// Add the documentation description
		$result [ self::FIELD_NAME_DOCUMENTATION_TEXT ] = $this->get_documentation_text();

		// Add the child fields
		$fields_child   = $this->get_ui_fields_child();
		$fields_child[] = static::FIELD_NAME_FIELDS_INDEX_SERVICE_MIN_CONFIDENCE_DEFAULT;

		$fields_child_flatten = [];
		foreach ( $fields_child as $fields ) {
			$fields_child_flatten[ key( $fields ) ] = $fields[ key( $fields ) ];
		}
		$result[ self::FIELD_NAME_FIELDS ] = $fields_child_flatten;

		return $result;
	}

	/**
	 * @return array
	 */
	public function get_ui_fields_child() {
		return [];
	}

	/**
	 * @return string[]
	 */
	public function get_extracted_fields() {
		$results = [];

		foreach ( $this->_get_extracted_fields_child() as $field_name ) {
			// Aff service id as prefix for all fields, to prevent duplicate field names.
			$results[] = $this->_build_unique_service_field_name( $field_name );
		}

		return $results;
	}

	/**
	 * @return string[]
	 */
	protected function _get_extracted_fields_child() {
		return [];
	}

	/**
	 * @return WPSOLR_AI_Api_Abstract[]
	 */
	static function get_ai_api_services() {

		if ( ! isset( self::$ai_apis ) ) {
			$ai_apis_by_name_asc = [];
			foreach ( self::$SERVICE_APIS as $ai_api_class ) {
				/** @var WPSOLR_AI_Api_Abstract $object */
				$object                                                          = new $ai_api_class();
				$ai_apis_by_name_asc[ $object->get_label() . $object->get_id() ] = $object;
			}


			foreach ( $ai_apis_by_name_asc as $label => $object ) {
				self::$ai_apis[] = $object;
			}
		}

		return self::$ai_apis;
	}

	/**
	 * @param string $provider_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	static function get_ai_api_provider_by_id( $provider_id ) {

		$providers = self::get_ai_api_providers();

		foreach ( $providers as $provider ) {
			if ( $provider_id === $provider['id'] ) {
				return $provider;
			}
		}

		// Not found
		throw new \Exception( sprintf( 'Provider %s is undefined.', $provider_id ) );
	}

	/**
	 * @param string $service_id
	 *
	 * @return WPSOLR_AI_Api_Abstract
	 * @throws \Exception
	 */
	static function get_ai_api_service_by_id( $service_id ) {

		$services = self::get_ai_api_services();

		foreach ( $services as $service ) {
			if ( $service_id === $service->get_id() ) {
				return $service;
			}
		}

		// Not found
		throw new \Exception( sprintf( 'Service %s is undefined.', $service_id ) );
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return static::API_ID;
	}

	/**
	 *
	 * @param string $ai_api_id
	 * @param array $config
	 * @param \Solarium\Client $search_engine_client
	 *
	 * @return WPSOLR_Solr_Admin_Api_Core
	 * @throws \Exception
	 */
	public static function new_solr_admin_api_by_id( $ai_api_id, $config, $search_engine_client ) {

		$ai_api = self::get_ai_api_service_by_id( $ai_api_id );

		return $ai_api->new_solr_admin_api( $config, $search_engine_client );
	}

	/**
	 * All providers
	 * @return array
	 */
	public static function get_ai_api_providers() {
		return self::AI_PROVIDERS;
	}


	/**
	 * Call a service on a document
	 *
	 * @param array $ai_api
	 * @param array $document_for_update
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function call_service( array $ai_api, array $document_for_update ) {
		$results = [];

		$this->ai_api = $ai_api;

		$call_data = [];
		if ( ! $this->_prepare_call( $document_for_update, $call_data ) ) {
			// No call necessary
			return [];
		}

		// Call the service on the document text
		try {

			// Initiate the AI API client once
			if ( ! $this->_api_client ) {
				$this->_api_client = $this->_create_api_client();
			}

			// Get the stats
			$option_ai_apis_nb_calls = WPSOLR_Service_Container::getOption()->get_option_ai_api_nb_calls();

			// Call the API
			$raw_service_results = $this->_call_api(
				$option_ai_apis_nb_calls, $this->_api_client, $document_for_update, $call_data
			);

			// Throw errors from results
			$decoded_service_results = $this->_decode_api_results( $raw_service_results );

			// Convert the API raw results
			$converted_results = $this->_convert_api_results( $decoded_service_results );

			// Encapsulate results
			$service_results = (object) [
				'results' => $converted_results,
				'status'  => (object) [
					'state'   => 'OK',
					'message' => '',
				],
			];

		} catch ( \Exception $e ) {
			$service_results = $this->_get_default_service_api_error( $e->getMessage() );
		}

		// Manage service errors
		$this->throw_error_message( $this->ai_api[ WPSOLR_Option::OPTION_AI_API_LABEL ], $service_results );

		// Save the extracted value as an indexed field
		foreach ( $service_results->results as $field_name => $field_value ) {
			$indexed_field_name = $this->_build_unique_service_field_name( $field_name );

			$results[ $indexed_field_name ] = is_array( $field_value ) ? $field_value : explode( ';', $field_value );
		}

		return $results;
	}

	/**
	 * @param string $ai_api_label
	 * @param object $response_object
	 *
	 * @throws \Exception
	 */
	public function throw_error_message( string $ai_api_label, $response_object ) {

		if ( 'OK' !== $response_object->status->state ) {
			throw new \Exception( sprintf( '(AI API "%s") %s',
				$ai_api_label, $response_object->status->message ) );
		}
	}

	/**
	 * Call the api. To be defined in children.
	 *
	 * @param $option_ai_apis_nb_calls
	 * @param mixed $api_client
	 * @param array $document_for_update
	 * @param array $args
	 *
	 * @return object
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		return $this->_get_default_service_api_error( 'This API is not implemented yet.' );
	}

	/**
	 * Prepare the call.
	 *
	 * @param array $document_for_update
	 * @param mixed $call_data // Data prepared for the call if necessary
	 *
	 * @return bool
	 */
	protected function _prepare_call( $document_for_update, &$call_data ) {
		// Define in children
		return true;
	}

	/**
	 * @param string $text
	 *
	 * @return object
	 */
	protected function _get_default_service_api_error( $text ) {

		return (object) array(
			'status' => (object) array(
				'state'   => 'ERROR',
				'message' => $text,
			),
		);
	}

	/**
	 * @param string $host
	 *
	 * @return string
	 */
	public function get_host( $host ) {
		return $host;
	}


	/**
	 * @param string $label
	 * @param string|array $id
	 * @param string $default
	 *
	 * @return string
	 */
	public function get_data_by_id( $label, $id, $default ) {
		return $default;
	}

	/**
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * @return string
	 */
	public function get_url() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return 'No documentation yet.';
	}

	/**
	 * @return string
	 */
	public function get_latest_version() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_incompatibility_reason() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_documentation_url() {
		return '';
	}

	/**
	 * @return array
	 */
	public function get_credentials() {
		return [];
	}

	/**
	 * @return array
	 */
	abstract public function get_provider();

	/**
	 * @param array $provider
	 *
	 * @return string
	 */
	public function get_provider_id() {
		return $this->get_provider()['id'];
	}

	/**
	 * @return bool
	 */

	/**
	 * @return bool
	 */
	public function get_is_disabled() {
		return false;
	}

	/**
	 *
	 * @param array $config
	 * @param \Solarium\Client $search_engine_client
	 *
	 * @return WPSOLR_Solr_Admin_Api_Core
	 */
	protected function new_solr_admin_api( $config, $search_engine_client ) {

		return new WPSOLR_Solr_Admin_Api_Core( $config, $search_engine_client );
	}

	/**
	 * Key/password is extracted from endpoint only during creation phase.
	 *
	 * @return bool
	 */
	public function get_is_endpoint_only() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function get_is_no_hosting() {
		return false;
	}


	/**
	 * Escape index label characters
	 *
	 * @param $index_label
	 *
	 * @return string|string[]|null
	 */
	protected function escape_index_label( $index_label ) {
		return trim( strtolower( preg_replace( '#[^\w]#', '', $index_label ) ) );
	}

	/**
	 * @param string $field_name
	 * @param string $field_display
	 * @param bool $is_index_readonly
	 * @param bool $is_new_index
	 * @param string $option_name
	 * @param array $option_data
	 * @param string $ai_api_uuid
	 * @param string $subtab
	 * @param bool $is_password
	 * @param bool $is_blurr
	 */
	static public function include_edit_field( $field_name, $field_display, $is_index_readonly, $is_new_index, $option_name, $option_data, $ai_api_uuid, $subtab, $is_password, $is_blurr ) {
		?>
        <div class="wdm_row wpsolr_hide <?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>">
            <div class='col_left'>
                <span class="<?php WPSOLR_Escape::echo_esc_html( WPSOLR_AI_Api_Abstract::FIELD_NAME_LABEL ); ?>"></span>
            </div>
            <div class='col_right'>

				<?php switch ( $field_display ) {
					case WPSOLR_AI_Api_Abstract::FIELD_NAME_FORMAT_DISPLAY_CHECKBOX:
						?>

                        <input class="wpsolr-remove-if-empty wpsolr-ui-field"
                               type="checkbox"
                               name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_AI_API_APIS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $ai_api_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>]"
							<?php checked( ! empty( $option_data[ WPSOLR_Option::OPTION_AI_API_APIS ][ $ai_api_uuid ][ $field_name ] ) ); ?>
                        >

						<?php break;
					case WPSOLR_AI_Api_Abstract::FIELD_NAME_FORMAT_DISPLAY_INPUT_TEXT:
						$value = $option_data[ WPSOLR_Option::OPTION_AI_API_APIS ][ $ai_api_uuid ][ $field_name ] ?? '';
						?>

                        <input class="wpsolr-remove-if-empty wpsolr-ui-field <?php WPSOLR_Escape::echo_escaped( $is_blurr ? 'wpsolr_blur' : '' ); ?> <?php WPSOLR_Escape::echo_escaped( $is_password ? 'wpsolr_password' : '' ); ?>"
                               type="<?php WPSOLR_Escape::echo_escaped( $is_password ? 'password' : 'text' ); ?>" <?php WPSOLR_Escape::echo_escaped( ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : '' ); ?>
                               name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_AI_API_APIS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $ai_api_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>]"
                               value="<?php WPSOLR_Escape::echo_esc_attr( $value ); ?>"
                        >

						<?php break;
					case WPSOLR_AI_Api_Abstract::FIELD_NAME_FORMAT_DISPLAY_TEXTAREA:
						?>

                        <textarea
                                class="wpsolr-remove-if-empty wpsolr-ui-field <?php WPSOLR_Escape::echo_escaped( $is_blurr ? 'wpsolr_blur' : '' ); ?> <?php WPSOLR_Escape::echo_escaped( $is_password ? 'wpsolr_password' : '' ); ?>"
                                name="<?php WPSOLR_Escape::echo_esc_attr( $option_name ); ?>[<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Option::OPTION_AI_API_APIS ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $ai_api_uuid ); ?>][<?php WPSOLR_Escape::echo_esc_attr( $field_name ); ?>]"
                                rows="10"
                        ><?php WPSOLR_Escape::echo_esc_textarea( empty( $option_data[ WPSOLR_Option::OPTION_AI_API_APIS ][ $ai_api_uuid ][ $field_name ] ) ? '' : $option_data[ WPSOLR_Option::OPTION_AI_API_APIS ][ $ai_api_uuid ][ $field_name ] ); ?></textarea

						<?php break;
				} ?>


				<?php if ( $is_password ) { ?>
                    <input type="checkbox" class="wpsolr_password_toggle"/>Show
				<?php } ?>
                <span class="<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AI_Api_Abstract::FIELD_NAME_INSTRUCTION ); ?>"></span>

                <div class="clear"></div>
                <span class='<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_AI_Api_Abstract::FIELD_NAME_FORMAT_ERROR_LABEL ); ?>'></span>
            </div>
            <div class="clear"></div>
        </div>
		<?php
	}


	/**
	 * Generate a unique field name across all services
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function _build_unique_service_field_name( string $field_name ): string {
		return strtolower( sprintf( self::UNIQUE_SERVICE_FIELD_NAME_TEMPLATE,
				$this->get_id(),
				$this->_replace_bad_caracters_service_field_name( $field_name ) )
		);
	}

	/**
	 * Remove field name bad caracters, like ':'
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function _replace_bad_caracters_service_field_name( string $field_name ): string {

		$field_name = str_replace( ':', '_', $field_name ); // Rosette
		$field_name = str_replace( '>', '_', $field_name ); // MeaningCloud

		return $field_name;
	}

	/**
	 * Get client from AI API
	 *
	 * @return mixed
	 */
	protected function _create_api_client() {
		// Implement in children
		return null;
	}

	/**
	 * @param mixed $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {
		return [];
	}

	/**
	 * @param mixed $raw_service_response
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function _decode_api_results( $raw_service_results ) {
		// Children
		return $raw_service_results;
	}

	/**
	 * Extract entities and group them by type with a name (deduplicate also)
	 * [['type' => 'type1', 'name' => 'P1'], ['type' => 'type1', 'name' => 'P1'], ['type' => 'type1', 'name' => 'P2']]
	 * ===> ['type1' => ['P1', 'P2']]
	 *
	 * @param array $entities
	 * @param string|string[] $field_type
	 * @param string $field_name
	 *
	 * @return array
	 */
	protected function _group_api_entities_by_type( array $entities, $field_type, string $field_name ) {

		$results = [];
		foreach ( $entities as $entity ) {

			if ( $this->_is_above_treshold_score( $entity ) ) {

				if ( is_array( $field_type ) ) {

					// Sub field
					$entity_type = $entity[ $field_type[0] ][ $field_type[1] ];

				} else {

					$entity_type = $entity[ $field_type ];
				}

				if ( empty( $results[ $entity_type ] ) ) {
					$results[ $entity_type ] = [];
				}

				$field_values = ( is_array( $entity[ $field_name ] ) ? $entity[ $field_name ] : [ $entity[ $field_name ] ] );
				foreach ( $field_values as $field_value ) {
					if ( ! in_array( $field_value, $results[ $entity_type ] ) ) {
						// No duplicate of entity type
						$results[ $entity_type ][] = $field_value;
					}
				}
			}

		}

		return $results;
	}

	/**
	 * Increment the api nb calls
	 *
	 * @param array $option_ai_apis_nb_calls
	 */
	protected function _increment_nb_api_calls( &$option_ai_apis_nb_calls ) {
		$option_ai_apis_nb_calls[ $this->ai_api['ai_api_uuid'] ] = 1 + ( isset( $option_ai_apis_nb_calls[ $this->ai_api['ai_api_uuid'] ] ) ? $option_ai_apis_nb_calls[ $this->ai_api['ai_api_uuid'] ] : 0 );
		update_option( WPSOLR_Option::OPTION_AI_API_NB_CALLS, $option_ai_apis_nb_calls );
	}


	/**
	 * Media formats that can be processed by an API
	 *
	 * @return string
	 */
	protected function _get_accepted_media_formats() {
		return '';
	}

	/**
	 *
	 * @param mixed $annotation
	 *
	 * @return bool
	 */
	protected function _is_above_treshold_score( $annotation ) {

		$score = $this->_get_api_results_score( $annotation );
		if ( ( $score < 0 ) || ( $score > 1 ) ) {
			throw new \Exception( sprintf( 'Confidence level "%s" should be in [0, 1]', $score ) );
		}
		$min_score = $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_MIN_CONFIDENCE ];
		if ( ( '' === $min_score ) || ( $min_score < 0 ) || ( $min_score > 1 ) ) {
			throw new \Exception( sprintf( 'Minimum confidence level "%s" should be in [0, 1]', $min_score ) );
		}

		return ( $score >= $min_score );
	}


	/**
	 * Retrieve the score from an API results
	 *
	 * @param  $annotation
	 *
	 * @return float In [0-1]
	 */
	protected function _get_api_results_score( $annotation ) {
		// Define in children if required
		if ( is_float( $annotation ) ) {
			return $annotation;
		}

		// Score not found, consider there is no filtering
		return static::CONST_DEFAULT_SCORE_IF_NOT_FOUND;
	}

}

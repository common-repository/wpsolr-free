<?php

namespace wpsolr\core\classes\engines\vespa;

use wpsolr\core\classes\engines\vespa\php_client\WPSOLR_Php_Search_Client;
use wpsolr\core\classes\engines\vespa\php_client\WPSOLR_Php_Search_Index;
use wpsolr\core\classes\engines\WPSOLR_Client;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Some common methods of the Vespa client.
 *
 */
trait WPSOLR_Vespa_Client {
	use WPSOLR_Client;
	use WPSOLR_Vespa_GraphQL_Utilities;

	static protected string $alias_get = 'search';
	static protected string $alias_aggregate_search_count = 'search_count';
	static protected string $alias_aggregate_type_field_prefix = 'field_';
	static protected string $alias_aggregate_type_stats_prefix = 'stats_';
	static protected array $converted_field_names = [];
	static protected array $unconverted_field_names = [];

	static protected $convert_field_name_if_date = [];

	protected $INDEX_REPLICAT_SORT_NAME_PATTERN = '_replica_sort_';

	protected $wpsolr_type = 'wpsolr_types';

	// Unique id to store attached decoded files.
	protected $WPSOLR_DOC_ID_ATTACHMENT = 'wpsolr_doc_id_attachment';

	/** @var WPSOLR_Php_Search_Client */
	protected $search_engine_client;

	/** @var string */
	protected $index_label;

	/** @var WPSOLR_Php_Search_Index[] */
	protected $search_indexes;

	// Index conf files
	protected $FILE_CONF_INDEX_5 = 'wpsolr_index_5.json';
	protected $FILE_CONF_INDEX_6 = 'wpsolr_index_6.json';
	protected $FILE_CONF_INDEX_7 = 'wpsolr_index_7.json';

	/**
	 * @inerhitDoc
	 */
	public function get_has_exists_filter(): bool {
		return false;
	}

	/**
	 * Try to fix the current index configuration before retrying
	 *
	 * @param $error_msg
	 *
	 * @return bool
	 */
	protected function _try_to_fix_error_doc_type( $error_msg ) {

		if ( false !== strpos( $error_msg, 'the final mapping would have more than 1 type' ) ) {
			// No type required (ES >= 7.x)
			$this->_fix_error_doc_type( 'index_doc_type', '' );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, 'type is missing' ) ) {
			// Type required (ES < 7.x)
			$this->_fix_error_doc_type( 'index_doc_type', $this->wpsolr_type );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, "suggester [autocomplete] doesn't expect any context" ) ) {
			// Index does not support suggester contexts: deactivate contexts in next request
			$this->_fix_error_doc_type( WPSOLR_Option::OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT, null );

			// Fixed
			return true;

		} else if ( false !== strpos( $error_msg, "Missing mandatory contexts" ) ) {
			// Index does support suggester contexts: activate contexts in next request
			$this->_fix_error_doc_type( WPSOLR_Option::OPTION_INDEXES_VERSION_SUGGESTER_HAS_CONTEXT, '1' );

			// Fixed
			return true;
		}

		// Not fixed
		return false;
	}


	/**
	 * Fix the current index configuration with the guessed doc type
	 *
	 * @param string $index_property
	 * @param string $doc_type
	 *
	 * @return void
	 */
	protected
	function _fix_error_doc_type(
		$index_property, $doc_type
	) {

		// To be able to retry now, save it on current object index
		$this->index[ $index_property ] = $doc_type;

		$option_indexes = WPSOLR_Service_Container::getOption()->get_option_indexes();

		if ( isset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ] ) ) {
			// To prevent retry later, save it in the index options

			if ( is_null( $doc_type ) ) {
				// null value means "unset"

				unset( $option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ][ $index_property ] );

			} else {

				$option_indexes[ WPSOLR_Option::OPTION_INDEXES_INDEXES ][ $this->index_indice ][ $index_property ] = $doc_type;
			}

			// Save it now
			update_option( WPSOLR_Option::OPTION_INDEXES, $option_indexes );
		}

	}

	/**
	 * @param string $index_label
	 *
	 * @return WPSOLR_Php_Search_Index
	 */
	public
	function get_search_index(
		$index_label = ''
	) {

		$index_label = empty( $index_label ) ? $this->index_label : $index_label;

		if ( ! isset( $this->search_indexes[ $index_label ] ) ) {
			$this->search_indexes[ $index_label ] = $this->search_engine_client->init_index( $index_label );
		}

		return $this->search_indexes[ $index_label ];
	}

	/**
	 * This index has the deprecated "type"?
	 *
	 * @return bool
	 */
	protected
	function _get_index_doc_type() {
		return $this->index['index_doc_type'] ?? $this->wpsolr_type;
	}

	/**
	 * @param string $index_label
	 */
	public
	function set_index_label(
		$index_label
	) {
		$this->index_label = empty( $index_label ) ? '' : $this->convert_class_name( $index_label );
	}

	/**
	 * @return string
	 */
	public
	function get_index_label() {
		return $this->index_label;
	}

	/**
	 * @param $config
	 *
	 * @return WPSOLR_Php_Search_Client
	 */
	protected
	function create_search_engine_client(
		$config
	) {

		$client = WPSOLR_Php_Search_Client::create( $config );

		$this->set_index_label( empty( $config ) ? '' : $config['index_label'] );

		return $client;
	}

	/**
	 * Load the content of a conf file.
	 *
	 * @href https://www.semi.technology/developers/vespa/current/restful-api-references/schema.html#create-a-class
	 *
	 * @return array
	 */
	protected
	function get_index_settings() {

		$properties         = [];
		$base64_field_names = [];
		$text_field_names   = [];
		foreach ( WpSolrSchema::DEFAULT_FIELD_TYPES as $field_name => $field_type ) {
			$field_data_type      = $this->_get_field_definition( $field_name );
			$converted_field_name = $this->convert_field_name( $field_name );
			$property             = [
				'dataType' => [
					$field_data_type
				],
				'name'     => $converted_field_name,
			];
			$property             = $this->_add_extra_properties_to_field_definition( $property );
			$properties[]         = $property;

			switch ( $field_data_type ) {
				case 'blob':
					$base64_field_names[] = $converted_field_name;
					break;

				default:
					$text_field_names[] = $converted_field_name;
					break;
			}
		}

		$class_schema = [
			'class'       => $this->index_label,
			'description' => 'Class created by WPSOLR from the index label',
			'properties'  => $properties,
		];

		$module = $this->config['extra_parameters'][ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ];
		if ( ! empty( $module ) ) {
			$class_schema['vectorizer'] = $module;
		}

		switch ( $module ) {
			case WPSOLR_Vespa_Constants::MODULE_TEXT_2_VEC_CONTEXTIONARY:
				$class_schema['moduleConfig'] = [
					WPSOLR_Vespa_Constants::MODULE_TEXT_2_VEC_CONTEXTIONARY => [
						/*
						 * Prevent indexing class name or plenty of potential errors if class name is meaning less
						 * https://www.semi.technology/developers/vespa/current/modules/text2vec-contextionary.html#more-information
						 */
						'vectorizeClassName' => false,
					],
				];
				break;

			case WPSOLR_Vespa_Constants::MODULE_MULTI2VEC_CLIP:
				for ( $i = 0; $i <= 10; $i ++ ) {
					// Generate 10 blob field names ( blob[] is not a type) to store multiple images
					$base64_field_names[] = $this->_generate_blob_field_name( $i, true );
				}

				$class_schema['moduleConfig'] = [
					WPSOLR_Vespa_Constants::MODULE_MULTI2VEC_CLIP => [
						'imageFields' => $base64_field_names,
						'textFields'  => $text_field_names,
						/*
												'weights'     => [
													'textFields'  => [ 0.5 ],
													'imageFields' => [ 0.5 ]
												]*/
					],
				];
				break;

			case WPSOLR_Vespa_Constants::MODULE_TEXT_2_VEC_TRANSFORMERS:
			case WPSOLR_Vespa_Constants::MODULE_NONE:
				// Nothing to do
				break;


			default:
				throw new \Exception( sprintf( 'Module %s is not implemented yet.', $this->config['extra_parameters'][ WPSOLR_Option::OPTION_INDEXES_ANALYSER_ID ] ) );
		}




		return $class_schema;

	}

	/**
	 * Retrieve the live Vespa version
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected
	function get_version() {

		$status      = $this->search_engine_client->getStatus();
		$status_data = $status->getResponse()->getData();
		if ( ! empty( $status_data ) && ! empty( $status_data['message'] ) ) {
			throw new \Exception( $status_data['message'] );
		}

		$version = $this->search_engine_client->getVersion();

		if ( version_compare( $version, '5', '<' ) ) {
			throw new \Exception( sprintf( 'WPSOLR works only with Vespa >= 5. Your version is %s.', $version ) );
		}

		return $version;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public
	function search_engine_client_format_date(
		$date_str
	) {
		$result = false;

		if ( is_int( $date_str ) ) {

			$result = $date_str;

		} else {

			$timestamp = strtotime( $date_str );

			if ( is_int( $timestamp ) ) {
				$result = $timestamp;
			}

		}

		return $result;
	}

	/**
	 * Create a match_all query
	 *
	 * @return array
	 */
	protected
	function _create_match_all_query() {

		$params         = $this->get_search_index();
		$params['body'] = [ 'query' => [ 'match_all' => new \stdClass() ] ];

		return $params;
	}

	/**
	 * Create a bool query
	 *
	 * @param array $bool_query
	 *
	 * @return array
	 */
	protected
	function _create_bool_query(
		$bool_query
	) {

		$params         = $this->get_search_index();
		$params['body'] = [ 'query' => [ 'bool' => $bool_query ] ];

		return $params;
	}

	/**
	 * Create the index
	 *
	 * @href https://www.semi.technology/developers/vespa/current/restful-api-references/schema.html#create-a-class
	 *
	 * @param array $index_parameters
	 */
	protected function admin_create_index( &$index_parameters ) {

		$settings = $this->get_index_settings();

		$this->get_search_index()->create_index( $settings );
	}

	/**
	 * Delete the index and all its replicas in batch
	 *
	 * https://www.vespa.com/doc/guides/sending-and-managing-data/manage-your-indices/how-to/deleting-multiple-indices/
	 *
	 * @throws \Exception
	 */
	public function admin_delete_index() {

		// Reset converted fields for this index
		foreach (
			[
				WPSOLR_OPTION::OPTION_WEAVIATE_CONVERTED_FIELD_NAMES,
				WPSOLR_OPTION::OPTION_WEAVIATE_UNCONVERTED_FIELD_NAMES,
			] as $option_name
		) {
			delete_option( $option_name );
		}

		$this->get_search_index()->delete_index();
	}

	/**
	 * Add a configuration to the index if missing.
	 */
	protected function admin_index_update( &$index_parameters ) {
		$this->admin_create_index( $index_parameters );
	}

	/**
	 * Date fields usable are the unix timestamp version
	 * https://www.vespa.com/doc/guides/managing-results/refine-results/sorting/how-to/sort-an-index-by-date/
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function _convert_field_name_if_date( $field_name ): string {

		if ( ! empty( static::$convert_field_name_if_date[ $field_name ] ) ) {
			return static::$convert_field_name_if_date[ $field_name ];
		}

		$new_field_name = $field_name;

		if ( WpSolrSchema::get_custom_field_is_date_type( $field_name ) ) {
			$new_field_name .= wpsolrschema::_SOLR_DYNAMIC_TYPE_INTEGER;
		}

		// save
		static::$convert_field_name_if_date[ $field_name ] = $new_field_name;

		return $new_field_name;
	}

	/**
	 * Date fields usable are the unix timestamp version
	 *
	 * @param string $value
	 *
	 * @return int|string
	 */
	protected function _convert_to_unix_time_if_date( $value ) {

		if ( ! is_numeric( $value ) ) {

			$converted_value = 1000 * strtotime( $value ); // ms
			$value           = ( false === $converted_value ) ? $value : $converted_value;
		}

		return $value;
	}

	/**
	 * Get the analysers available
	 * @return array
	 */
	static public function get_analysers() {

		return [
			WPSOLR_Vespa_Constants::MODULE_NONE                     => [ 'label' => 'None', ],
			WPSOLR_Vespa_Constants::MODULE_TEXT_2_VEC_TRANSFORMERS  => [
				'label'      => 'Text (text2vec-transformers)',
				'is_default' => true,
			],
			WPSOLR_Vespa_Constants::MODULE_TEXT_2_VEC_CONTEXTIONARY => [ 'label' => 'Text (text2vec-contextionary)', ],
			WPSOLR_Vespa_Constants::MODULE_IMG2VEC_NEURAL           => [ 'label' => 'Image (img2vec-neural)', ],
			WPSOLR_Vespa_Constants::MODULE_MULTI2VEC_CLIP           => [ 'label' => 'Text and image (multi2vec-clip)', ],
			//static::$MODULE_QNA_TRANSFORMERS         => [ 'label' => 'Question Answering (qna-transformers)', ],
			//static::$MODULE_NER_TRANSFORMERS         => [ 'label' => 'Named Entity Recognition (ner-transformers)', ],
			//static::$MODULE_TEXT_SPELLCHECK          => [ 'label' => 'Spellcheck (text-spellcheck)', ],
		];
	}

	/**
	 * Strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function convert_field_name( string $field_name ): string {

		return sprintf( 'wpsolr_%s', $field_name );

		$option_converted_field_names = get_option( WPSOLR_OPTION::OPTION_WEAVIATE_CONVERTED_FIELD_NAMES, [] );

		if ( ! isset( $option_converted_field_names[ $field_name ] ) ) {
			$option_converted_field_names[ $field_name ] = str_replace( '-', '_', sanitize_title( sprintf( 'wpsolr_%s', $field_name ) ) );
			update_option( WPSOLR_OPTION::OPTION_WEAVIATE_CONVERTED_FIELD_NAMES, $option_converted_field_names );

			$option_unconverted_field_names                                                 = get_option( WPSOLR_OPTION::OPTION_WEAVIATE_UNCONVERTED_FIELD_NAMES, [] );
			$option_unconverted_field_names[ $option_converted_field_names[ $field_name ] ] = $field_name;
			update_option( WPSOLR_OPTION::OPTION_WEAVIATE_UNCONVERTED_FIELD_NAMES, $option_unconverted_field_names );
		}

		return $option_converted_field_names[ $field_name ];
	}

	/**
	 * Reverse strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function unconvert_field_name( string $field_name ): string {

		return substr( $field_name, strlen( 'wpsolr_' ) );
	}

	/**
	 * Strict Vespa name syntax
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	public function convert_class_name( string $class_name ): string {
		return $class_name;
	}

	/**
	 * @href https://docs.vespa.ai/en/reference/schema-reference.html#field-types
	 *
	 * @param string $field_name
	 * @param null $field_value
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _get_field_definition( string $field_name, &$field_value = null ): array {

		$result = [
			'label'    => $this->convert_field_name( $field_name ),
			'indexing' => 'summary | attribute',
			#'index'    => '',
		];

		$field_type = WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ?? WpSolrSchema::get_custom_field_dynamic_type( $field_name );

		// ['int'] => 'int'
		$field_type          = is_array( $field_type ) ? $field_type[0] : $field_type;
		$field_type_is_array = ! ( isset( WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ) && ! is_array( WpSolrSchema::DEFAULT_FIELD_TYPES[ $field_name ] ) );

		switch ( $field_type ) {
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_INTEGER_LONG:
				$result['type'] = 'long';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_DATE:
				$result['type'] = 'long';
				$field_value    = $this->search_engine_client_format_date( $field_value );
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT:
				$result['type'] = $field_type_is_array ? 'array<float>' : 'float';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_FLOAT_DOUBLE:
				$result['type'] = $field_type_is_array ? 'array<double>' : 'double';
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_S:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING:
			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING1:
				$result['type'] = $field_type_is_array ? 'array<string>' : 'string';
				//$result['match'] = 'exact'; // https://docs.vespa.ai/en/reference/schema-reference.html#match
				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT:
				$result['type']     = $field_type_is_array ? 'array<string>' : 'string';
				$result['indexing'] = 'summary | index';
				$result['summary']  = 'dynamic';
				//$result['match']    = 'text';
				// For partial matching: https://docs.vespa.ai/en/text-matching-ranking.html#n-gram-match
				$result['match'] = <<<'EOF'
{
	gram
	gram-size: 2
}
EOF;

				break;

			case WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64:
				$result['type']     = 'raw';
				$result['indexing'] = 'summary';
				break;

			default:
				throw new \Exception( "$field_type type not implemented for field $field_name" );
		}

		return $result;
	}

	/**
	 * @param string[] $fields
	 * @param string $error_msg
	 *
	 * @throws \Exception
	 */
	protected function _add_index_fields_definitions( array $fields ): void {

		$schema = [
			'label'      => $this->index_label,
			'new_fields' => [],
			'fieldset'   => [ 'wpsolr_title', 'wpsolr_content', ],
		];

		/**
		 * Collect fields already in schema
		 */
		$schema_file            = $this->get_search_index()->get_schema();
		$schema_existing_fields = [];
		if ( preg_match_all( "/field\s+(\w+)\s+type/", $schema_file, $matches ) ) {
			foreach ( $matches[1] as $match ) {
				$schema_existing_fields[] = $match;
			}
		}
		if ( preg_match( "/##BEGIN_FIELDS(.*?)##END_FIELDS/s", $schema_file, $matches ) ) {
			$schema['existing_fields'] = $matches[1];
		}

		foreach ( $fields as $field_name => $field_properties ) {
			if ( ( false !== strpos( $field_name, '%' ) ) ) {
				continue;
			}

			if ( ! in_array( $this->convert_field_name( $field_name ), $schema_existing_fields ) ) {
				$schema['new_fields'][] = $this->_get_field_definition( $field_name );
			}
		}

		/**
		 * Update schema with fields
		 */
		$this->get_search_index()->update_schema( $schema );
	}

	/**
	 * Generate new base64 field names: wpsolr_blob_0_b64, wpsolr_blob_1_b64, wpsolr_blob_2_b64, ....
	 *
	 * @param int $i
	 * @param bool $is_convert_field
	 *
	 * @return string
	 */
	protected function _generate_blob_field_name( int $i, $is_convert_field ): string {
		return str_replace( WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64,
			sprintf( '_%s%s', $i, WpSolrSchema::_SOLR_DYNAMIC_TYPE_BASE64 ),
			$is_convert_field ? $this->convert_field_name( WpSolrSchema::_FIELD_NAME_BASE64 ) : WpSolrSchema::_FIELD_NAME_BASE64
		);
	}

	/**
	 * @param array $property
	 *
	 * @return array
	 */
	protected function _add_extra_properties_to_field_definition( array $property ): array {
		return $property;


		switch ( $property['dataType'][0] ) {
			case 'string[]':
				// https://vespa.io/developers/vespa/configuration/schema-configuration#property-tokenization
				$property['tokenization'] = 'field'; // default is 'world', which uses an analyser
				break;
		}

		return $property;
	}

}

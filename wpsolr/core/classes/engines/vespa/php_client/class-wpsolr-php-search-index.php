<?php

namespace wpsolr\core\classes\engines\vespa\php_client;

use SimpleXMLElement;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Admin_Utilities;

class WPSOLR_Php_Search_Index {

	/**
	 * Vespa API parameters
	 */
	const VESPA_DEFAULT_TENANT = 'default';
	const VESPA_DEFAULT_APPLICATION = 'default';
	const VESPA_DEFAULT_DOCUMENT_NAMESPACE = 'wpsolr';

	/**
	 * Vespa API URLs
	 */
	const URL_APPLICATION_V2_TENANT_PREPAREANDACTIVATE = '/application/v2/tenant/%s/prepareandactivate';
	const URL_CONFIG_V2_TENANT_APPLICATION = '/config/v2/tenant/%s/application/%s/';
	const URL_CONFIG_V2_TENANT_APPLICATION_SEARCH_INDEXSCHEMA = '/config/v2/tenant/%s/application/%s/vespa.config.search.indexschema/%s/';

	/**
	 * Application files
	 */
	const ZIP_NEW_APPLICATION_PACKAGE = 'https://www.dropbox.com/s/z9k5iq4t559grqj/application_create.zip?dl=0';
	const SCHEMA_PATTERN = 'schemas/%s.sd';
	const SCHEMA_GENERATED_PATTERN = 'schemas/%s_generated.sd';
	const SERVICES_PATTERN = 'services.xml';
	const VALIDATION_OVERRIDES_PATTERN = 'validation-overrides.xml';

	const SERVICE_CONTENT_ID = 'wpsolr'; // id of the content element embedding WPSOLR's schema declarations
	const TWIG_SCHEMA_TEMPLATE_PATTERN = __DIR__ . '/../application_packages/application_update/schemas/%s';
	const TWIG_SCHEMA_ROOT_PATTERN = __DIR__ . '/../application_packages/application_update/%s';
	const SCHEMA_TEMPLATE_TWIG = 'schema_template.twig';
	const SCHEMA_TEMPLATE_GENERATED_TWIG = 'schema_template_generated.twig';
	const VALIDATION_OVERRIDES_TWIG = 'validation-overrides.twig';

	# https://github.com/vespa-engine/vespa/blob/master/config-model-api/src/main/java/com/yahoo/config/application/api/ValidationId.java
	const VALIDATION_OVERRIDES_VALIDATION_ID_REMOVE_SCHEMA = 'schema-removal';
	const VALIDATION_OVERRIDES_VALIDATION_ID_INDEXING_CHANGE = 'indexing-change';
	const VALIDATION_OVERRIDES_VALIDATION_ID_FIELD_TYPE_CHANGE = 'field-type-change';

	protected string $index_label;
	protected WPSOLR_Php_Rest_Api $api;
	protected array $config;

	/**
	 * Constructor.
	 *
	 * @param string $index_label
	 * @param WPSOLR_Php_Rest_Api $api
	 * @param array $config
	 */
	public function __construct( string $index_label, WPSOLR_Php_Rest_Api $api, array $config ) {
		$this->index_label = $index_label;
		$this->api         = $api;
		$this->config      = $config;
	}

	/**
	 * @return string
	 */
	public function get_index_label() {
		return $this->index_label;
	}
	/**************************************************************************************************************
	 *
	 * Weaviate REST API calls
	 *
	 *************************************************************************************************************/

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function is_ready(): bool {
		return $this->api->get( '/v1/.well-known/ready', [], [] )->is_http_code_200();
	}

	/**
	 * @param array $settings
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function _create_session_id( array $settings ) {
		$result = $this->api->post( '/application/v2/tenant/default/session?from=%s/application/v2/tenant/default/application/default/environment/default/region/default/instance/default', [ $this->api->generate_path() ], $settings );
		if ( empty( $session_id = $result->get_body_session_id() ) ) {
			throw new \Exception( 'A session id could not be created.' );
		}

		return $session_id;
	}

	/**
	 * @param string $session_id
	 * @param string[] $files
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	protected function _application_upload_files( array $files, string $session_id = '' ) {

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file => $file_content ) {
			$result = $this->api->put_binary_content( '/application/v2/tenant/default/session/%s/content/%s',
				[ $session_id, $file ],
				$file_content
			);

			if ( ! $result->is_http_code_200() || empty( $result->get_body_prepared() ) ) {
				throw new \Exception( sprintf( 'Problem during upload of index content file "%s"', $file ) );
			}

		}
	}

	/**
	 * @param string $session_id
	 * @param string[] $files
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _application_download_files( array $files, string $session_id = '' ): array {
		$results = [];

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file ) {

			try {

				$result = $this->api->get( '/application/v2/tenant/default/session/%s/content/%s', [
					$session_id,
					$file
				], [] );

				$results[ $file ] = $result->get_body_raw_content();

			} catch ( \Exception $e ) {
				// Nothing
			}
		}

		return $results;
	}

	/**
	 * @param string $session_id
	 * @param string[] $files
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _application_delete_files( array $files, string $session_id = '' ): array {
		$results = [];

		if ( empty( $session_id ) ) {
			$session_id = $this->_create_session_id( [] );
		}

		foreach ( $files as $file ) {

			try {

				$result = $this->api->delete( '/application/v2/tenant/default/session/%s/content/%s', [
					$session_id,
					$file
				], [] );

				$results[ $file ] = '';

			} catch ( \Exception $e ) {
				// Nothing
			}
		}

		return $results;
	}

	/**
	 * @param string $session_id
	 * @param array $settings
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	protected function _application_prepare_active( string $session_id, array $settings ) {
		$result = $this->api->put( '/application/v2/tenant/default/session/%s/prepared?applicationName=%s',
			[ $session_id, static::VESPA_DEFAULT_APPLICATION ],
			$settings
		);

		$result = $this->api->put( '/application/v2/tenant/default/session/%s/active',
			[ $session_id ],
			$settings
		);

		return $result;
	}

	/**
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function create_index( array $settings ) {
		$session_id = $this->_create_session_id( $settings );

		/**
		 * Download application files
		 */
		$files = $this->_application_download_files( [
			static::SERVICES_PATTERN,
			sprintf( static::SCHEMA_PATTERN, $this->index_label ),
			sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ),
		], $session_id );


		/**
		 * Modify services file to add/remove the schema if necessary
		 */
		$file_content = new SimpleXMLElement( $files[ static::SERVICES_PATTERN ] );
		$documents    = $file_content->xpath( sprintf( '//content[@id="%s"]', static::SERVICE_CONTENT_ID ) )[0]->documents;
		if ( ! $documents->xpath( sprintf( 'document[@type="%s"]', $this->index_label ) ) ) {
			$new_document = $documents->addChild( 'document' );
			$new_document->addAttribute( 'type', $this->index_label );
			$new_document->addAttribute( 'mode', 'index' );

			// Update content
			$files[ static::SERVICES_PATTERN ] = $file_content->asXML();
		}

		/**
		 * Generate schema file if not already present
		 */
		$schema_data = [
			'label' => $this->index_label,
		];
		if ( ! isset( $files[ sprintf( static::SCHEMA_PATTERN, $this->index_label ) ] ) ) {
			$files[ sprintf( static::SCHEMA_PATTERN, $this->index_label ) ] = $this->generate_twig_content( self::SCHEMA_TEMPLATE_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema_data );
		}

		/**
		 * Modify generated schema file
		 */
		$files[ sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ) ] = $this->generate_twig_content( self::SCHEMA_TEMPLATE_GENERATED_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema_data );


		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		return $this->_application_prepare_active( $session_id, $settings );
	}

	/**
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function update_index( array $settings ) {
		return $this->api->put( '/v1/schema', [ $this->index_label ], $settings );
	}

	public function get_index_fields_definitions() {
		$results = $this->api->get( '/v1/schema/%s', [ $this->index_label ], [] );

		return $results->get_fields();
	}

	/**
	 * @param array $field_definition
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function add_index_field_definition( array $field_definition ) {
		return $this->api->post( '/v1/schema/%s/properties', [ $this->index_label ], $field_definition );
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function has_index(): bool {

		/**
		 * Is application already deployed?
		 */
		try {
			$this->api->get( static::URL_CONFIG_V2_TENANT_APPLICATION, [
				static::VESPA_DEFAULT_TENANT,
				static::VESPA_DEFAULT_APPLICATION
			], [] );
		} catch ( \Exception $e ) {
			if ( str_contains( $e->getMessage(), 'NOT_FOUND' ) ) {
				// Deploy application before creation the index
				$this->api->post_binary_content( static::URL_APPLICATION_V2_TENANT_PREPAREANDACTIVATE, [
					static::VESPA_DEFAULT_TENANT
				], file_get_contents( WPSOLR_Admin_Utilities::convert_dropbox_url_from_html_to_zip( static::ZIP_NEW_APPLICATION_PACKAGE ) ) );

			} else {
				throw $e;
			}
		}


		/**
		 * Is index already deployed?
		 */
		try {
			/**
			 * Download index configuration files from the application session
			 */
			$results = $this->_application_download_files( $files = [
				static::SERVICES_PATTERN,
				sprintf( static::SCHEMA_PATTERN, $this->index_label ),
				sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ),
			] );

			return ( count( $files ) === count( $results ) );

		} catch ( \Exception $e ) {
			if ( str_contains( $e->getMessage(), 'NOT_FOUND' ) ) {
				return false;
			} else {
				throw $e;
			}
		}

	}

	/**
	 * @throws \Exception
	 */
	public function delete_index() {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download application files
		 */
		$files = $this->_application_download_files( [
			static::SERVICES_PATTERN,
			static::VALIDATION_OVERRIDES_PATTERN,
		], $session_id );


		/**
		 * Modify services file to remove the schema
		 */
		$file_content = new SimpleXMLElement( $files[ static::SERVICES_PATTERN ] );
		$documents    = $file_content->xpath( sprintf( '//content[@id="%s"]', static::SERVICE_CONTENT_ID ) )[0]->documents;
		if ( $document = $documents->xpath( sprintf( 'document[@type="%s"]', $this->index_label ) ) ) {
			// https://stackoverflow.com/questions/9643116/deleting-simplexmlelement-node
			unset( $document[0][0] );

			// Update content
			$files[ static::SERVICES_PATTERN ] = $file_content->asXML();
		}

		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		$today                                         = date( "Y-m-d", time() );
		$files[ static::VALIDATION_OVERRIDES_PATTERN ] = $this->generate_twig_content(
			self::VALIDATION_OVERRIDES_TWIG, static::TWIG_SCHEMA_ROOT_PATTERN,
			[
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_REMOVE_SCHEMA,
				],
			] );


		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		$this->_application_prepare_active( $session_id, [] );


		/**
		 * Remove unused application files with a new session
		 */
		$session_id = $this->_create_session_id( [] );
		$this->_application_delete_files( [
			static::VALIDATION_OVERRIDES_PATTERN,
			sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ),
			sprintf( static::SCHEMA_PATTERN, $this->index_label ),
		], $session_id );
		$result = $this->_application_prepare_active( $session_id, [] );

		return $result;
	}

	/**
	 * @param array $schema
	 *
	 * @return void
	 */
	public function update_schema( array $schema ) {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download schema files
		 */
		$files = $this->_application_download_files( [
			sprintf( static::SCHEMA_PATTERN, $this->index_label ),
			sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ),
			static::VALIDATION_OVERRIDES_PATTERN,
		], $session_id );


		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		/**
		 * Modify validation overrides to add/remove the schema if necessary
		 */
		$today                                         = date( "Y-m-d", time() );
		$files[ static::VALIDATION_OVERRIDES_PATTERN ] = $this->generate_twig_content(
			self::VALIDATION_OVERRIDES_TWIG, static::TWIG_SCHEMA_ROOT_PATTERN,
			[
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_INDEXING_CHANGE,
				],
				[
					'until'         => $today,
					'validation_id' => self::VALIDATION_OVERRIDES_VALIDATION_ID_FIELD_TYPE_CHANGE,
				],
			] );

		/**
		 * Update schema
		 */
		$files[ sprintf( static::SCHEMA_PATTERN, $this->index_label ) ]           = $this->generate_twig_content(
			self::SCHEMA_TEMPLATE_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema );
		$files[ sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ) ] = $this->generate_twig_content(
			self::SCHEMA_TEMPLATE_GENERATED_TWIG, static::TWIG_SCHEMA_TEMPLATE_PATTERN, $schema );


		/**
		 * Upload
		 */
		$this->_application_upload_files( $files, $session_id );

		/**
		 * Prepare and activate the application modifications
		 */
		$this->_application_prepare_active( $session_id, [] );

		// Wait
		sleep( 10 );
	}

	/**
	 *
	 * @return string
	 */
	public function get_schema() {
		$session_id = $this->_create_session_id( [] );

		/**
		 * Download schema files
		 */
		$files = $this->_application_download_files( [
			sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ),
		], $session_id );

		return $files[ sprintf( static::SCHEMA_GENERATED_PATTERN, $this->index_label ) ];
	}

	/**
	 * @https://docs.vespa.ai/en/reference/document-select-language.html
	 *
	 * @param string $id
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function delete_object_id( string $id ) {
		return $this->api->delete( '/document/v1/%s/%s/docid/%s', [
			$this->index_label, // namespace
			$this->index_label, // schema
			$id
		] );
	}

	/**
	 * @param string $selection
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function delete_objects( string $selection ) {
		return $this->api->delete( "/document/v1/%s/%s/docid?cluster=%s&selection=%s", [
			$this->index_label, // namespace
			$this->index_label, // schema
			static::SERVICE_CONTENT_ID, // cluster
			$selection
		] );
	}

	/**
	 * @param array $query
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function search( array $query ): WPSOLR_Php_Rest_Api_Response {
		return $this->api->post( '/search/', [], $query );
	}

	/**
	 * @href https://www.semi.technology/developers/weaviate/current/restful-api-references/batch.html#method-and-url
	 *
	 * @param array $documents
	 *
	 * @return WPSOLR_Php_Rest_Api_Response
	 * @throws \Exception
	 */
	public function index_objects( array $documents ): WPSOLR_Php_Rest_Api_Response {
		// https://docs.vespa.ai/en/document-v1-api-guide.html#create-if-nonexistent - http://localhost:8080/document/v1/wpsolr1/wpsolr1/docid/47?create=true
		return $this->api->post( '/document/v1/%s/%s/docid/%%s?create=true', [
			$this->index_label, // namespace
			$this->index_label, // schema
		], $documents, true );

	}

	/**
	 * @param string $file
	 * @param array $data
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function generate_twig_content( string $file, string $folder, array $data ): string {
		return WPSOLR_Service_Container::get_template_builder()->load_template(
			[
				'template_file' => sprintf( $folder, $file ),
				'template_args' => 'data',
			],
			$data
		);
	}

}
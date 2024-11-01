<?php

namespace wpsolr\core\classes\engines\opensearch_php;


use wpsolr\core\classes\engines\elasticsearch_php\WPSOLR_ElasticsearchClient;

/**
 * Some common methods of the OpenSearch client.
 */
trait WPSOLR_OpenSearchClient {
	use WPSOLR_ElasticsearchClient;

	protected $FILE_CONF_OS_INDEX_1_0 = 'wpsolr_index_1_0.json';

	/**
	 * @return string
	 */
	protected function _get_configuration_file_from_version(): string {
		try {

			$version = $this->search_engine_client->info()['version']['number'];

			$file = $this->FILE_CONF_OS_INDEX_1_0;
			if ( version_compare( $version, '1.0', '>=' ) ) {

				$file = $this->FILE_CONF_OS_INDEX_1_0;

			}

		} catch ( \Exception $e ) {
			// OpenSearch does not give access to cluster infos

			$file = $this->FILE_CONF_OS_INDEX_1_0;
		}

		return $file;
	}
	
	/**
	 * @return \OpenSearch\ClientBuilder
	 */
	protected function _get_client_builder() {
		return \OpenSearch\ClientBuilder::create();
	}

}

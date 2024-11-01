<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_OpenSearch_None extends WPSOLR_Hosting_Api_Abstract {

	const HOSTING_API_ID = 'none_opensearch';

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return sprintf( self::NONE_LABEL, 'OpenSearch' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_OPENSEARCH;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_PROTOCOL_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_HOST_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_PORT_ELASTICSEARCH_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_SHARDS_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_OPENSEARCH_REPLICAS_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_KEY_DEFAULT,
			static::FIELD_NAME_FIELDS_INDEX_SECRET_DEFAULT,
		];

		return $result;
	}
}
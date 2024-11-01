<?php

namespace wpsolr\core\classes\hosting_api;

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;

class WPSOLR_Hosting_Api_Vespa_None extends WPSOLR_Hosting_Api_Abstract {

	const HOSTING_API_ID = 'none_vespa';

	/**
	 * @inerhitDoc
	 */
	public function get_is_disabled() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return true;
	}

	/**
	 * @inerhitDoc
	 */
	public function get_is_endpoint_only() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return sprintf( self::NONE_LABEL, 'Vespa' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_engine() {
		return WPSOLR_AbstractEngineClient::ENGINE_VESPA;
	}

	/**
	 * @inheritDoc
	 */
	public function get_is_host_contains_user_password() {
		return false; // Vespa does not require it
	}

	/**
	 * @return string
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-vespa-index/';
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		$result = [
			static::FIELD_NAME_FIELDS_INDEX_LABEL_DEFAULT,
			[
				self::FIELD_NAME_FIELDS_INDEX_ENDPOINT => [
					self::FIELD_NAME_LABEL                 => 'Admin and config cluster',
					self::FIELD_NAME_PLACEHOLDER           => 'Copy a Vespa instance URL here, like http://localhost:19071',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your Server URL here',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_INDEX_ENDPOINT_1 => [
					self::FIELD_NAME_LABEL                 => 'Stateless container cluster',
					self::FIELD_NAME_PLACEHOLDER           => 'Copy a Vespa instance URL here, like http://localhost:8080',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => true,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_COPY . 'your Server URL here',
					],
				],
			],

		];

		return $result;
	}

}
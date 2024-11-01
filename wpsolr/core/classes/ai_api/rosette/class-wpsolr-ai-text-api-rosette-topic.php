<?php

namespace wpsolr\core\classes\ai_api\rosette;

use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;

class WPSOLR_AI_Text_Api_Rosette_Topic extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_rosette_topic';

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
		return static::TEXT_SERVICE_EXTRACTION_TOPIC['label'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'https://www.rosette.com/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/configure-your-indexes/create-an-algolia-index/';
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

		$result = [
			self::FIELD_NAME_FIELDS_URL_DEFAULT,
		];

		return $result;
	}
}

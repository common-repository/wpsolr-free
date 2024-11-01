<?php

namespace wpsolr\core\classes\ai_api\systran;

use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;

class WPSOLR_AI_Text_Api_Systran extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_systran';

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
		return 'Systran.io Natural Language';
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'https://platform.systran.net/samples/nlp';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/systran-natural-language/';
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_SYSTRAN;
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

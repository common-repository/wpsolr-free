<?php

namespace wpsolr\core\classes\models\taxonomy;


use WP_Term;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\models\WPSOLR_Model_Abstract;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Model_Taxonomy_Term
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Taxonomy_Term extends WPSOLR_Model_Abstract {

	/** @var \WP_Term */
	protected $data;

	/**
	 * @inheritdoc
	 */
	public function get_model_meta_type_class() {
		return WPSOLR_Model_Meta_Type_Taxonomy::class;
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return $this->data->term_id;
	}

	/**
	 * @inheritdoc
	 */
	function get_type() {
		return $this->data->taxonomy;
	}

	/**
	 * @inheritdoc
	 */
	public function get_date_modified() {
		return null;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->data->name;
	}

	/**
	 * @inheritdoc
	 */
	public function create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {

		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] = ! empty( trim( $this->data->description ) ) ? $this->data->description : ' '; // Prevent empty content error with ES
		if ( ! $search_engine_client->get_has_exists_filter() ) {
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_IS_EXCLUDED_S ] = WPSOLR_AbstractEngineClient::FIELD_VALUE_UNDEFINED;
		}
	}


	/**
	 * @inheritdoc
	 */
	public function get_permalink( $url_is_edit, $model ) {
		return $url_is_edit ? get_edit_term_link( ( $model instanceof WP_Term ) ? $model->term_id : (int) $model, $this->get_type() )
			: get_term_link( ( $model instanceof WP_Term ) ? $model->term_id : (int) $model, $this->get_type() );
	}

	/**
	 * @inheritdoc
	 */
	protected function get_default_result_content() {
		return $this->data->description;
	}

	/**
	 * @inerhitDoc
	 */
	public function update_custom_field( string $name, string $value ) {
		update_term_meta( $this->get_id(), $name, $value );
	}

	/**
	 * @inerhitDoc
	 */
	public function get_custom_field( string $name, bool $single = false ) {
		return get_term_meta( $this->get_id(), $name, $single );
	}

}
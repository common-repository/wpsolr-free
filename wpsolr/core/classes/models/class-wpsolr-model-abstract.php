<?php

namespace wpsolr\core\classes\models;

use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Model_Abstract
 * @package wpsolr\core\classes\models
 */
abstract class WPSOLR_Model_Abstract {

	/** @var mixed */
	protected $data;

	/** @var array */
	protected $solarium_document_for_update;

	/**
	 * @return mixed
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * @param object $data
	 *
	 * @return $this
	 */
	public function set_data( $data ) {
		$this->data = $data;

		return $this;
	}

	/**
	 * Child generate a document to index
	 *
	 * @param string $attachment_body
	 *
	 * @return mixed
	 */
	public function create_document_from_model_or_attachment( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {

		// Common indexing part here
		$this->solarium_document_for_update                                               = [];
		$data_id                                                                          = $this->get_id();
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_ID ]               = $search_engine_client->generate_model_unique_id( $this );
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PID ]              = $data_id;
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TYPE ]             = $this->get_type();
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_META_TYPE_S ]      = $this->get_meta_type();
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED ] = $search_engine_client->search_engine_client_format_date( $this->get_date_modified() );
		if ( ! empty( $title = $this->get_title() ) ) {
			// If no titles then no indexing with ES
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TITLE ]   = $title;
			$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TITLE_S ] = $title; // For sorting titles
		}

		// Index post url
		$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PERMALINK ] = $this->get_permalink( false, $this->data );
		$is_in_galaxy                                                              = false;
		$this->_index_post_url( $solarium_document_for_update, $this->get_id(), $is_in_galaxy );

		// First chance to customize the solarium update document
		$this->solarium_document_for_update = apply_filters(
			WPSOLR_Events::WPSOLR_FILTER_SOLARIUM_DOCUMENT_BEFORE_UPDATE,
			$this->solarium_document_for_update,
			$search_engine_client->get_indexing_options(),
			$this->get_data(),
			$attachment_body,
			$search_engine_client
		);

		// Let models do the indexing from now
		$this->create_document_from_model_or_attachment_inner( $search_engine_client, $attachment_body );

		// Last chance to customize the solarium update document
		$this->solarium_document_for_update = apply_filters(
			WPSOLR_Events::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE,
			$this->solarium_document_for_update,
			$search_engine_client->get_indexing_options(),
			$this->get_data(),
			$attachment_body,
			$search_engine_client
		);

		// Let models do the "after" indexing from now
		$this->after_create_document_from_model_or_attachment_inner( $search_engine_client, $attachment_body );

		return $this->solarium_document_for_update;
	}

	/**
	 * When no highlighting, use some other value to show in results
	 *
	 * @param string $content
	 * @param bool $is_shortcode_expanded
	 * @param int $highlighting_fragsize
	 */
	public function set_content_if_no_highlighted_results( &$content, $is_shortcode_expanded, $highlighting_fragsize ) {

		if ( empty( $content ) ) {
			// Set a default value for content if no highlighting returned.

			if ( isset( $data ) ) {
				// Excerpt first, or content.
				$content = $this->get_default_result_content();

				if ( $is_shortcode_expanded && ( strpos( $content, '[solr_search_shortcode]' ) === false ) ) {

					// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
					$content = do_shortcode( $content );
				}

				// Remove shortcodes tags remaining, but not their content.
				// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
				// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
				// Modified to enable "/" in attributes
				$content = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content );  # strip shortcodes, keep shortcode content;


				// Strip HTML and PHP tags
				$content = strip_tags( $content );

				if ( $highlighting_fragsize > 0 ) {
					// Cut content at the max length defined in options.
					$content = substr( $content, 0, $highlighting_fragsize );
				}
			}
		}

	}

	/**
	 * Content when no result highlighting
	 *
	 * @return string
	 */
	abstract protected function get_default_result_content();

	/**
	 * @return string
	 */
	public function get_title() {
		return '';
	}

	/**
	 * get model's data ID
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Child generate a document to index
	 *
	 * @param string $attachment_body
	 *
	 */
	abstract public function create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body );

	/**
	 *Update a custom field
	 *
	 * @param string $name
	 * @param string $value
	 * @param bool $is_update
	 */
	abstract public function update_custom_field( string $name, string $value );

	/**
	 *Update a custom field
	 *
	 * @param string $name
	 * @param bool $single
	 */
	abstract public function get_custom_field( string $name, bool $single = false );

	/**
	 * Child after generate a document to index
	 *
	 * @param string $attachment_body
	 *
	 */
	public function after_create_document_from_model_or_attachment_inner( WPSOLR_AbstractIndexClient $search_engine_client, $attachment_body ) {
		// Define in children
	}

	/**
	 * Get type
	 *
	 * @return string
	 */
	abstract function get_type();

	/**
	 * Get meta type
	 *
	 * @return string
	 */
	function get_meta_type() {
		return ( $this->get_model_meta_type_class() )::META_TYPE;
	}

	/**
	 * Get model class
	 *
	 * @return WPSOLR_Model_Meta_Type_Abstract
	 */
	abstract public function get_model_meta_type_class();

	/**
	 * get model's date of last modification. Used for indexing only models modified after last indexing date.
	 *
	 * @return string
	 */
	abstract public function get_date_modified();

	/**
	 * Index a post url
	 *
	 * @param $solarium_document_for_update
	 * @param $post_id
	 * @param bool $is_in_galaxy
	 *
	 * @return void
	 */
	protected function _index_post_url( &$solarium_document_for_update, $post_id, $is_in_galaxy ) {

		if ( $is_in_galaxy ) {

			// Master must get urls from the index, as the $post_id is not in local database
			$url = $this->get_permalink( false, $post_id );
			if ( false !== $url ) {

				$this->solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_HREF_STR ] = $url;
			}
		}
	}


	/**
	 * Get the permalink for the model
	 *
	 * @param bool $url_is_edit
	 * @param mixed $model object or id
	 *
	 * @return string
	 */
	abstract public function get_permalink( $url_is_edit, $model );

	/**
	 * @param $text
	 *
	 * @ref https://stackoverflow.com/questions/14114411/remove-all-special-characters-from-a-string
	 *
	 * @return string
	 */
	protected function _clean_string( $string ) {

		$string = str_replace( "\xc2\xa0", ' ', $string );; // Malformed UTF-8 characters, possibly incorrectly encoded. https://stackoverflow.com/questions/40724543/how-to-replace-decoded-non-breakable-space-nbsp

		return $string;
	}
}
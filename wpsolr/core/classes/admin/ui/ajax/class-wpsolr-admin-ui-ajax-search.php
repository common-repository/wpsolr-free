<?php

namespace wpsolr\core\classes\admin\ui\ajax;

/**
 * Ajax search lists (multiselect, drop-down,...)
 *
 * Class WPSOLR_Admin_UI_Ajax_Search
 * @package wpsolr\core\classes\admin\ui\ajax
 */
abstract class WPSOLR_Admin_UI_Ajax_Search extends WPSOLR_Admin_UI_Ajax {

	// Form fields
	const FORM_FIELD_FILTER_QUERIES_IDS = 'filter_queries_ids';
	const FORM_FIELD_NAME = 'name';
	const FORM_FIELD_FILTER_QUERY_EXPRESSION = 'expression';
	const FORM_FIELD_FILTER_QUERY_POSTS = 'filter_query_posts';
	const FORM_FIELD_FILTER_QUERY_TAXONOMIES = 'filter_query_taxonomies';
	const FORM_FIELD_FILTER_QUERY_TERMS = 'filter_query_terms';
	const FORM_FIELD_FILTER_QUERY_POST_TYPES = 'filter_query_post_types';
	const FORM_FIELD_IS_FILTER_QUERY_NOT = 'is_not';
	const FORM_FIELD_FILTER_QUERY_CONTENT = 'content';
	const FORM_FIELD_IS_FILTER_QUERY_OPTION_VALUE = 'is_filter_query';
	const FORM_FIELD_IS_FILTER_QUERY_OPTION_LABEL = 'is';
	const FORM_FIELD_IS_FILTER_QUERY_NOT_OPTION_VALUE = 'is_filter_query_not';
	const FORM_FIELD_IS_FILTER_QUERY_NOT_OPTION_LABEL = 'is not';
	const FORM_FIELD_IS_FILTER_QUERY_INACTIVE_OPTION_VALUE = '';
	const FORM_FIELD_IS_FILTER_QUERY_INACTIVE_OPTION_LABEL = 'is or is not filter_query';
	const FORM_FIELD_FILTER_QUERY_GENERATED_EXPRESSION = 'generated_expression';
	const FORM_FIELD_FILTER_QUERY_ENVIRONMENTS = 'filter_query_environments';

	// Ajax parameters
	const AJAX_PARAMETER_TERM = 'term';
	const AJAX_PARAMETER_LIMIT = 'limit';
	const AJAX_PARAMETER_INCLUDE = 'include';
	const AJAX_PARAMETER_EXCLUDE = 'exclude';

	// Execution parameters
	const PARAMETER_TERM = 'term';
	const PARAMETER_LIMIT = 'limit';
	const PARAMETER_INCLUDE = 'include';
	const PARAMETER_EXCLUDE = 'exclude';
	const PARAMETER_PARAMS = 'params';
	const PARAMETER_PARAMS_FILTERS = 'ajax_filters';
	const PARAMETER_PARAMS_SELECTORS = 'params_selectors';
	const PARAMETER_PARAMS_EXTRAS = 'params_extras';

	/**
	 * @inheritDoc
	 */
	public static function extract_parameters() {

		$params_extra = ( empty( $_GET[ self::PARAMETER_PARAMS ] ) || empty( $_GET[ self::PARAMETER_PARAMS ][ self::PARAMETER_PARAMS_EXTRAS ] ) ) ? [] : $_GET[ self::PARAMETER_PARAMS ][ self::PARAMETER_PARAMS_EXTRAS ];
		if ( empty( $params_extra ) && ! empty( $_GET[ self::PARAMETER_PARAMS_EXTRAS ] ) ) {
			$params_extra = $_GET[ self::PARAMETER_PARAMS_EXTRAS ] ?? [];
		}

		$parameters = [
			self::PARAMETER_TERM           => sanitize_text_field( empty( $_GET[ self::AJAX_PARAMETER_TERM ] ) ? '' : $_GET[ self::AJAX_PARAMETER_TERM ] ),
			self::PARAMETER_LIMIT          => sanitize_text_field( empty( $_GET[ self::AJAX_PARAMETER_LIMIT ] ) ? 10 : self::AJAX_PARAMETER_LIMIT ),
			self::PARAMETER_INCLUDE        => sanitize_text_field( empty( $_GET[ self::AJAX_PARAMETER_INCLUDE ] ) ? '' : $_GET[ self::AJAX_PARAMETER_INCLUDE ] ),
			self::PARAMETER_EXCLUDE        => sanitize_text_field( empty( $_GET[ self::AJAX_PARAMETER_EXCLUDE ] ) ? '' : $_GET[ self::AJAX_PARAMETER_EXCLUDE ] ),
			self::PARAMETER_PARAMS_FILTERS => sanitize_text_field( ( empty( $_GET[ self::PARAMETER_PARAMS ] ) || empty( $_GET[ self::PARAMETER_PARAMS ][ self::PARAMETER_PARAMS_FILTERS ] ) ) ? '' : $_GET[ self::PARAMETER_PARAMS ][ self::PARAMETER_PARAMS_FILTERS ] ),
			self::PARAMETER_PARAMS_EXTRAS  => array_map( 'sanitize_text_field', $params_extra ),
		];

		return $parameters;
	}

}
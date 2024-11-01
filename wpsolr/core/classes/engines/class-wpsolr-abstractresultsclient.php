<?php

namespace wpsolr\core\classes\engines;

/**
 * Class WPSOLR_AbstractResultsClient
 *
 * Abstract class for search results.
 */
abstract class WPSOLR_AbstractResultsClient {

	protected $results;
	protected $raw_results;

	/**
	 * Raw results
	 * @return mixed
	 */
	public function get_raw_results() {
		return $this->raw_results ?? $this->results;
	}

	/**
	 * @return mixed
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * @return mixed
	 */
	abstract public function get_suggestions();

	/**
	 * Get nb of results.
	 *
	 * @return int
	 * @throws \Exception
	 */
	abstract public function get_nb_results();

	/**
	 * Get nb of rows returned (limited by the limit parameter)
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function get_nb_rows() {
		// To be defined
		throw new \Exception( 'get_nb_rows() not implemented.' );
	}

	/**
	 * Get cursor mark during a scroll
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_cursor_mark() {
		// To be defined
		//throw new Exception( 'get_cursor_mark() not implemented.' );

		return '';
	}

	/**
	 * Get a facet
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	abstract public function get_facet( $facet_name );

	/**
	 * Get highlighting
	 *
	 * @param \Solarium\QueryType\Select\Result\Document|\Elastica\Result $result |object
	 *
	 * @return array
	 */
	abstract public function get_highlighting( $result );

	/**
	 * Get stats
	 *
	 * @param string $facet_name
	 * @param array $options
	 *
	 * @return array
	 */
	abstract public function get_stats( $facet_name, array $options = [] );

	/**
	 * Get top hits aggregation
	 *
	 * @param string $field_name
	 *
	 * @return array
	 */
	public function get_top_hits( $field_name ) {
		throw new \Exception( 'Group suggestions are not defined for this search engine.' );
	}

	/**
	 * @return mixed
	 */
	public function get_questions_answers_results() {
		// Override in children implementing Q&A
		return $this->get_results();
	}

	/**
	 * Get the query id returned by the search when event tracking is activated
	 * @return string
	 */
	public function get_event_tracking_query_id() {
		// Override in children supporting events tracking
		return '';
	}

}

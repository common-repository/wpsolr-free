<?php

namespace wpsolr\core\classes\engines\vespa\php_client;

use Exception;
use WP_Error;

class WPSOLR_Php_Rest_Api_Response {

	/**
	 * @var array
	 */
	protected $wp_response;
	/**
	 * @var mixed|null
	 */
	protected $body;

	/**
	 * Constructor.
	 *
	 * @param array|WP_Error $wp_response
	 */
	public function __construct( $wp_response ) {

		if ( $wp_response instanceof WP_Error ) {
			$this->_send_error_msg( $wp_response->get_error_message(), $wp_response->get_error_code() );
		}

		if ( ! empty( $wp_response['body'] ) ) {
			$this->body = json_decode( $wp_response['body'] ) ?? $wp_response['body'];

			if ( 200 !== ( $wp_response['response']['code'] ?? 200 ) ) {

				// WCS error
				if ( ! empty( $wp_response['body'] ) ) {
					$this->_send_error_msg( $wp_response['body'] );
				}

				if ( ! empty( $wp_response['response'] ) && ! empty( $wp_response['response']['message'] ) ) {
					$this->_send_error_msg( $wp_response['response']['message'] );
				}
			}

			if ( is_object( $this->body ) && ! empty( $this->body ) ) {

				// REST
				$error_code = 'error-code'; // '-' is not supported on a PHP object
				if ( ! empty( $this->body->$error_code ) && ! empty( $this->body->message ) ) {
					$this->_send_error_msg( $this->body->message, $this->body->$error_code );
				}

				// GraphQL Search
				if ( ! empty( $this->body->errors ) && isset( $this->body->errors[0] ) && ! empty( $this->body->errors[0]->message ) ) {
					$this->_send_error_msg( $this->body->errors[0]->message );
				}

				// GraphQL query
				if ( ! empty( $this->body->code ) && isset( $this->body->message ) ) {
					$this->_send_error_msg( $this->body->message );
				}
			}

			if ( is_array( $this->body ) && ! empty( $this->body ) ) {
				$messages = [];
				foreach ( $this->body as $body ) {

					// Batch indexing
					if ( ! empty( $body->result ) && isset( $body->result->errors ) ) {
						foreach ( $body->result->errors as $error ) {
							foreach ( $error as $message ) {
								$messages[] = $message->message;
							}
						}
					}

				}
				if ( ! empty( $messages ) ) {
					$this->_send_error_msg( implode( " | ", $messages ) );
				}
			}

		}

		$this->wp_response = $wp_response;
	}

	/**
	 * @return int
	 */
	protected function _get_http_code() {
		return $this->wp_response['response']['code'];
	}

	/**
	 * @return bool
	 */
	public function is_http_code_200(): bool {
		return ( 200 === $this->_get_http_code() );
	}

	/**
	 * @param string $message
	 * @param string $error_code
	 *
	 * @throws Exception
	 */
	protected function _send_error_msg( string $message, string $error_code = '' ) {

		if ( false !== strpos( $message, 'is not a valid class name' ) ) {
			// https://github.com/semi-technologies/weaviate/blob/bfbc4cec308ea6306ee858ca8a41e1a025fe7809/entities/schema/validation.go#L26
			$message = sprintf( '%s (Valid CamelCase format /^([A-Z][a-z]+)+$/: MyIndex. Invalid formats: myIndex, MyINdex, My Index, MyIndex1).', $message );
		}

		throw new Exception( empty( $error_code ) ? sprintf( 'Error sent from Vespa: %s ', $message ) : sprintf( 'Error sent from Vespa: (%s) %s ', $error_code, $message ) );
	}

	/**
	 * @return int
	 */
	public function get_count() {
		return $this->body->root->fields->totalCount;
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return $this->body->properties;
	}

	/**
	 * @return array
	 */
	public function get_results(): array {
		return (array) $this->body->root;
	}

	/**
	 * @return string
	 */
	public function get_body_session_id(): string {
		return ( (array) $this->body )['session-id'] ?? '';
	}

	/**
	 * @return string
	 */
	public function get_body_prepared(): string {
		return ( (array) $this->body )['prepared'] ?? '';
	}

	/**
	 * @return string
	 */
	public function get_body_raw_content(): string {
		return $this->body;
	}
}
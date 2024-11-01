<?php

namespace wpsolr\core\classes\utilities;

/**
 * Sanitize variables
 *
 */
class WPSOLR_Sanitize {

	/**
	 * Recursive sanitization of string and arrays
	 *
	 * @param string|array $to_be_sanitized
	 *
	 * @return string|array
	 */
	public static function sanitize_text_field( $to_be_sanitized ) {

		if ( is_array( $to_be_sanitized ) ) {

			// Array
			$results = [];
			foreach ( $to_be_sanitized as $key => $value ) {
				$results[ $key ] = static::sanitize_text_field( $value );
			}

			return $results;

		} else {
			// Simple text
			return sanitize_text_field( $to_be_sanitized );
		}

	}
}

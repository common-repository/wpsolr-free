<?php

namespace wpsolr\core\classes\utilities;

/**
 * Escape output
 *
 */
class WPSOLR_Escape {

	/**
	 * @param string $json
	 *
	 */
	public static function esc_json( string $json ) {
		// No need: done in wp_json_decode()
		return $json;
	}

	/**
	 * @param string $textarea
	 *
	 * @return string
	 */
	public static function esc_textarea( string $textarea ): string {
		// We use escape attributes in WPSOLR
		#return esc_textarea( $textarea );
		return self::esc_attr( $textarea, false );
	}

	/**
	 * @param string $url
	 *
	 */
	public static function esc_url( string $url ) {
		return esc_url( $url );
	}

	/**
	 * @param string $attr
	 * @param bool $is_strict
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function esc_attr_jquery( string $attr ): string {
		// jQuery's selectors are HTML
		return static::esc_attr( $attr, false );
	}

	/**
	 * @param string $attr
	 * @param bool $is_strict
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function esc_attr( string $attr, bool $is_strict = true ): string {
		$esc_attr = esc_attr( $attr );
		if ( $is_strict && ( $attr !== $esc_attr ) ) {
			error_log( sprintf( '(WPSOLR) Attribute is different from escaped value: %s => %s', $attr, $esc_attr ) );
		}

		return $esc_attr;
	}

	/**
	 * @param string $attr
	 * @param bool $is_strict
	 *
	 */
	public static function echo_esc_attr_jquery( string $attr ) {
		// jQuery's selectors are HTML
		echo static::esc_attr_jquery( $attr );
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	public static function esc_html( string $html ): string {
		return esc_html( $html );
	}

	/**
	 * @param string $escaped
	 *
	 * @return string
	 */
	public static function esc_escaped( string $escaped ): string {
		// Do nothing on already escaped
		return $escaped;
	}

	/**
	 * @param string $escaped
	 *
	 */
	public static function echo_escaped( string $escaped ) {
		echo static::esc_escaped( $escaped );
	}

	/**
	 * @param string $attr
	 *
	 * @throws \Exception
	 */
	public static function echo_esc_attr( string $attr ) {
		echo static::esc_attr( $attr );
	}

	/**
	 * @param string $html
	 *
	 */
	public static function echo_esc_html( string $html ) {
		echo static::esc_html( $html );
	}

	/**
	 * @param string $url
	 *
	 */
	public static function echo_esc_url( string $url ) {
		echo static::esc_url( $url );
	}

	/**
	 * @param string $json
	 *
	 */
	public static function echo_esc_json( string $json ) {
		echo static::esc_json( $json );
	}

	/**
	 * @param string $textarea
	 *
	 * @return void
	 */
	public static function echo_esc_textarea( string $textarea ) {
		echo static::esc_textarea( $textarea );
	}

	/**
	 * @param string $js_script
	 *
	 * @return void
	 */
	public static function echo_esc_js( string $js_script ) {
		echo esc_js( $js_script );
	}

}

<?php

namespace wpsolr\core\classes\ai_api;

use wpsolr\core\classes\utilities\WPSOLR_Post_Type_Image;

abstract class WPSOLR_AI_Image_Api_Abstract extends WPSOLR_AI_Api_Abstract {
	use WPSOLR_Post_Type_Image;

	/**
	 * Media formats supported
	 */
	const MEDIA_FORMATS_REGEXP = '';

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return <<<'TAG'
Images are sent to the API in different ways:
<br>- From the media library if you select "Media" on your index post types settings below.
<br>- From the featured image, or attached medias, or external image links on your index post types selected in the settings below.
<br><br>
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return static::IMAGE_SERVICE_ANNOTATION['label'];
	}

	/**
	 * @param string $send_file_as
	 * @param string $url
	 *
	 * @return array
	 */
	protected function _get_file_content_or_source( $send_file_as, $url ) {

		return [ $send_file_as => ( static::$FILE_SEND_AS_CONTENT === $send_file_as ) ? file_get_contents( $url ) : $url ];
	}

	/**
	 * @inheritDoc
	 */
	protected function _prepare_call( $document_for_update, &$call_data ) {

		/**
		 * Send 'content' file or 'source' file
		 */
		$params = [
			'internal_image_mode' => $this->_get_internal_image_mode(),
			'external_image_mode' => $this->_get_external_image_mode(),
			'is_attachment_image' => true,
			'is_featured_image'   => true,
			'is_embedded_image'   => true,
		];

		$images = $this->_extract_images_from_document( $document_for_update, $params );

		/**
		 * Complete.
		 */
		if ( ! empty( $images ) ) {
			$call_data['images'] = $images;

			return true;
		}

		return false;
	}

	/**
	 * @param string $image_src
	 *
	 * @return bool
	 */
	protected function _get_is_image_format_supported( $image_src ) {
		// Check in lower or upper case
		return ( 1 === preg_match( static::MEDIA_FORMATS_REGEXP, $image_src ) ) ||
		       ( 1 === preg_match( strtoupper( static::MEDIA_FORMATS_REGEXP ), $image_src ) );
	}

	/**
	 * @return string
	 */
	protected function _get_internal_image_mode() {
		return ! empty( $this->ai_api[ self::FIELD_NAME_FIELDS_INTERNAL_IMAGE_SEND_URL ] ) ? static::$FILE_SEND_AS_URL : static::$FILE_SEND_AS_CONTENT;
	}

	/**
	 * @return string
	 */
	protected function _get_external_image_mode() {
		return ! empty( $this->ai_api[ self::FIELD_NAME_FIELDS_EXTERNAL_IMAGE_SEND_CONTENT ] ) ? static::$FILE_SEND_AS_CONTENT : static::$FILE_SEND_AS_URL;;
	}

}

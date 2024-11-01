<?php

namespace wpsolr\core\classes\ai_api\aws;

use Aws\Result;

/**
 * https://docs.aws.amazon.com/rekognition/latest/dg/API_RecognizeCelebrities.html
 */
class WPSOLR_AI_Image_Api_Amazon_Detect_Celebrities extends WPSOLR_AI_Image_Api_Amazon_Abstract {

	const API_ID = 'image_aws_celebrities';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'Celebrities detection';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return sprintf( "The following properties are detected from images: celebrities names. %s", parent::get_documentation_text() );
	}

	/**
	 * Fields
	 */
	protected const FIELD_IMAGE_AMAZON_ANNOTATE_CELEBRITIES_NAME = 'name';

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			self::FIELD_IMAGE_AMAZON_ANNOTATE_CELEBRITIES_NAME,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function _call_api_for_one_image( $api_client, $image ) {
		return $api_client->recognizeCelebrities( [
			'Image'         => [ 'Bytes' => $image[ static::$FILE_SEND_AS_CONTENT ] ],
			'MinConfidence' => 50,
		] );
	}

	/**
	 * @param Result[] $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {

		if ( empty( $raw_service_response ) ) {
			// No response
			return [];
		}

		// Convert indexed to associative array
		$results = [];

		foreach ( $raw_service_response as $response ) {
			foreach ( $response->get( 'CelebrityFaces' ) as $annotation ) {

				if ( $this->_is_above_treshold_score( $annotation ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_CELEBRITIES_NAME,
						'value' => $annotation['Name'],
					];
				}
			}
		}

		return $this->_group_api_entities_by_type( $results, 'type', 'value' );;

	}

}

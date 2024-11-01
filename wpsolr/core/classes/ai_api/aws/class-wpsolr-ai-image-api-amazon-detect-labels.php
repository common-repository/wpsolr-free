<?php

namespace wpsolr\core\classes\ai_api\aws;

use Aws\Result;

/**
 * https://medium.com/@iaay/laravel-aws-rekognition-sdk-integration-757518974da9
 * https://docs.aws.amazon.com/rekognition/latest/dg/API_DetectLabels.html
 */
class WPSOLR_AI_Image_Api_Amazon_Detect_Labels extends WPSOLR_AI_Image_Api_Amazon_Abstract {

	const API_ID = 'image_aws_labels';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'Labels detection';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return sprintf( "The following properties are detected from images: labels. %s", parent::get_documentation_text() );
	}

	/**
	 * Fields
	 */
	protected const FIELD_IMAGE_AMAZON_ANNOTATE_LABEL = 'label';

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			self::FIELD_IMAGE_AMAZON_ANNOTATE_LABEL,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function _call_api_for_one_image( $api_client, $image ) {
		return $api_client->detectLabels( [
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
			foreach ( $response->get( 'Labels' ) as $annotation ) {
				if ( $this->_is_above_treshold_score( $annotation ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_LABEL,
						'value' => $annotation['Name'],
					];
				}
			}
		}

		return $this->_group_api_entities_by_type( $results, 'type', 'value' );;

	}

}

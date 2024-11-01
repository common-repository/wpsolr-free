<?php

namespace wpsolr\core\classes\ai_api\aws;

use Aws\Result;

/**
 * https://docs.aws.amazon.com/rekognition/latest/dg/API_DetectFaces.html
 */
class WPSOLR_AI_Image_Api_Amazon_Detect_Faces extends WPSOLR_AI_Image_Api_Amazon_Abstract {

	const API_ID = 'image_aws_faces';

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return 'Faces detection';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return sprintf( "The following properties are detected from images: emotions, gender, age range. %s", parent::get_documentation_text() );
	}

	/**
	 * Fields
	 */
	protected const EMOTIONS = 'Emotions';
	protected const FIELD_IMAGE_AMAZON_ANNOTATE_FACE_EMOTIONS = 'face_emotions';
	protected const GENDER = 'Gender';
	protected const FIELD_IMAGE_AMAZON_ANNOTATE_FACE_GENDER = 'face_gender';
	protected const AGE_RANGE = 'AgeRange';
	protected const FIELD_IMAGE_AMAZON_ANNOTATE_FACE_AGE_RANGE = 'face_age_range';

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_EMOTIONS,
			self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_GENDER,
			self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_AGE_RANGE,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function _call_api_for_one_image( $api_client, $image ) {
		return $api_client->detectFaces( [
			'Image'         => [ 'Bytes' => $image[ static::$FILE_SEND_AS_CONTENT ] ],
			'Attributes'    => [ 'ALL' ],
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
			foreach ( $response->get( 'FaceDetails' ) as $face_details ) {
				foreach ( $face_details as $name => $details ) {
					if ( $this->_is_above_treshold_score( $face_details ) ) {
						switch ( $name ) {
							case self::AGE_RANGE:
								$results[] = [
									'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_AGE_RANGE,
									'value' => $details['Low'],
								];
								$results[] = [
									'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_AGE_RANGE,
									'value' => $details['High'],
								];
								break;

							case self::GENDER:
								$results[] = [
									'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_GENDER,
									'value' => $details['Value'],
								];
								break;

							case self::EMOTIONS:
								/**
								 * "Emotions": [
								 *  {
								 *  "Type": "CALM",
								 *  "Confidence": 98.92642211914062
								 *  }
								 * ]
								 */
								foreach ( $details as $annotation_detail ) {
									$results[] = [
										'type'  => self::FIELD_IMAGE_AMAZON_ANNOTATE_FACE_EMOTIONS,
										'value' => $annotation_detail['Type'],
									];

								}
								break;
						}
					}
				}
			}

		}

		return $this->_group_api_entities_by_type( $results, 'type', 'value' );;

	}

}

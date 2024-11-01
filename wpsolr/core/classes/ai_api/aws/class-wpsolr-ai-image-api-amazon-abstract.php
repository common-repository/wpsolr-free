<?php

namespace wpsolr\core\classes\ai_api\aws;

use Aws\Rekognition\RekognitionClient;
use Aws\Result;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Image_Api_Abstract;

/**
 * https://medium.com/@iaay/laravel-aws-rekognition-sdk-integration-757518974da9
 * https://docs.aws.amazon.com/rekognition/latest/dg/API_DetectLabels.html
 */
abstract class WPSOLR_AI_Image_Api_Amazon_Abstract extends WPSOLR_AI_Image_Api_Abstract {

	const MEDIA_FORMATS_REGEXP = '/\.jpeg|\.jpg|\.png/';

	/**
	 * @inheritDoc
	 */
	protected function _get_internal_image_mode() {
		// AWS accepts only content images
		return static::$FILE_SEND_AS_CONTENT;
	}

	/**
	 * @inheritDoc
	 */
	protected function _get_external_image_mode() {
		// AWS accepts only content images
		return static::$FILE_SEND_AS_CONTENT;
	}

	/**
	 * @inheritDoc
	 */
	public function get_is_disabled() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_is_no_hosting() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'https://aws.amazon.com/rekognition/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/ai-image-and-ocr-apis-add-on/amazon-rekognition/';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return '<br><br>' . parent::get_documentation_text() . <<<'TAG'
Amazon Rekognition makes it easy to add image and video analysis to your applications using proven, highly scalable, deep learning technology that requires no machine learning expertise to use. With Amazon Rekognition, you can identify objects, people, text, scenes, and activities in images and videos, as well as detect any inappropriate content. Amazon Rekognition also provides highly accurate facial analysis and facial search capabilities that you can use to detect, analyze, and compare faces for a wide variety of user verification, people counting, and public safety use cases.
<br><br>
Image formats supported: jpeg, jpg, png
<br><br>
See the <a href="https://docs.aws.amazon.com/rekognition/latest/dg/limits.html" target="_new">Limits of the API</a>.<br>
<span style="color:red">This API does not support batch mode: each image requires one call.
So, if your post type text contains 10 images, 10 calls to the API will be performed.
</span>
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::IMAGE_PROVIDER_AMAZON_REKOGNITION;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY => [
					self::FIELD_NAME_LABEL                 => 'AWS access key ID',
					self::FIELD_NAME_PLACEHOLDER           => 'AWS access key ID',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS access key ID',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_SERVICE_SECRET => [
					self::FIELD_NAME_LABEL                 => 'AWS secret access key',
					self::FIELD_NAME_PLACEHOLDER           => 'AWS secret access key',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your AWS secret access key',
					],
				],
			],
			self::FIELD_NAME_FIELDS_SERVICE_AWS_REGION_DEFAULT,
		];

	}

	/**
	 * @inheritDoc
	 *
	 * @return RekognitionClient
	 */
	protected function _create_api_client() {
		return new RekognitionClient( [
			'credentials' => [
				'key'    => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY ],
				'secret' => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_SECRET ],
			],
			'region'      => $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_AWS_REGION ],
			'version'     => 'latest',
		] );
	}

	/**
	 * @param RekognitionClient $api_client
	 *
	 * @return Result[]
	 * @inheritDoc
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		$results = [];
		foreach ( $args['images'] as $image ) {
			// No Rekognition batch API: call each image one after another

			// Update stats
			$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

			$results[] = $this->_call_api_for_one_image( $api_client, $image );
		}

		return $results;
	}

	/**
	 * Call the api for one image: Rekognition has no batch api!
	 * To be defined in children.
	 *
	 * @param RekognitionClient $api_client
	 * @param array $image
	 *
	 * @return Result
	 */
	abstract protected function _call_api_for_one_image( $api_client, $image );

	/**
	 * @param array $annotation
	 *
	 * @inheridoc
	 */
	protected function _get_api_results_score( $annotation ) {
		// MatchConfidence is in [0-100]. Expected in [0-1].
		if ( isset( $annotation['Confidence'] ) ) {
			return ( floatval( $annotation['Confidence'] ) / 100 );
		} elseif ( isset( $annotation['MaxConfidence'] ) ) {
			return ( floatval( $annotation['MaxConfidence'] ) / 100 );
		}

		return static::CONST_DEFAULT_SCORE_IF_NOT_FOUND;
	}

}

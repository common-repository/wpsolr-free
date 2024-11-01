<?php

namespace wpsolr\core\classes\ai_api\google;

use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\AnnotateImageResponse;
use Google\Cloud\Vision\V1\BatchAnnotateImagesResponse;
use Google\Cloud\Vision\V1\ColorInfo;
use Google\Cloud\Vision\V1\EntityAnnotation;
use Google\Cloud\Vision\V1\FaceAnnotation;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\ImageSource;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Color_Helper;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Image_Api_Abstract;

/**
 * https://googleapis.github.io/google-cloud-php/#/docs/cloud-vision/v1.1.1/vision/v1/imageannotatorclient
 * https://github.com/GoogleCloudPlatform/php-docs-samples/tree/master/vision/src
 */
class WPSOLR_AI_Image_Api_Google_Annotate extends WPSOLR_AI_Image_Api_Abstract {

	const API_ID = 'image_google_annotate';

	/**
	 * Constants
	 */
	private const LIKELIHOOD = [
		'UNKNOWN',
		'VERY_UNLIKELY',
		'UNLIKELY',
		'POSSIBLE',
		'LIKELY',
		'VERY_LIKELY'
	];

	/**
	 * Image annotation feature types
	 */
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_LABEL = 'label';
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_LANDMARK = 'landmark';
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_LOGO = 'logo';
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_FACE = 'face';
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_TEXT = 'text';
	protected const FIELD_IMAGE_GOOGLE_ANNOTATE_DOMINANT_COLORS = 'colors';

	/**
	 * Replace use Google\Cloud\Vision\V1\Feature\Type that cannot be loaded during tests
	 * wpsolr\core\classes\tests\plugins\wpsolr_pro\extensions\ai_api\WPSOLR_Extension_AI_Api_Image_Score_Minimum_Zero_Local_Elasticsearch_AcceptanceTest::test_image_api_amazon_rekognition_attachment_detect_faces_score_minimum_zero
	| Error: Class 'Google\Cloud\Vision\V1\Feature\Type' not found
	 */
	const LABEL_DETECTION = 4;
	const LANDMARK_DETECTION = 2;
	const LOGO_DETECTION = 3;
	const FACE_DETECTION = 1;
	const DOCUMENT_TEXT_DETECTION = 11;
	const IMAGE_PROPERTIES = 7;

	const FEATURE_TYPES_OPTION_MAPPING = [
		self::LABEL_DETECTION         => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL,
		self::LANDMARK_DETECTION      => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LANDMARK,
		self::LOGO_DETECTION          => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LOGO,
		self::FACE_DETECTION          => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_FACE,
		self::DOCUMENT_TEXT_DETECTION => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_TEXT,
		//Type::IMAGE_PROPERTIES        => self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_PROPERTY,
	];

	const MEDIA_FORMATS_REGEXP = '/\.jpeg|\.jpg|\.png|\.gif|\.bmp|\.webp|\.raw|\.ico|\.pdf|\.tiff/';


	/**
	 * @var TranslationServiceClient
	 */
	protected $translation_api;

	/**
	 * @var string
	 */
	protected $translation_language;
	/**
	 * @var string
	 */
	protected $translation_location;

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
		return 'https://cloud.google.com/vision';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/ai-image-and-ocr-apis-add-on/google-vision/';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {

		return parent::get_documentation_text() . <<<'TAG'
The following properties are detected from images: labels, landmarks, logos, face emotions, texts (OCR).
<br><br>

Google Cloud’s Vision API offers powerful pre-trained machine learning models through REST and RPC APIs. Assign labels to images and quickly classify them into millions of predefined categories. Detect objects and faces, read printed and handwritten text, and build valuable metadata into your image catalog.
<br><br>
Image formats supported: jpeg, jpg, png, gif, bmp, webp, raw, ico, pdf, tiff
<br><br>
See the <a href="https://cloud.google.com/vision/docs/supported-files" target="_new">Limits of the API</a>.<br>
<span style="color:red">
This API requires the bcmath PHP library installed on your server. For instance: "sudo apt install php7.4-bcmath".
</span>

TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::IMAGE_PROVIDER_GOOGLE;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY_JSON => [
					self::FIELD_NAME_LABEL                 => 'Service account JSON key of the Google Project you authorized the Vision API',
					self::FIELD_NAME_PLACEHOLDER           => 'Service account JSON key',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your service account JSON key',
					],
				],
			],
			self::FIELD_NAME_FIELDS_INTERNAL_IMAGE_SEND_URL_DEFAULT,
			self::FIELD_NAME_FIELDS_EXTERNAL_IMAGE_SEND_CONTENT_DEFAULT,
			self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_TEXT_DEFAULT,
			self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_DEFAULT,
			[
				self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE => [
					self::FIELD_NAME_LABEL                 => 'Translate labels from images',
					self::FIELD_NAME_INSTRUCTION           => sprintf( '<p>%s</p> <p>%s <a href="%s" target="_new">language ISO-639-1 code</a>. %s</p> <p>%s</p>',
						'Google extracted labels are always in English, even if your images contain non-English language.',
						'If you want to translate labels, copy here its ',
						'https://cloud.google.com/translate/docs/languages',
						'The <a href="https://cloud.google.com/translate" target="_new">Google Translate API</a> will then be called automatically to perform the labels translations (you will therefore pay for the Image API and the Translation API).',
						'Leave empty if you do not wish to translate labels.'
					),
					self::FIELD_NAME_PLACEHOLDER           => 'Language code',
					self::FIELD_NAME_DEFAULT_VALUE         => '',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
				],
			],
			//self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_PROPERTY_DEFAULT,
			self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_FACE_DEFAULT,
			self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LANDMARK_DEFAULT,
			self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LOGO_DEFAULT,


		];
	}

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		return [
			self::FIELD_IMAGE_GOOGLE_ANNOTATE_LABEL,
			self::FIELD_IMAGE_GOOGLE_ANNOTATE_LANDMARK,
			self::FIELD_IMAGE_GOOGLE_ANNOTATE_LOGO,
			$this->get_annotation_face_field_anger(),
			$this->get_annotation_face_field_joy(),
			$this->get_annotation_face_field_surprise(),
			$this->get_annotation_face_field_sorrow(),
			self::FIELD_IMAGE_GOOGLE_ANNOTATE_TEXT,
			//self::FIELD_IMAGE_GOOGLE_ANNOTATE_DOMINANT_COLORS,
		];
	}

	/**
	 * @param ImageAnnotatorClient $api_client
	 *
	 * @inheritDoc
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		$features = [];
		foreach (
			static::FEATURE_TYPES_OPTION_MAPPING as $feature_type => $property_label
		) {
			if ( isset( $this->ai_api[ $property_label ] ) ) {
				// Feature is selected
				$features[] = new Feature( [ 'type' => $feature_type, 'max_results' => 100, ] );
			}
		}

		$requests = [];
		foreach ( $args['images'] as $image ) {
			$request = new AnnotateImageRequest();

			if ( isset( $image[ static::$FILE_SEND_AS_URL ] ) ) {
				$image = [ 'source' => new ImageSource( [ 'image_uri' => $image[ static::$FILE_SEND_AS_URL ] ] ) ];
			}
			if ( isset( $image[ static::$FILE_SEND_AS_CONTENT ] ) ) {
				$image = [ 'content' => $image[ static::$FILE_SEND_AS_CONTENT ] ];
			}

			$request->setImage( new Image( $image ) );
			$request->setFeatures( $features );

			$requests[] = $request;
		}

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		return $api_client->batchAnnotateImages( $requests );
	}

	/**
	 * @inheritDoc
	 *
	 * @param BatchAnnotateImagesResponse $raw_service_response
	 *
	 * @return $raw_service_response
	 */
	protected function _decode_api_results( $raw_service_response ) {

		foreach ( $raw_service_response->getResponses() as $response ) {
			/** @var AnnotateImageResponse $response */
			if ( $response && $response->getError() ) {

				throw new \Exception( $response->getError()->getMessage() );
			}
		}

		return $raw_service_response;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ImageAnnotatorClient
	 * @throws \Google\ApiCore\ValidationException
	 */
	protected function _create_api_client() {

		$credentials = json_decode( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY_JSON ], true );

		if ( isset( $this->ai_api[ self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE ] ) &&
		     ! empty( trim( $this->ai_api[ self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE ] ) ) &&
		     ( 'en' !== trim( $this->ai_api[ self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE ] ) ) &&
		     ( isset( $credentials['project_id'] ) )
		) {
			// Instantiate the translate API if the language is set and is not 'en'

			$this->translation_location = TranslationServiceClient::locationName( $credentials['project_id'], 'global' );

			$this->translation_language = trim( $this->ai_api[ self::FIELD_NAME_FIELDS_SERVICE_IMAGE_TYPE_LABEL_TRANSLATE ] );

			$this->translation_api = new TranslationServiceClient( [
				'credentials' => json_decode( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY_JSON ], true ),
			] );

		}

		return new ImageAnnotatorClient( [
			'credentials' => json_decode( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY_JSON ], true ),
		] );
	}

	/**
	 * @param BatchAnnotateImagesResponse $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {

		if ( ! $raw_service_response->getResponses() ) {
			// No response
			return [];
		}

		// Convert indexed to associative array
		$results = [];

		foreach ( $raw_service_response->getResponses() as $response ) {
			/** @var AnnotateImageResponse $response */
			/** @var EntityAnnotation $annotation */

			/**
			 * Labels: https://cloud.google.com/vision/docs/labels
			 */
			if ( empty( $this->translation_language ) ) {
				// No translation
				foreach ( $response->getLabelAnnotations() as $annotation ) {
					if ( $this->_is_above_treshold_score( $annotation->getScore() ) ) {
						$results[] = [
							'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_LABEL,
							'value' => $annotation->getDescription(),
						];
					}
				}

			} else {
				// translate

				$to_translate = [];
				foreach ( $response->getLabelAnnotations() as $annotation ) {
					if ( $this->_is_above_treshold_score( $annotation->getScore() ) ) {
						$to_translate[] = $annotation->getDescription();
					}
				}

				if ( ! empty( $to_translate ) ) {

					$translations = $this->translation_api->translateText( $to_translate, $this->translation_language, $this->translation_location );
					foreach ( $translations->getTranslations() as $translation ) {
						$results[] = [
							'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_LABEL,
							'value' => $translation->getTranslatedText(),
						];
					}
				}

			}

			/**
			 * Landmarks: https://cloud.google.com/vision/docs/detecting-landmarks
			 */
			foreach ( $response->getLandmarkAnnotations() as $annotation ) {
				if ( $this->_is_above_treshold_score( $annotation->getScore() ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_LANDMARK,
						'value' => $annotation->getDescription(),
					];
				}
			}

			/**
			 * Logos: https://cloud.google.com/vision/docs/detecting-logos
			 */
			foreach ( $response->getLogoAnnotations() as $annotation ) {
				if ( $this->_is_above_treshold_score( $annotation->getScore() ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_LOGO,
						'value' => $annotation->getDescription(),
					];
				}
			}

			/**
			 * Faces: https://cloud.google.com/vision/docs/detecting-faces
			 */
			foreach ( $response->getFaceAnnotations() as $annotation ) {
				/** @var FaceAnnotation $annotation */
				if ( $this->_is_above_treshold_score( $annotation->getDetectionConfidence() ) ) {
					$results[] = [
						'type'  => $this->get_annotation_face_field_anger(),
						'value' => self::LIKELIHOOD[ $annotation->getAngerLikelihood() ],
					];
					$results[] = [
						'type'  => $this->get_annotation_face_field_joy(),
						'value' => self::LIKELIHOOD[ $annotation->getJoyLikelihood() ],
					];
					$results[] = [
						'type'  => $this->get_annotation_face_field_surprise(),
						'value' => self::LIKELIHOOD[ $annotation->getSurpriseLikelihood() ],
					];
					$results[] = [
						'type'  => $this->get_annotation_face_field_sorrow(),
						'value' => self::LIKELIHOOD[ $annotation->getSorrowLikelihood() ],
					];
				}
			}

			/**
			 * Texts: https://cloud.google.com/vision/docs/ocr
			 */
			$annotation = $response->getFullTextAnnotation();
			if ( $annotation ) {
				$text_pages = '';
				foreach ( $annotation->getPages() as $page ) {
					$text_blocks = '';
					foreach ( $page->getBlocks() as $block ) {
						$text_paragraphs = '';
						foreach ( $block->getParagraphs() as $paragraph ) {
							$text_paragraph = '';
							foreach ( $paragraph->getWords() as $word ) {
								foreach ( $word->getSymbols() as $symbol ) {

									if ( $this->_is_above_treshold_score( $symbol->getConfidence() ) ) {
										$text_paragraph .= $symbol->getText();
									}

								}

								if ( ! empty( $text_paragraph ) ) {
									$text_paragraph .= ' ';
								}
							}

							if ( ! empty( $text_paragraph ) ) {
								$text_paragraphs .= sprintf( '<div class="wpsolr_annotation_paragraph">%s</div>', $text_paragraph );
							}
						}

						if ( ! empty( $text_paragraphs ) ) {
							$text_blocks .= sprintf( '<div class="wpsolr_annotation_block">%s</div>', $text_paragraphs );
						}
					}
					if ( ! empty( $text_blocks ) ) {
						$text_pages .= sprintf( '<div class="wpsolr_annotation_page">%s</div>', $text_blocks );
					}
				}

				if ( ! empty( $text_pages ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_TEXT,
						'value' => $text_pages,
					];
				}

			}

			/**
			 * Colors: https://cloud.google.com/vision/docs/detecting-properties
			 */
			if ( $annotations = $response->getImagePropertiesAnnotation() ) {
				$values = [];
				foreach ( $annotations->getDominantColors()->getColors() as $color ) {
					/** @var  ColorInfo $color */

					if ( $this->_is_above_treshold_score( $color->getScore() ) ) {

						$hsl = WPSOLR_AI_Color_Helper::rgb_to_hsl(
							$color->getColor()->getRed(),
							$color->getColor()->getGreen(),
							$color->getColor()->getBlue()
						);


						if ( $hsl && ( 3 === count( $hsl ) ) ) {
							$values[] = [
								'fraction' => $color->getPixelFraction(), // 0-1
								'h'        => $hsl[0], // 0-360
								's'        => $hsl[1], // 0-1
								'l'        => $hsl[2], // 0-1
							];
						}
					}
				}

				if ( ! empty( $values ) ) {
					$results[] = [
						'type'  => self::FIELD_IMAGE_GOOGLE_ANNOTATE_DOMINANT_COLORS,
						'value' => $values,
					];
				}

			}
		}

		return $this->_group_api_entities_by_type( $results, 'type', 'value' );;
	}

	/**
	 * @return string
	 */
	private function get_annotation_face_field_anger() {
		return $this->get_annotation_face_field( 'anger' );
	}

	/**
	 * @return string
	 */
	private function get_annotation_face_field_joy() {
		return $this->get_annotation_face_field( 'joy' );
	}

	/**
	 * @return string
	 */
	private function get_annotation_face_field_surprise() {
		return $this->get_annotation_face_field( 'surprise' );
	}

	/**
	 * @return string
	 */
	private function get_annotation_face_field_sorrow() {
		return $this->get_annotation_face_field( 'sorrow' );
	}

	/**
	 * @param string $likelihood
	 *
	 * @return string
	 */
	private function get_annotation_face_field( $likelihood ) {
		return sprintf( '%s_%s', self::FIELD_IMAGE_GOOGLE_ANNOTATE_FACE, $likelihood );
	}

}

<?php

namespace wpsolr\core\classes\ai_api\meaningcloud;

use MeaningCloud\MCRequest;
use MeaningCloud\MCTopicsRequest;
use MeaningCloud\MCTopicsResponse;
use wpsolr\core\classes\ai_api\WPSOLR_AI_Text_Api_Abstract;
use wpsolr\core\classes\WpSolrSchema;

class WPSOLR_AI_Text_Api_Meaningcloud_Entity extends WPSOLR_AI_Text_Api_Abstract {

	const API_ID = 'text_meaningcloud_entity';

	/**
	 * @inheritDoc
	 */
	public function get_is_disabled() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_label() {
		return static::TEXT_SERVICE_EXTRACTION_ENTITY['label'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_url() {
		return 'https://www.meaningcloud.com/developer/topics-extraction/doc/2.0/what-is-topics-extraction';
	}

	/**
	 * @inheritDoc
	 */
	public function get_documentation_url() {
		return 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/activate-extensions/extension-nlp/meaningcloud/';
	}

	/**
	 * @return string
	 */
	public function get_documentation_text() {
		return <<<'TAG'
Topics Extraction is MeaningCloud's solution for extracting the different elements present in sources of information. 
This detection process is carried out by combining a number of complex natural language processing techniques that allow to obtain morphological, syntactic and semantic analyses of a text and use them to identify different types of significant elements.
TAG;
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		return static::TEXT_PROVIDER_MEANINGCLOUD;
	}

	/**
	 * @inheritdoc
	 */
	public function get_ui_fields_child() {

		return [
			[
				self::FIELD_NAME_FIELDS_SERVICE_KEY => [
					self::FIELD_NAME_LABEL                 => 'License Key',
					self::FIELD_NAME_PLACEHOLDER           => 'License Keyfound in your MeaningCloud developer account',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your License Key',
					],
				],
			],
			[
				self::FIELD_NAME_FIELDS_SERVICE_LANGUAGE => [
					self::FIELD_NAME_LABEL                 => 'Language',
					self::FIELD_NAME_INSTRUCTION           => sprintf( 'Possible  <a href="%s" target="_new">values</a>: %s',
						'https://www.meaningcloud.com/developer/topics-extraction/doc/2.0/request',
						<<<'TAG'
en: English
es: Spanish
it: Italian
fr: French
pt: Portuguese
ca: Catalan
da: Danish
sv: Swedish
no: Norwegian
fi: Finnish
zh: Chinese
ru: Russian
TAG
					),
					self::FIELD_NAME_DEFAULT_VALUE         => 'en',
					self::FIELD_NAME_PLACEHOLDER           => 'The language of your documents',
					self::FIELD_NAME_FORMAT_IS_CREATE_ONLY => false,
					self::FIELD_NAME_FORMAT                => [
						self::FIELD_NAME_FORMAT_TYPE        => self::FIELD_NAME_FORMAT_TYPE_MANDATORY,
						self::FIELD_NAME_FORMAT_ERROR_LABEL => self::PLEASE_ENTER . 'your language',
					],
				],
			],
		];

	}

	/**
	 * @inheritDoc
	 */
	protected function _get_extracted_fields_child() {
		// https://www.meaningcloud.com/developer/documentation/ontology
		// Ontologies extracted with script ./extract_all_ontology.js
		return [
			'Top>Person>FirstName',
			'Top>Person>FullName',
			'Top>Person>LastName',
			'Top>Event>NaturalDisaster',
			'Top>Event>NaturalPhenomena',
			'Top>Event>Occasion>Conference',
			'Top>Event>Occasion>Games',
			'Top>Event>War',
			'Top>Id>Ticker',
			'Top>Id>Email',
			'Top>Id>Hashtag',
			'Top>Id>IdNumber',
			'Top>Id>Nickname',
			'Top>Id>PhoneNumber',
			'Top>Id>PostalCode',
			'Top>Id>Url',
			'Top>LivingThing>Animal>Invertebrate>Insect',
			'Top>LivingThing>Animal>Vertebrate>Amphibian',
			'Top>LivingThing>Animal>Vertebrate>Bird',
			'Top>LivingThing>Animal>Vertebrate>Fish',
			'Top>LivingThing>Animal>Vertebrate>Mammal',
			'Top>LivingThing>Animal>Vertebrate>Reptile',
			'Top>LivingThing>BodyPart',
			'Top>LivingThing>Flora>FloraPart',
			'Top>Location>Address',
			'Top>Location>AstralBody>Planet',
			'Top>Location>AstralBody>Star',
			'Top>Location>Facility>AmusementPark',
			'Top>Location>Facility>ArcheologicalPlace',
			'Top>Location>Facility>FacilityPart',
			'Top>Location>Facility>Line>Railroad',
			'Top>Location>Facility>Line>Road',
			'Top>Location>Facility>Line>Tunnel',
			'Top>Location>Facility>Market',
			'Top>Location>Facility>Monument',
			'Top>Location>Facility>Park',
			'Top>Location>Facility>Prison',
			'Top>Location>Facility>SportsFacility',
			'Top>Location>Facility>StationTop>Airport',
			'Top>Location>Facility>StationTop>Port',
			'Top>Location>Facility>StationTop>Station',
			'Top>Location>Facility>Theatre',
			'Top>Location>Facility>WorshipPlace',
			'Top>Location>GeographicalEntity>LandForm>Basin',
			'Top>Location>GeographicalEntity>LandForm>Beach',
			'Top>Location>GeographicalEntity>LandForm>Cape',
			'Top>Location>GeographicalEntity>LandForm>Desert',
			'Top>Location>GeographicalEntity>LandForm>Forest',
			'Top>Location>GeographicalEntity>LandForm>Isle',
			'Top>Location>GeographicalEntity>LandForm>Mountain',
			'Top>Location>GeographicalEntity>LandForm>Valley',
			'Top>Location>GeographicalEntity>LandForm>Volcano',
			'Top>Location>GeographicalEntity>NaturalReserve',
			'Top>Location>GeographicalEntity>WaterForm>Channel',
			'Top>Location>GeographicalEntity>WaterForm>Gulf',
			'Top>Location>GeographicalEntity>WaterForm>Lake',
			'Top>Location>GeographicalEntity>WaterForm>Ocean',
			'Top>Location>GeographicalEntity>WaterForm>River',
			'Top>Location>GeographicalEntity>WaterForm>Sea',
			'Top>Location>GeoPoliticalEntity>Adm1',
			'Top>Location>GeoPoliticalEntity>Adm2',
			'Top>Location>GeoPoliticalEntity>Adm3',
			'Top>Location>GeoPoliticalEntity>City',
			'Top>Location>GeoPoliticalEntity>Continent',
			'Top>Location>GeoPoliticalEntity>Country',
			'Top>Location>GeoPoliticalEntity>District',
			'Top>Numex>Age',
			'Top>Numex>Calorie',
			'Top>Numex>Intensity',
			'Top>Numex>Money',
			'Top>Numex>Percent',
			'Top>Numex>PhysicalExtent',
			'Top>Numex>Space',
			'Top>Numex>Speed',
			'Top>Numex>Temperature',
			'Top>Numex>Volume',
			'Top>Numex>Weight',
			'Top>Organization>ArtisticOrganization>Museum',
			'Top>Organization>ArtisticOrganization>MusicGroup',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>AutomobileCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerDurablesCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerServicesCompany>Casinos',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerServicesCompany>Entertainment',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerServicesCompany>Hotels',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerServicesCompany>MediaCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>ConsumerServicesCompany>Restaurants',
			'Top>Organization>Company>ConsumerGoodsCompany>Discretionary>RetailingCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Staples>FoodCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Staples>FoodDrugRetailing',
			'Top>Organization>Company>ConsumerGoodsCompany>Staples>HouseholdProductsCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Staples>PersonalProductsCompany',
			'Top>Organization>Company>ConsumerGoodsCompany>Staples>PersonalServicesCompany',
			'Top>Organization>Company>EnergyCompany',
			'Top>Organization>Company>FinancialCompany>BankingCompany>BankingServices>Bank',
			'Top>Organization>Company>FinancialCompany>BankingCompany>DiversifiedFinancialsServices',
			'Top>Organization>Company>FinancialCompany>BankingCompany>InvestmentServices',
			'Top>Organization>Company>FinancialCompany>Insurance',
			'Top>Organization>Company>FinancialCompany>InvestmentTrust',
			'Top>Organization>Company>FinancialCompany>RealEstate',
			'Top>Organization>Company>HealthcareCompany>HealthcareEquipmentCompany',
			'Top>Organization>Company>HealthcareCompany>HealthcareServicesCompany>Hospitals',
			'Top>Organization>Company>HealthcareCompany>PharmaCompany>BiotechCompany',
			'Top>Organization>Company>HealthcareCompany>PharmaCompany>Pharmaceutical',
			'Top>Organization>Company>IndustrialCompany>IndustrialGoods>AerospaceDefense',
			'Top>Organization>Company>IndustrialCompany>IndustrialGoods>EquipmentCompany',
			'Top>Organization>Company>IndustrialCompany>IndustrialServicesCompany>CommercialServices',
			'Top>Organization>Company>IndustrialCompany>IndustrialServicesCompany>ConstructionServices',
			'Top>Organization>Company>IndustrialCompany>IndustrialServicesCompany>DistributionServices',
			'Top>Organization>Company>IndustrialCompany>TransportationCompany',
			'Top>Organization>Company>MaterialsCompany>AppliedResources',
			'Top>Organization>Company>MaterialsCompany>ChemicalCompany',
			'Top>Organization>Company>MaterialsCompany>MineralResources',
			'Top>Organization>Company>TechnologyCompany>SoftwareCompany',
			'Top>Organization>Company>TechnologyCompany>TechnologyEquipmentCompany>Communication',
			'Top>Organization>Company>TechnologyCompany>TechnologyEquipmentCompany>Hardware',
			'Top>Organization>Company>TechnologyCompany>TechnologyEquipmentCompany>Semiconductors',
			'Top>Organization>Company>TelcoServicesCompany',
			'Top>Organization>Company>UtilitiesCompany>ElectricUtilities',
			'Top>Organization>Company>UtilitiesCompany>MultilineUtilities',
			'Top>Organization>Company>UtilitiesCompany>NaturalGasUtilities',
			'Top>Organization>Company>UtilitiesCompany>WaterUtilities',
			'Top>Organization>EducationalOrganization>School',
			'Top>Organization>EducationalOrganization>University',
			'Top>Organization>Government',
			'Top>Organization>Institute>CulturalInstitute',
			'Top>Organization>Institute>EnterpriseAssociation',
			'Top>Organization>Institute>LaborUnion',
			'Top>Organization>Institute>Ngo',
			'Top>Organization>Institute>ProfessionalAssociation',
			'Top>Organization>InternationalOrganization',
			'Top>Organization>Military>Army',
			'Top>Organization>Military>Police',
			'Top>Organization>PoliticalParty',
			'Top>Organization>PublicInstitution',
			'Top>Organization>ReligiousOrganization',
			'Top>Organization>SportsOrganization>SportsLeague',
			'Top>Organization>SportsOrganization>SportsTeam',
			'Top>Organization>StockMarket',
			'Top>Organization>TerroristOrganization',
			'Top>OtherEntity>Award',
			'Top>OtherEntity>Class',
			'Top>OtherEntity>Color',
			'Top>OtherEntity>Disease',
			'Top>OtherEntity>Doctrine>Academic',
			'Top>OtherEntity>Doctrine>CultureEntity',
			'Top>OtherEntity>Doctrine>GroupTendency',
			'Top>OtherEntity>Doctrine>Movement',
			'Top>OtherEntity>Doctrine>PersonTendency',
			'Top>OtherEntity>Doctrine>Plan',
			'Top>OtherEntity>Doctrine>ReligionEntity',
			'Top>OtherEntity>Doctrine>Sports',
			'Top>OtherEntity>Doctrine>Style',
			'Top>OtherEntity>Doctrine>Theory',
			'Top>OtherEntity>EthnicGroup',
			'Top>OtherEntity>Gentilic',
			'Top>OtherEntity>God',
			'Top>OtherEntity>IndexName>EconomicIndexName>StockIndexName',
			'Top>OtherEntity>Language',
			'Top>OtherEntity>MethodSystem',
			'Top>OtherEntity>Nationality',
			'Top>OtherEntity>Offence',
			'Top>OtherEntity>Rule>Contract',
			'Top>OtherEntity>Rule>LawRule',
			'Top>OtherEntity>Rule>Treaty',
			'Top>OtherEntity>Title',
			'Top>OtherEntity>Vocation',
			'Top>Process>CausingHappiness',
			'Top>Process>CausingUnhappiness',
			'Top>Process>ContentBearingProcess>CommunicationProcess>Disseminating',
			'Top>Process>ContentBearingProcess>CommunicationProcess>Expressing',
			'Top>Process>ContentBearingProcess>CommunicationProcess>Gesture',
			'Top>Process>ContentBearingProcess>CommunicationProcess>Indicating',
			'Top>Process>ContentBearingProcess>CommunicationProcess>LinguisticCommunication',
			'Top>Process>ContentBearingProcess>CommunicationProcess>Telephoning',
			'Top>Process>DualObjectProcess>Attaching',
			'Top>Process>DualObjectProcess>Combining',
			'Top>Process>DualObjectProcess>Comparing',
			'Top>Process>DualObjectProcess>Detaching',
			'Top>Process>DualObjectProcess>Separating',
			'Top>Process>DualObjectProcess>Substituting',
			'Top>Process>DualObjectProcess>Transaction',
			'Top>Process>IntentionalProcess>Commenting',
			'Top>Process>IntentionalProcess>ContentDevelopment>ArtPainting',
			'Top>Process>IntentionalProcess>ContentDevelopment>ComposingMusic',
			'Top>Process>IntentionalProcess>ContentDevelopment>ComputerProgramming',
			'Top>Process>IntentionalProcess>ContentDevelopment>Drawing',
			'Top>Process>IntentionalProcess>ContentDevelopment>FilmMaking',
			'Top>Process>IntentionalProcess>ContentDevelopment>Photographing',
			'Top>Process>IntentionalProcess>ContentDevelopment>Publication',
			'Top>Process>IntentionalProcess>ContentDevelopment>Reading',
			'Top>Process>IntentionalProcess>ContentDevelopment>Tracing',
			'Top>Process>IntentionalProcess>ContentDevelopment>Translating',
			'Top>Process>IntentionalProcess>ContentDevelopment>Writing',
			'Top>Process>IntentionalProcess>CriminalAction',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Calculating',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Classifying',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Designing',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Discovering',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Interpreting',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Investigating',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Learning',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Planning',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Predicting',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Reasoning',
			'Top>Process>IntentionalProcess>IntentionalPsychologicalProcess>Selecting',
			'Top>Process>IntentionalProcess>Keeping',
			'Top>Process>IntentionalProcess>Listening',
			'Top>Process>IntentionalProcess>Looking',
			'Top>Process>IntentionalProcess>Maintaining',
			'Top>Process>IntentionalProcess>Making',
			'Top>Process>IntentionalProcess>Maneuver',
			'Top>Process>IntentionalProcess>OrganizationalProcess>BeginningOperations',
			'Top>Process>IntentionalProcess>OrganizationalProcess>CeasingOperations',
			'Top>Process>IntentionalProcess>OrganizationalProcess>Election',
			'Top>Process>IntentionalProcess>OrganizationalProcess>Founding',
			'Top>Process>IntentionalProcess>OrganizationalProcess>JoiningAnOrganization',
			'Top>Process>IntentionalProcess>OrganizationalProcess>LeavingAnOrganization',
			'Top>Process>IntentionalProcess>OrganizationalProcess>Managing',
			'Top>Process>IntentionalProcess>OrganizationalProcess>OrganizationalMerging',
			'Top>Process>IntentionalProcess>Pursuing',
			'Top>Process>IntentionalProcess>RecreationOrExercise>Gaming',
			'Top>Process>IntentionalProcess>RecreationOrExercise>Smoking',
			'Top>Process>IntentionalProcess>RecreationOrExercise>SocialParty',
			'Top>Process>IntentionalProcess>RecreationOrExercise>Vacationing',
			'Top>Process>IntentionalProcess>Repairing',
			'Top>Process>IntentionalProcess>SocialInteraction>Ceremony',
			'Top>Process>IntentionalProcess>SocialInteraction>ChangeOfPossession',
			'Top>Process>IntentionalProcess>SocialInteraction>Contest',
			'Top>Process>IntentionalProcess>SocialInteraction>Cooperation',
			'Top>Process>IntentionalProcess>SocialInteraction>Meeting',
			'Top>Process>IntentionalProcess>Trip',
			'Top>Process>InternalChange>BiologicalProcess',
			'Top>Process>InternalChange>ChemicalProcess',
			'Top>Process>InternalChange>Creation',
			'Top>Process>InternalChange>Damaging',
			'Top>Process>InternalChange>GeologicalProcess',
			'Top>Process>InternalChange>QuantityChange>Decreasing',
			'Top>Process>InternalChange>QuantityChange>Focusing',
			'Top>Process>InternalChange>QuantityChange>Increasing',
			'Top>Process>InternalChange>ShapeChange',
			'Top>Process>InternalChange>StateChange',
			'Top>Process>InternalChange>SurfaceChange',
			'Top>Process>InternalChange>TidalProcess',
			'Top>Process>InternalChange>TurningOffDevice',
			'Top>Process>InternalChange>TurningOnDevice',
			'Top>Process>Motion>BodyMotion',
			'Top>Process>Motion>Closing',
			'Top>Process>Motion>DirectionChange',
			'Top>Process>Motion>GasMotion',
			'Top>Process>Motion>Irrigating',
			'Top>Process>Motion>LiquidMotion',
			'Top>Process>Motion>MotionDownward',
			'Top>Process>Motion>MotionUpward',
			'Top>Process>Motion>Opening',
			'Top>Process>Motion>Pulling',
			'Top>Process>Motion>Radiating',
			'Top>Process>Motion>Reversing',
			'Top>Process>Motion>Rotating',
			'Top>Process>Motion>Stretching',
			'Top>Process>Motion>Swarming',
			'Top>Process>Motion>Translocation>Accelerating',
			'Top>Process>Motion>Translocation>Ambulating',
			'Top>Process>Motion>Translocation>Arriving',
			'Top>Process>Motion>Translocation>Boarding',
			'Top>Process>Motion>Translocation>Deboarding',
			'Top>Process>Motion>Translocation>Decelerating',
			'Top>Process>Motion>Translocation>Escaping',
			'Top>Process>Motion>Translocation>Falling',
			'Top>Process>Motion>Translocation>Flying',
			'Top>Process>Motion>Translocation>Inmigrating',
			'Top>Process>Motion>Translocation>Landing',
			'Top>Process>Motion>Translocation>Leaving',
			'Top>Process>Motion>Translocation>Returning',
			'Top>Process>Motion>Translocation>TakingOff',
			'Top>Process>Motion>Translocation>Transfer',
			'Top>Process>Motion>Translocation>Transportation',
			'Top>Process>Motion>Translocation>Trespassing',
			'Top>Process>NaturalProcess',
			'Top>Process>WeatherProcess',
			'Top>Product>Cosmetic',
			'Top>Product>CulturalProduct>Broadcast',
			'Top>Product>CulturalProduct>Composition',
			'Top>Product>CulturalProduct>Dance',
			'Top>Product>CulturalProduct>Game',
			'Top>Product>CulturalProduct>Movie',
			'Top>Product>CulturalProduct>MusicalProduct',
			'Top>Product>CulturalProduct>Picture',
			'Top>Product>CulturalProduct>Printing>Book',
			'Top>Product>CulturalProduct>Printing>Document',
			'Top>Product>CulturalProduct>Printing>Magazine',
			'Top>Product>CulturalProduct>Printing>Newspaper',
			'Top>Product>CulturalProduct>Show',
			'Top>Product>Food>Beverage',
			'Top>Product>Food>CookedPlate',
			'Top>Product>Food>DairyProduct',
			'Top>Product>Food>Fishfood',
			'Top>Product>Food>FruitOrVegetable',
			'Top>Product>Food>Legume',
			'Top>Product>Food>Meat',
			'Top>Product>Food>OilOrGrease',
			'Top>Product>Food>Tobacco',
			'Top>Product>Furniture',
			'Top>Product>Machine>ElectricalAppliance',
			'Top>Product>Machine>ElectronicAppliance>Computer',
			'Top>Product>Machine>ElectronicAppliance>ElectronicApplianceOther',
			'Top>Product>Machine>ElectronicAppliance>ElectronicDevice',
			'Top>Product>Machine>ElectronicAppliance>MobilePhone',
			'Top>Product>Machine>Instrument',
			'Top>Product>Machine>PrecisionInstrument',
			'Top>Product>Machine>Vehicle>Aircraft',
			'Top>Product>Machine>Vehicle>Car',
			'Top>Product>Machine>Vehicle>Ship',
			'Top>Product>Machine>Vehicle>Spaceship',
			'Top>Product>Machine>Vehicle>Train',
			'Top>Product>Machine>Weapon',
			'Top>Product>Part',
			'Top>Product>ProfessionalService>AssistanceService',
			'Top>Product>ProfessionalService>CourierService',
			'Top>Product>ProfessionalService>EducationalService',
			'Top>Product>ProfessionalService>EnquiryService',
			'Top>Product>ProfessionalService>EnvironmentalService',
			'Top>Product>ProfessionalService>FinancialService',
			'Top>Product>ProfessionalService>FoodOrBeverageService',
			'Top>Product>ProfessionalService>LegalService',
			'Top>Product>ProfessionalService>LeisureService',
			'Top>Product>ProfessionalService>LodgingService',
			'Top>Product>ProfessionalService>PassengerService',
			'Top>Product>ProfessionalService>RealEstateService',
			'Top>Product>ProfessionalService>RentingService',
			'Top>Product>ProfessionalService>SocialService',
			'Top>Product>ProfessionalService>TelecommunicationsService',
			'Top>Product>Substance>ChemicalCompound',
			'Top>Product>Substance>ChemicalElement',
			'Top>Product>Substance>Drug',
			'Top>Product>Substance>Fuel',
			'Top>Product>Substance>Metal',
			'Top>Product>Substance>Mineral',
			'Top>Product>Textile>Accessory',
			'Top>Product>Textile>Clothes',
			'Top>Product>Textile>Fabric',
			'Top>Product>Textile>Footwear',
			'Top>Product>Toy',
			'Top>Product>Utensil>Container',
			'Top>Product>Utensil>UtensilOther',
			'Top>Product>WasteProduct',
			'Top>Timex>Date',
			'Top>Timex>Period',
			'Top>Timex>Time',
			'Top>Unit>CalorieUnit',
			'Top>Unit>Currency',
			'Top>Unit>IntensityUnit',
			'Top>Unit>PhysicalExtentUnit',
			'Top>Unit>SpaceUnit',
			'Top>Unit>SpeedUnit',
			'Top>Unit>TemperatureUnit',
			'Top>Unit>TimeUnit>Day',
			'Top>Unit>TimeUnit>Era',
			'Top>Unit>TimeUnit>Month',
			'Top>Unit>TimeUnit>Season',
			'Top>Unit>VolumeUnit',
			'Top>Unit>WeightUnit',
			'Top>Arts>Architecture',
			'Top>Arts>Cinema',
			'Top>Arts>Music',
			'Top>Arts>Painting',
			'Top>Arts>Photography',
			'Top>Arts>Sculpture',
			'Top>BasicSciences>Chemistry',
			'Top>BasicSciences>Geometry',
			'Top>BasicSciences>Mathematics',
			'Top>BasicSciences>Physics',
			'Top>Humanities>History',
			'Top>Humanities>Linguistics',
			'Top>Humanities>Literature',
			'Top>Humanities>Mythology',
			'Top>Humanities>Philosophy',
			'Top>Humanities>Prehistory',
			'Top>LifeSciences>Anatomy',
			'Top>LifeSciences>Biology',
			'Top>LifeSciences>Medicine',
			'Top>LifeSciences>Pharmacology',
			'Top>NaturalSciences>Astronomy',
			'Top>NaturalSciences>Botany',
			'Top>NaturalSciences>Ecology',
			'Top>NaturalSciences>Geology',
			'Top>NaturalSciences>Meteorology',
			'Top>NaturalSciences>Zoology',
			'Top>SocialSciences>Anthropology',
			'Top>SocialSciences>Economy',
			'Top>SocialSciences>Geography',
			'Top>SocialSciences>Law',
			'Top>SocialSciences>Psychology',
			'Top>SocialSciences>Sociology',
			'Top>Society>Astrology',
			'Top>Society>Bulls',
			'Top>Society>Culture',
			'Top>Society>Education',
			'Top>Society>Fashion',
			'Top>Society>Gastronomy',
			'Top>Society>HealthFood',
			'Top>Society>Leisure',
			'Top>Society>Media',
			'Top>Society>Military',
			'Top>Society>Politics',
			'Top>Society>Religion',
			'Top>Society>SecurityPolice',
			'Top>Society>SocialProtection',
			'Top>Society>StatisticsPolls',
			'Top>Society>Tourism',
			'Top>Society>Transport',
			'Top>Sport>Basketball',
			'Top>Sport>Cycling',
			'Top>Sport>Football',
			'Top>Sport>Golf',
			'Top>Sport>Handball',
			'Top>Sport>Motor',
			'Top>Sport>Tennis',
			'Top>Technology>Aeronautics',
			'Top>Technology>Agriculture',
			'Top>Technology>Audiovisual',
			'Top>Technology>Computing',
			'Top>Technology>Construction',
			'Top>Technology>Electricity',
			'Top>Technology>Electronics',
			'Top>Technology>Industry',
			'Top>Technology>Internet',
			'Top>Technology>Metalurgy',
			'Top>Technology>Nautical',
			'Top>Technology>Telecommunication',
		];
	}

	/**
	 * @param MCRequest $api_client
	 *
	 * @return MCTopicsResponse
	 *
	 * @inheritDoc
	 */
	protected function _call_api( $option_ai_apis_nb_calls, $api_client, $document_for_update, $args = [] ) {

		$api_client = new MCTopicsRequest(
			$this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_KEY ],
			trim( $this->ai_api[ static::FIELD_NAME_FIELDS_SERVICE_LANGUAGE ] ),
			$document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] );

		// https://www.meaningcloud.com/developer/topics-extraction/doc/2.0/request
		$api_client->addParam( 'tt', 'e' ); // Ony entities

		$api_client->setContentTxt( $document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] );

		// Update stats
		$this->_increment_nb_api_calls( $option_ai_apis_nb_calls );

		return $api_client->sendTopicsRequest();
	}

	/**
	 * @inheritDoc
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function _create_api_client() {
		return null;
	}

	/**
	 * @inheritDoc
	 *
	 * @param MCTopicsResponse $raw_service_response
	 *
	 * @return MCTopicsResponse
	 */
	protected function _decode_api_results( $raw_service_response ) {

		if ( ! $raw_service_response->isSuccessful() ) {

			throw new \Exception( $raw_service_response->getStatusMsg() );
		}


		return $raw_service_response;
	}

	/**
	 * @param MCTopicsResponse $raw_service_response
	 *
	 * @return array
	 */
	protected function _convert_api_results( $raw_service_response ) {
		return $this->_group_api_entities_by_type( $raw_service_response->getEntities(),
			[
				'sementity',
				'type',
			], 'form' );
	}

	/**
	 * @param array $annotation
	 *
	 * @inheridoc
	 */
	protected function _get_api_results_score( $annotation ) {
		// relevance is in [0-100]. Expected in [0-1].
		return ( floatval( $annotation['relevance'] ) / 100 );
	}

}

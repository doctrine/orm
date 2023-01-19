<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\DebugUnitOfWorkListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Tests\DbalExtensions\QueryLog;
use Doctrine\Tests\DbalTypes\Rot13Type;
use Doctrine\Tests\EventListener\CacheMetadataListener;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\Warning;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Throwable;

use function array_map;
use function array_pop;
use function array_reverse;
use function array_slice;
use function assert;
use function explode;
use function get_debug_type;
use function getenv;
use function implode;
use function is_object;
use function method_exists;
use function realpath;
use function sprintf;
use function str_contains;
use function strtolower;
use function var_export;

use const PHP_EOL;

/**
 * Base testcase class for all functional ORM testcases.
 */
abstract class OrmFunctionalTestCase extends OrmTestCase
{
    /**
     * The metadata cache shared between all functional tests.
     *
     * @var CacheItemPoolInterface|null
     */
    private static $metadataCache = null;

    /**
     * The query cache shared between all functional tests.
     *
     * @var CacheItemPoolInterface|null
     */
    private static $queryCache = null;

    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var DbalExtensions\Connection|null
     */
    protected static $sharedConn;

    /** @var EntityManagerInterface */
    protected $_em;

    /** @var SchemaTool */
    protected $_schemaTool;

    /**
     * The names of the model sets used in this testcase.
     *
     * @var array
     */
    protected $_usedModelSets = [];

    /**
     * To be configured by the test that uses result set cache
     *
     * @var CacheItemPoolInterface|null
     */
    protected $resultCache;

    /**
     * Whether the database schema has already been created.
     *
     * @var array
     */
    protected static $tablesCreated = [];

    /**
     * Array of entity class name to their tables that were created.
     *
     * @var array
     */
    protected static $_entityTablesCreated = [];

    /**
     * List of model sets and their classes.
     *
     * @var array
     */
    protected static $modelSets = [
        'cms' => [
            Models\CMS\CmsUser::class,
            Models\CMS\CmsPhonenumber::class,
            Models\CMS\CmsAddress::class,
            Models\CMS\CmsEmail::class,
            Models\CMS\CmsGroup::class,
            Models\CMS\CmsTag::class,
            Models\CMS\CmsArticle::class,
            Models\CMS\CmsComment::class,
        ],
        'company' => [
            Models\Company\CompanyPerson::class,
            Models\Company\CompanyEmployee::class,
            Models\Company\CompanyManager::class,
            Models\Company\CompanyOrganization::class,
            Models\Company\CompanyEvent::class,
            Models\Company\CompanyAuction::class,
            Models\Company\CompanyRaffle::class,
            Models\Company\CompanyCar::class,
            Models\Company\CompanyContract::class,
        ],
        'ecommerce' => [
            Models\ECommerce\ECommerceCart::class,
            Models\ECommerce\ECommerceCustomer::class,
            Models\ECommerce\ECommerceProduct::class,
            Models\ECommerce\ECommerceShipping::class,
            Models\ECommerce\ECommerceFeature::class,
            Models\ECommerce\ECommerceCategory::class,
        ],
        'generic' => [
            Models\Generic\BooleanModel::class,
            Models\Generic\DateTimeModel::class,
            Models\Generic\DecimalModel::class,
        ],
        'routing' => [
            Models\Routing\RoutingLeg::class,
            Models\Routing\RoutingLocation::class,
            Models\Routing\RoutingRoute::class,
            Models\Routing\RoutingRouteBooking::class,
        ],
        'navigation' => [
            Models\Navigation\NavUser::class,
            Models\Navigation\NavCountry::class,
            Models\Navigation\NavPhotos::class,
            Models\Navigation\NavTour::class,
            Models\Navigation\NavPointOfInterest::class,
        ],
        'directorytree' => [
            Models\DirectoryTree\AbstractContentItem::class,
            Models\DirectoryTree\File::class,
            Models\DirectoryTree\Directory::class,
        ],
        'ddc117' => [
            Models\DDC117\DDC117Article::class,
            Models\DDC117\DDC117Reference::class,
            Models\DDC117\DDC117Translation::class,
            Models\DDC117\DDC117ArticleDetails::class,
            Models\DDC117\DDC117ApproveChanges::class,
            Models\DDC117\DDC117Editor::class,
            Models\DDC117\DDC117Link::class,
        ],
        'ddc3699' => [
            Models\DDC3699\DDC3699Parent::class,
            Models\DDC3699\DDC3699RelationOne::class,
            Models\DDC3699\DDC3699RelationMany::class,
            Models\DDC3699\DDC3699Child::class,
        ],
        'stockexchange' => [
            Models\StockExchange\Bond::class,
            Models\StockExchange\Stock::class,
            Models\StockExchange\Market::class,
        ],
        'legacy' => [
            Models\Legacy\LegacyUser::class,
            Models\Legacy\LegacyUserReference::class,
            Models\Legacy\LegacyArticle::class,
            Models\Legacy\LegacyCar::class,
        ],
        'customtype' => [
            Models\CustomType\CustomTypeChild::class,
            Models\CustomType\CustomTypeParent::class,
            Models\CustomType\CustomTypeUpperCase::class,
        ],
        'compositekeyinheritance' => [
            Models\CompositeKeyInheritance\JoinedRootClass::class,
            Models\CompositeKeyInheritance\JoinedChildClass::class,
            Models\CompositeKeyInheritance\SingleRootClass::class,
            Models\CompositeKeyInheritance\SingleChildClass::class,
        ],
        'taxi' => [
            Models\Taxi\PaidRide::class,
            Models\Taxi\Ride::class,
            Models\Taxi\Car::class,
            Models\Taxi\Driver::class,
        ],
        'cache' => [
            Models\Cache\Country::class,
            Models\Cache\State::class,
            Models\Cache\City::class,
            Models\Cache\Traveler::class,
            Models\Cache\TravelerProfileInfo::class,
            Models\Cache\TravelerProfile::class,
            Models\Cache\Travel::class,
            Models\Cache\Attraction::class,
            Models\Cache\Restaurant::class,
            Models\Cache\Beach::class,
            Models\Cache\Bar::class,
            Models\Cache\Flight::class,
            Models\Cache\Token::class,
            Models\Cache\Login::class,
            Models\Cache\Client::class,
            Models\Cache\Person::class,
            Models\Cache\Address::class,
            Models\Cache\Action::class,
            Models\Cache\ComplexAction::class,
            Models\Cache\AttractionInfo::class,
            Models\Cache\AttractionContactInfo::class,
            Models\Cache\AttractionLocationInfo::class,
        ],
        'tweet' => [
            Models\Tweet\User::class,
            Models\Tweet\Tweet::class,
            Models\Tweet\UserList::class,
        ],
        'ddc2504' => [
            Models\DDC2504\DDC2504RootClass::class,
            Models\DDC2504\DDC2504ChildClass::class,
            Models\DDC2504\DDC2504OtherClass::class,
        ],
        'ddc3346' => [
            Models\DDC3346\DDC3346Author::class,
            Models\DDC3346\DDC3346Article::class,
        ],
        'quote' => [
            Models\Quote\Address::class,
            Models\Quote\City::class,
            Models\Quote\FullAddress::class,
            Models\Quote\Group::class,
            Models\Quote\NumericEntity::class,
            Models\Quote\Phone::class,
            Models\Quote\User::class,
        ],
        'vct_onetoone' => [
            Models\ValueConversionType\InversedOneToOneEntity::class,
            Models\ValueConversionType\OwningOneToOneEntity::class,
        ],
        'vct_onetoone_compositeid' => [
            Models\ValueConversionType\InversedOneToOneCompositeIdEntity::class,
            Models\ValueConversionType\OwningOneToOneCompositeIdEntity::class,
        ],
        'vct_onetoone_compositeid_foreignkey' => [
            Models\ValueConversionType\AuxiliaryEntity::class,
            Models\ValueConversionType\InversedOneToOneCompositeIdForeignKeyEntity::class,
            Models\ValueConversionType\OwningOneToOneCompositeIdForeignKeyEntity::class,
        ],
        'vct_onetomany' => [
            Models\ValueConversionType\InversedOneToManyEntity::class,
            Models\ValueConversionType\OwningManyToOneEntity::class,
        ],
        'vct_onetomany_compositeid' => [
            Models\ValueConversionType\InversedOneToManyCompositeIdEntity::class,
            Models\ValueConversionType\OwningManyToOneCompositeIdEntity::class,
        ],
        'vct_onetomany_compositeid_foreignkey' => [
            Models\ValueConversionType\AuxiliaryEntity::class,
            Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity::class,
            Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity::class,
        ],
        'vct_onetomany_extralazy' => [
            Models\ValueConversionType\InversedOneToManyExtraLazyEntity::class,
            Models\ValueConversionType\OwningManyToOneExtraLazyEntity::class,
        ],
        'vct_manytomany' => [
            Models\ValueConversionType\InversedManyToManyEntity::class,
            Models\ValueConversionType\OwningManyToManyEntity::class,
        ],
        'vct_manytomany_compositeid' => [
            Models\ValueConversionType\InversedManyToManyCompositeIdEntity::class,
            Models\ValueConversionType\OwningManyToManyCompositeIdEntity::class,
        ],
        'vct_manytomany_compositeid_foreignkey' => [
            Models\ValueConversionType\AuxiliaryEntity::class,
            Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity::class,
            Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity::class,
        ],
        'vct_manytomany_extralazy' => [
            Models\ValueConversionType\InversedManyToManyExtraLazyEntity::class,
            Models\ValueConversionType\OwningManyToManyExtraLazyEntity::class,
        ],
        'geonames' => [
            Models\GeoNames\Country::class,
            Models\GeoNames\Admin1::class,
            Models\GeoNames\Admin1AlternateName::class,
            Models\GeoNames\City::class,
        ],
        'custom_id_object_type' => [
            Models\CustomType\CustomIdObjectTypeParent::class,
            Models\CustomType\CustomIdObjectTypeChild::class,
        ],
        'pagination' => [
            Models\Pagination\Company::class,
            Models\Pagination\Logo::class,
            Models\Pagination\Department::class,
            Models\Pagination\User::class,
            Models\Pagination\User1::class,
        ],
        'versioned_many_to_one' => [
            Models\VersionedManyToOne\Category::class,
            Models\VersionedManyToOne\Article::class,
        ],
        'issue5989' => [
            Models\Issue5989\Issue5989Person::class,
            Models\Issue5989\Issue5989Employee::class,
            Models\Issue5989\Issue5989Manager::class,
        ],
    ];

    /** @param class-string ...$models */
    final protected function createSchemaForModels(string ...$models): void
    {
        try {
            $this->_schemaTool->createSchema($this->getMetadataForModels($models));
        } catch (ToolsException $e) {
        }
    }

    /**
     * @param class-string ...$models
     *
     * @return string[]
     */
    final protected function getUpdateSchemaSqlForModels(string ...$models): array
    {
        return $this->_schemaTool->getUpdateSchemaSql($this->getMetadataForModels($models));
    }

    /** @param class-string ...$models */
    final protected function getSchemaForModels(string ...$models): Schema
    {
        return $this->_schemaTool->getSchemaFromMetadata($this->getMetadataForModels($models));
    }

    /**
     * @param class-string[] $models
     *
     * @return ClassMetadata[]
     */
    private function getMetadataForModels(array $models): array
    {
        return array_map(
            function (string $className): ClassMetadata {
                return $this->_em->getClassMetadata($className);
            },
            $models
        );
    }

    protected function useModelSet(string $setName): void
    {
        $this->_usedModelSets[$setName] = true;
    }

    /**
     * Sweeps the database tables and clears the EntityManager.
     */
    protected function tearDown(): void
    {
        $conn = static::$sharedConn;

        // In case test is skipped, tearDown is called, but no setup may have run
        if (! $conn) {
            return;
        }

        $platform = $conn->getDatabasePlatform();

        if ($this->isQueryLogAvailable()) {
            $this->getQueryLog()->reset();
        }

        if (isset($this->_usedModelSets['cms'])) {
            $conn->executeStatement('DELETE FROM cms_users_groups');
            $conn->executeStatement('DELETE FROM cms_groups');
            $conn->executeStatement('DELETE FROM cms_users_tags');
            $conn->executeStatement('DELETE FROM cms_tags');
            $conn->executeStatement('DELETE FROM cms_addresses');
            $conn->executeStatement('DELETE FROM cms_phonenumbers');
            $conn->executeStatement('DELETE FROM cms_comments');
            $conn->executeStatement('DELETE FROM cms_articles');
            $conn->executeStatement('DELETE FROM cms_users');
            $conn->executeStatement('DELETE FROM cms_emails');
        }

        if (isset($this->_usedModelSets['ecommerce'])) {
            $conn->executeStatement('DELETE FROM ecommerce_carts_products');
            $conn->executeStatement('DELETE FROM ecommerce_products_categories');
            $conn->executeStatement('DELETE FROM ecommerce_products_related');
            $conn->executeStatement('DELETE FROM ecommerce_carts');
            $conn->executeStatement('DELETE FROM ecommerce_customers');
            $conn->executeStatement('DELETE FROM ecommerce_features');
            $conn->executeStatement('DELETE FROM ecommerce_products');
            $conn->executeStatement('DELETE FROM ecommerce_shippings');
            $conn->executeStatement('UPDATE ecommerce_categories SET parent_id = NULL');
            $conn->executeStatement('DELETE FROM ecommerce_categories');
        }

        if (isset($this->_usedModelSets['company'])) {
            $conn->executeStatement('DELETE FROM company_contract_employees');
            $conn->executeStatement('DELETE FROM company_contract_managers');
            $conn->executeStatement('DELETE FROM company_contracts');
            $conn->executeStatement('DELETE FROM company_persons_friends');
            $conn->executeStatement('DELETE FROM company_managers');
            $conn->executeStatement('DELETE FROM company_employees');
            $conn->executeStatement('UPDATE company_persons SET spouse_id = NULL');
            $conn->executeStatement('DELETE FROM company_persons');
            $conn->executeStatement('DELETE FROM company_raffles');
            $conn->executeStatement('DELETE FROM company_auctions');
            $conn->executeStatement('UPDATE company_organizations SET main_event_id = NULL');
            $conn->executeStatement('DELETE FROM company_events');
            $conn->executeStatement('DELETE FROM company_organizations');
        }

        if (isset($this->_usedModelSets['generic'])) {
            $conn->executeStatement('DELETE FROM boolean_model');
            $conn->executeStatement('DELETE FROM date_time_model');
            $conn->executeStatement('DELETE FROM decimal_model');
        }

        if (isset($this->_usedModelSets['routing'])) {
            $conn->executeStatement('DELETE FROM RoutingRouteLegs');
            $conn->executeStatement('DELETE FROM RoutingRouteBooking');
            $conn->executeStatement('DELETE FROM RoutingRoute');
            $conn->executeStatement('DELETE FROM RoutingLeg');
            $conn->executeStatement('DELETE FROM RoutingLocation');
        }

        if (isset($this->_usedModelSets['navigation'])) {
            $conn->executeStatement('DELETE FROM navigation_tour_pois');
            $conn->executeStatement('DELETE FROM navigation_photos');
            $conn->executeStatement('DELETE FROM navigation_pois');
            $conn->executeStatement('DELETE FROM navigation_tours');
            $conn->executeStatement('DELETE FROM navigation_countries');
        }

        if (isset($this->_usedModelSets['directorytree'])) {
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('file'));
            // MySQL doesn't know deferred deletions therefore only executing the second query gives errors.
            $conn->executeStatement('DELETE FROM Directory WHERE parentDirectory_id IS NOT NULL');
            $conn->executeStatement('DELETE FROM Directory');
        }

        if (isset($this->_usedModelSets['ddc117'])) {
            $conn->executeStatement('DELETE FROM ddc117editor_ddc117translation');
            $conn->executeStatement('DELETE FROM DDC117Editor');
            $conn->executeStatement('DELETE FROM DDC117ApproveChanges');
            $conn->executeStatement('DELETE FROM DDC117Link');
            $conn->executeStatement('DELETE FROM DDC117Reference');
            $conn->executeStatement('DELETE FROM DDC117ArticleDetails');
            $conn->executeStatement('DELETE FROM DDC117Translation');
            $conn->executeStatement('DELETE FROM DDC117Article');
        }

        if (isset($this->_usedModelSets['stockexchange'])) {
            $conn->executeStatement('DELETE FROM exchange_bonds_stocks');
            $conn->executeStatement('DELETE FROM exchange_bonds');
            $conn->executeStatement('DELETE FROM exchange_stocks');
            $conn->executeStatement('DELETE FROM exchange_markets');
        }

        if (isset($this->_usedModelSets['legacy'])) {
            $conn->executeStatement('DELETE FROM legacy_users_cars');
            $conn->executeStatement('DELETE FROM legacy_users_reference');
            $conn->executeStatement('DELETE FROM legacy_articles');
            $conn->executeStatement('DELETE FROM legacy_cars');
            $conn->executeStatement('DELETE FROM legacy_users');
        }

        if (isset($this->_usedModelSets['customtype'])) {
            $conn->executeStatement('DELETE FROM customtype_parent_friends');
            $conn->executeStatement('DELETE FROM customtype_parents');
            $conn->executeStatement('DELETE FROM customtype_children');
            $conn->executeStatement('DELETE FROM customtype_uppercases');
        }

        if (isset($this->_usedModelSets['compositekeyinheritance'])) {
            $conn->executeStatement('DELETE FROM JoinedChildClass');
            $conn->executeStatement('DELETE FROM JoinedRootClass');
            $conn->executeStatement('DELETE FROM SingleRootClass');
        }

        if (isset($this->_usedModelSets['taxi'])) {
            $conn->executeStatement('DELETE FROM taxi_paid_ride');
            $conn->executeStatement('DELETE FROM taxi_ride');
            $conn->executeStatement('DELETE FROM taxi_car');
            $conn->executeStatement('DELETE FROM taxi_driver');
        }

        if (isset($this->_usedModelSets['tweet'])) {
            $conn->executeStatement('DELETE FROM tweet_tweet');
            $conn->executeStatement('DELETE FROM tweet_user_list');
            $conn->executeStatement('DELETE FROM tweet_user');
        }

        if (isset($this->_usedModelSets['cache'])) {
            $conn->executeStatement('DELETE FROM cache_attraction_location_info');
            $conn->executeStatement('DELETE FROM cache_attraction_contact_info');
            $conn->executeStatement('DELETE FROM cache_attraction_info');
            $conn->executeStatement('DELETE FROM cache_visited_cities');
            $conn->executeStatement('DELETE FROM cache_flight');
            $conn->executeStatement('DELETE FROM cache_attraction');
            $conn->executeStatement('DELETE FROM cache_travel');
            $conn->executeStatement('DELETE FROM cache_traveler');
            $conn->executeStatement('DELETE FROM cache_traveler_profile_info');
            $conn->executeStatement('DELETE FROM cache_traveler_profile');
            $conn->executeStatement('DELETE FROM cache_city');
            $conn->executeStatement('DELETE FROM cache_state');
            $conn->executeStatement('DELETE FROM cache_country');
            $conn->executeStatement('DELETE FROM cache_login');
            $conn->executeStatement('DELETE FROM cache_token');
            $conn->executeStatement('DELETE FROM cache_complex_action');
            $conn->executeStatement('DELETE FROM cache_action');
            $conn->executeStatement('DELETE FROM cache_client');
        }

        if (isset($this->_usedModelSets['ddc3346'])) {
            $conn->executeStatement('DELETE FROM ddc3346_articles');
            $conn->executeStatement('DELETE FROM ddc3346_users');
        }

        if (isset($this->_usedModelSets['ornemental_orphan_removal'])) {
            $conn->executeStatement('DELETE FROM ornemental_orphan_removal_person');
            $conn->executeStatement('DELETE FROM ornemental_orphan_removal_phone_number');
        }

        if (isset($this->_usedModelSets['quote'])) {
            $conn->executeStatement(
                sprintf(
                    'UPDATE %s SET %s = NULL',
                    $platform->quoteIdentifier('quote-address'),
                    $platform->quoteIdentifier('user-id')
                )
            );

            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-users-groups'));
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-group'));
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-phone'));
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-user'));
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-address'));
            $conn->executeStatement('DELETE FROM ' . $platform->quoteIdentifier('quote-city'));
        }

        if (isset($this->_usedModelSets['vct_onetoone'])) {
            $conn->executeStatement('DELETE FROM vct_owning_onetoone');
            $conn->executeStatement('DELETE FROM vct_inversed_onetoone');
        }

        if (isset($this->_usedModelSets['vct_onetoone_compositeid'])) {
            $conn->executeStatement('DELETE FROM vct_owning_onetoone_compositeid');
            $conn->executeStatement('DELETE FROM vct_inversed_onetoone_compositeid');
        }

        if (isset($this->_usedModelSets['vct_onetoone_compositeid_foreignkey'])) {
            $conn->executeStatement('DELETE FROM vct_owning_onetoone_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_inversed_onetoone_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_onetomany'])) {
            $conn->executeStatement('DELETE FROM vct_owning_manytoone');
            $conn->executeStatement('DELETE FROM vct_inversed_onetomany');
        }

        if (isset($this->_usedModelSets['vct_onetomany_compositeid'])) {
            $conn->executeStatement('DELETE FROM vct_owning_manytoone_compositeid');
            $conn->executeStatement('DELETE FROM vct_inversed_onetomany_compositeid');
        }

        if (isset($this->_usedModelSets['vct_onetomany_compositeid_foreignkey'])) {
            $conn->executeStatement('DELETE FROM vct_owning_manytoone_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_inversed_onetomany_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_onetomany_extralazy'])) {
            $conn->executeStatement('DELETE FROM vct_owning_manytoone_extralazy');
            $conn->executeStatement('DELETE FROM vct_inversed_onetomany_extralazy');
        }

        if (isset($this->_usedModelSets['vct_manytomany'])) {
            $conn->executeStatement('DELETE FROM vct_xref_manytomany');
            $conn->executeStatement('DELETE FROM vct_owning_manytomany');
            $conn->executeStatement('DELETE FROM vct_inversed_manytomany');
        }

        if (isset($this->_usedModelSets['vct_manytomany_compositeid'])) {
            $conn->executeStatement('DELETE FROM vct_xref_manytomany_compositeid');
            $conn->executeStatement('DELETE FROM vct_owning_manytomany_compositeid');
            $conn->executeStatement('DELETE FROM vct_inversed_manytomany_compositeid');
        }

        if (isset($this->_usedModelSets['vct_manytomany_compositeid_foreignkey'])) {
            $conn->executeStatement('DELETE FROM vct_xref_manytomany_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_owning_manytomany_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_inversed_manytomany_compositeid_foreignkey');
            $conn->executeStatement('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_manytomany_extralazy'])) {
            $conn->executeStatement('DELETE FROM vct_xref_manytomany_extralazy');
            $conn->executeStatement('DELETE FROM vct_owning_manytomany_extralazy');
            $conn->executeStatement('DELETE FROM vct_inversed_manytomany_extralazy');
        }

        if (isset($this->_usedModelSets['geonames'])) {
            $conn->executeStatement('DELETE FROM geonames_admin1_alternate_name');
            $conn->executeStatement('DELETE FROM geonames_admin1');
            $conn->executeStatement('DELETE FROM geonames_city');
            $conn->executeStatement('DELETE FROM geonames_country');
        }

        if (isset($this->_usedModelSets['custom_id_object_type'])) {
            $conn->executeStatement('DELETE FROM custom_id_type_child');
            $conn->executeStatement('DELETE FROM custom_id_type_parent');
        }

        if (isset($this->_usedModelSets['pagination'])) {
            $conn->executeStatement('DELETE FROM pagination_logo');
            $conn->executeStatement('DELETE FROM pagination_department');
            $conn->executeStatement('DELETE FROM pagination_company');
            $conn->executeStatement('DELETE FROM pagination_user');
        }

        if (isset($this->_usedModelSets['versioned_many_to_one'])) {
            $conn->executeStatement('DELETE FROM versioned_many_to_one_article');
            $conn->executeStatement('DELETE FROM versioned_many_to_one_category');
        }

        if (isset($this->_usedModelSets['issue5989'])) {
            $conn->executeStatement('DELETE FROM issue5989_persons');
            $conn->executeStatement('DELETE FROM issue5989_employees');
            $conn->executeStatement('DELETE FROM issue5989_managers');
        }

        $this->_em->clear();
    }

    /**
     * @param array $classNames
     *
     * @throws RuntimeException
     */
    protected function setUpEntitySchema(array $classNames): void
    {
        if ($this->_em === null) {
            throw new RuntimeException('EntityManager not set, you have to call parent::setUp() before invoking this method.');
        }

        $classes = [];
        foreach ($classNames as $className) {
            if (! isset(static::$_entityTablesCreated[$className])) {
                static::$_entityTablesCreated[$className] = true;
                $classes[]                                = $this->_em->getClassMetadata($className);
            }
        }

        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }
    }

    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     */
    protected function setUp(): void
    {
        $this->setUpDBALTypes();

        if (! isset(static::$sharedConn)) {
            static::$sharedConn = TestUtil::getConnection();
        }

        if (isset($GLOBALS['DOCTRINE_MARK_SQL_LOGS'])) {
            $platform = static::$sharedConn->getDatabasePlatform();
            if (
                $platform instanceof MySQLPlatform
                || $platform instanceof PostgreSQLPlatform
            ) {
                static::$sharedConn->executeQuery('SELECT 1 /*' . static::class . '*/');
            } elseif ($platform instanceof OraclePlatform) {
                static::$sharedConn->executeQuery('SELECT 1 /*' . static::class . '*/ FROM dual');
            }
        }

        if (! $this->_em) {
            $this->_em         = $this->getEntityManager();
            $this->_schemaTool = new SchemaTool($this->_em);
        }

        $classes = [];

        foreach ($this->_usedModelSets as $setName => $bool) {
            if (! isset(static::$tablesCreated[$setName])) {
                foreach (static::$modelSets[$setName] as $className) {
                    $classes[] = $this->_em->getClassMetadata($className);
                }

                static::$tablesCreated[$setName] = true;
            }
        }

        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }

        $this->getQueryLog()->enable();
    }

    /**
     * Gets an EntityManager for testing purposes.
     *
     * @throws ORMException
     */
    protected function getEntityManager(
        ?DbalExtensions\Connection $connection = null,
        ?MappingDriver $mappingDriver = null
    ): EntityManagerInterface {
        // NOTE: Functional tests use their own shared metadata cache, because
        // the actual database platform used during execution has effect on some
        // metadata mapping behaviors (like the choice of the ID generation).
        if (self::$metadataCache === null) {
            self::$metadataCache = new ArrayAdapter();
        }

        if (self::$queryCache === null) {
            self::$queryCache = new ArrayAdapter();
        }

        //FIXME: two different configs! $conn and the created entity manager have
        // different configs.
        $config = new Configuration();
        $config->setMetadataCache(self::$metadataCache);
        $config->setQueryCache(self::$queryCache);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        if ($this->resultCache !== null) {
            $config->setResultCache($this->resultCache);
        }

        $enableSecondLevelCache = getenv('ENABLE_SECOND_LEVEL_CACHE');

        if ($this->isSecondLevelCacheEnabled || $enableSecondLevelCache) {
            $cacheConfig = new CacheConfiguration();
            $factory     = new DefaultCacheFactory(
                $cacheConfig->getRegionsConfiguration(),
                $this->getSharedSecondLevelCache()
            );

            $this->secondLevelCacheFactory = $factory;

            if ($this->isSecondLevelCacheLogEnabled) {
                $this->secondLevelCacheLogger = new StatisticsCacheLogger();
                $cacheConfig->setCacheLogger($this->secondLevelCacheLogger);
            }

            $cacheConfig->setCacheFactory($factory);
            $config->setSecondLevelCacheEnabled(true);
            $config->setSecondLevelCacheConfiguration($cacheConfig);

            $this->isSecondLevelCacheEnabled = true;
        }

        $config->setMetadataDriverImpl(
            $mappingDriver ?? ORMSetup::createDefaultAnnotationDriver([
                realpath(__DIR__ . '/Models/Cache'),
                realpath(__DIR__ . '/Models/GeoNames'),
            ])
        );

        $conn = $connection ?: static::$sharedConn;
        assert($conn !== null);
        $conn->queryLog->reset();

        // get rid of more global state
        $evm = $conn->getEventManager();
        foreach ($evm->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evm->removeEventListener([$event], $listener);
            }
        }

        if ($enableSecondLevelCache) {
            $evm->addEventListener('loadClassMetadata', new CacheMetadataListener());
        }

        if (isset($GLOBALS['db_event_subscribers'])) {
            foreach (explode(',', $GLOBALS['db_event_subscribers']) as $subscriberClass) {
                $subscriberInstance = new $subscriberClass();
                $evm->addEventSubscriber($subscriberInstance);
            }
        }

        if (isset($GLOBALS['debug_uow_listener'])) {
            $evm->addEventListener(['onFlush'], new DebugUnitOfWorkListener());
        }

        return EntityManager::create($conn, $config);
    }

    final protected function createSchemaManager(): AbstractSchemaManager
    {
        return method_exists(Connection::class, 'createSchemaManager')
            ? $this->_em->getConnection()->createSchemaManager()
            : $this->_em->getConnection()->getSchemaManager();
    }

    /** @throws Throwable */
    protected function onNotSuccessfulTest(Throwable $e): void
    {
        if ($e instanceof AssertionFailedError || $e instanceof Warning) {
            throw $e;
        }

        if ($this->isQueryLogAvailable() && $this->getQueryLog()->queries !== []) {
            $queries       = '';
            $last25queries = array_slice(array_reverse($this->getQueryLog()->queries, true), 0, 25, true);
            foreach ($last25queries as $i => $query) {
                $params   = array_map(static function ($p) {
                    return is_object($p) ? get_debug_type($p) : var_export($p, true);
                }, $query['params'] ?: []);
                $queries .= $i . ". SQL: '" . $query['sql'] . "' Params: " . implode(', ', $params) . PHP_EOL;
            }

            $trace    = $e->getTrace();
            $traceMsg = '';
            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (str_contains($part['file'], 'PHPUnit/')) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'] . ':' . $part['line'] . PHP_EOL;
                }
            }

            $message = '[' . get_debug_type($e) . '] ' . $e->getMessage() . PHP_EOL . PHP_EOL . 'With queries:' . PHP_EOL . $queries . PHP_EOL . 'Trace:' . PHP_EOL . $traceMsg;

            throw new Exception($message, (int) $e->getCode(), $e);
        }

        throw $e;
    }

    public function assertSQLEquals(string $expectedSql, string $actualSql): void
    {
        self::assertEquals(
            strtolower($expectedSql),
            strtolower($actualSql),
            'Lowercase comparison of SQL statements failed.'
        );
    }

    /**
     * Configures DBAL types required in tests
     */
    protected function setUpDBALTypes(): void
    {
        if (Type::hasType('rot13')) {
            Type::overrideType('rot13', Rot13Type::class);
        } else {
            Type::addType('rot13', Rot13Type::class);
        }
    }

    final protected function isQueryLogAvailable(): bool
    {
        return $this->_em && $this->_em->getConnection() instanceof DbalExtensions\Connection;
    }

    final protected function getQueryLog(): QueryLog
    {
        $connection = $this->_em->getConnection();
        if (! $connection instanceof DbalExtensions\Connection) {
            throw new RuntimeException(sprintf(
                'The query log is only available if %s is used as wrapper class. Got %s.',
                DbalExtensions\Connection::class,
                get_debug_type($connection)
            ));
        }

        return $connection->queryLog;
    }

    final protected function assertQueryCount(int $expectedCount, string $message = ''): void
    {
        self::assertThat($this->getQueryLog()->queries, new Count($expectedCount), $message);
    }

    /** @psalm-return array{sql: string, params: array|null, types: array|null} */
    final protected function getLastLoggedQuery(int $index = 0): array
    {
        $queries   = $this->getQueryLog()->queries;
        $lastQuery = null;
        for ($i = $index; $i >= 0; $i--) {
            $lastQuery = array_pop($queries);
        }

        if ($lastQuery === null) {
            throw new RuntimeException('The query log was empty.');
        }

        return $lastQuery;
    }

    /**
     * Drops the table with the specified name, if it exists.
     *
     * @throws Exception
     */
    protected function dropTableIfExists(string $name): void
    {
        $schemaManager = $this->createSchemaManager();

        try {
            $schemaManager->dropTable($name);
        } catch (DatabaseObjectNotFoundException $e) {
        }
    }

    /**
     * Drops and creates a new table.
     *
     * @throws Exception
     */
    protected function dropAndCreateTable(Table $table): void
    {
        $schemaManager = $this->createSchemaManager();
        $platform      = $this->_em->getConnection()->getDatabasePlatform();
        $tableName     = $table->getQuotedName($platform);

        $this->dropTableIfExists($tableName);
        $schemaManager->createTable($table);
    }
}

<?php

namespace Doctrine\Tests;

use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\EventListener\CacheMetadataListener;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Cache\DefaultCacheFactory;

/**
 * Base testcase class for all functional ORM testcases.
 *
 * @since 2.0
 */
abstract class OrmFunctionalTestCase extends OrmTestCase
{
    /**
     * The metadata cache shared between all functional tests.
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $_metadataCacheImpl = null;

    /**
     * The query cache shared between all functional tests.
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $_queryCacheImpl = null;

    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var \Doctrine\DBAL\Connection|null
     */
    protected static $_sharedConn;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    protected $_schemaTool;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $_sqlLoggerStack;

    /**
     * The names of the model sets used in this testcase.
     *
     * @var array
     */
    protected $_usedModelSets = array();

    /**
     * Whether the database schema has already been created.
     *
     * @var array
     */
    protected static $_tablesCreated = array();

    /**
     * Array of entity class name to their tables that were created.
     *
     * @var array
     */
    protected static $_entityTablesCreated = array();

    /**
     * List of model sets and their classes.
     *
     * @var array
     */
    protected static $_modelSets = array(
        'cms' => array(
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
            'Doctrine\Tests\Models\CMS\CmsEmail',
            'Doctrine\Tests\Models\CMS\CmsGroup',
            'Doctrine\Tests\Models\CMS\CmsArticle',
            'Doctrine\Tests\Models\CMS\CmsComment',
        ),
        'forum' => array(),
        'company' => array(
            'Doctrine\Tests\Models\Company\CompanyPerson',
            'Doctrine\Tests\Models\Company\CompanyEmployee',
            'Doctrine\Tests\Models\Company\CompanyManager',
            'Doctrine\Tests\Models\Company\CompanyOrganization',
            'Doctrine\Tests\Models\Company\CompanyEvent',
            'Doctrine\Tests\Models\Company\CompanyAuction',
            'Doctrine\Tests\Models\Company\CompanyRaffle',
            'Doctrine\Tests\Models\Company\CompanyCar',
            'Doctrine\Tests\Models\Company\CompanyContract',
        ),
        'ecommerce' => array(
            'Doctrine\Tests\Models\ECommerce\ECommerceCart',
            'Doctrine\Tests\Models\ECommerce\ECommerceCustomer',
            'Doctrine\Tests\Models\ECommerce\ECommerceProduct',
            'Doctrine\Tests\Models\ECommerce\ECommerceShipping',
            'Doctrine\Tests\Models\ECommerce\ECommerceFeature',
            'Doctrine\Tests\Models\ECommerce\ECommerceCategory'
        ),
        'generic' => array(
            'Doctrine\Tests\Models\Generic\BooleanModel',
            'Doctrine\Tests\Models\Generic\DateTimeModel',
            'Doctrine\Tests\Models\Generic\DecimalModel',
            'Doctrine\Tests\Models\Generic\SerializationModel',
        ),
        'routing' => array(
            'Doctrine\Tests\Models\Routing\RoutingLeg',
            'Doctrine\Tests\Models\Routing\RoutingLocation',
            'Doctrine\Tests\Models\Routing\RoutingRoute',
            'Doctrine\Tests\Models\Routing\RoutingRouteBooking',
        ),
        'navigation' => array(
            'Doctrine\Tests\Models\Navigation\NavUser',
            'Doctrine\Tests\Models\Navigation\NavCountry',
            'Doctrine\Tests\Models\Navigation\NavPhotos',
            'Doctrine\Tests\Models\Navigation\NavTour',
            'Doctrine\Tests\Models\Navigation\NavPointOfInterest',
        ),
        'directorytree' => array(
            'Doctrine\Tests\Models\DirectoryTree\AbstractContentItem',
            'Doctrine\Tests\Models\DirectoryTree\File',
            'Doctrine\Tests\Models\DirectoryTree\Directory',
        ),
        'ddc117' => array(
            'Doctrine\Tests\Models\DDC117\DDC117Article',
            'Doctrine\Tests\Models\DDC117\DDC117Reference',
            'Doctrine\Tests\Models\DDC117\DDC117Translation',
            'Doctrine\Tests\Models\DDC117\DDC117ArticleDetails',
            'Doctrine\Tests\Models\DDC117\DDC117ApproveChanges',
            'Doctrine\Tests\Models\DDC117\DDC117Editor',
            'Doctrine\Tests\Models\DDC117\DDC117Link',
        ),
        'ddc3699' => array(
            'Doctrine\Tests\Models\DDC3699\DDC3699Parent',
            'Doctrine\Tests\Models\DDC3699\DDC3699RelationOne',
            'Doctrine\Tests\Models\DDC3699\DDC3699RelationMany',
            'Doctrine\Tests\Models\DDC3699\DDC3699Child',
        ),
        'stockexchange' => array(
            'Doctrine\Tests\Models\StockExchange\Bond',
            'Doctrine\Tests\Models\StockExchange\Stock',
            'Doctrine\Tests\Models\StockExchange\Market',
        ),
        'legacy' => array(
            'Doctrine\Tests\Models\Legacy\LegacyUser',
            'Doctrine\Tests\Models\Legacy\LegacyUserReference',
            'Doctrine\Tests\Models\Legacy\LegacyArticle',
            'Doctrine\Tests\Models\Legacy\LegacyCar',
        ),
        'customtype' => array(
            'Doctrine\Tests\Models\CustomType\CustomTypeChild',
            'Doctrine\Tests\Models\CustomType\CustomTypeParent',
            'Doctrine\Tests\Models\CustomType\CustomTypeUpperCase',
        ),
        'compositekeyinheritance' => array(
            'Doctrine\Tests\Models\CompositeKeyInheritance\JoinedRootClass',
            'Doctrine\Tests\Models\CompositeKeyInheritance\JoinedChildClass',
            'Doctrine\Tests\Models\CompositeKeyInheritance\SingleRootClass',
            'Doctrine\Tests\Models\CompositeKeyInheritance\SingleChildClass',
        ),
        'taxi' => array(
            'Doctrine\Tests\Models\Taxi\PaidRide',
            'Doctrine\Tests\Models\Taxi\Ride',
            'Doctrine\Tests\Models\Taxi\Car',
            'Doctrine\Tests\Models\Taxi\Driver',
        ),
        'cache' => array(
            'Doctrine\Tests\Models\Cache\Country',
            'Doctrine\Tests\Models\Cache\State',
            'Doctrine\Tests\Models\Cache\City',
            'Doctrine\Tests\Models\Cache\Traveler',
            'Doctrine\Tests\Models\Cache\TravelerProfileInfo',
            'Doctrine\Tests\Models\Cache\TravelerProfile',
            'Doctrine\Tests\Models\Cache\Travel',
            'Doctrine\Tests\Models\Cache\Attraction',
            'Doctrine\Tests\Models\Cache\Restaurant',
            'Doctrine\Tests\Models\Cache\Beach',
            'Doctrine\Tests\Models\Cache\Bar',
            'Doctrine\Tests\Models\Cache\Flight',
            'Doctrine\Tests\Models\Cache\Token',
            'Doctrine\Tests\Models\Cache\Login',
            'Doctrine\Tests\Models\Cache\Client',
            'Doctrine\Tests\Models\Cache\Person',
            'Doctrine\Tests\Models\Cache\Address',
            'Doctrine\Tests\Models\Cache\Action',
            'Doctrine\Tests\Models\Cache\ComplexAction',
            'Doctrine\Tests\Models\Cache\AttractionInfo',
            'Doctrine\Tests\Models\Cache\AttractionContactInfo',
            'Doctrine\Tests\Models\Cache\AttractionLocationInfo'
        ),
        'tweet' => array(
            'Doctrine\Tests\Models\Tweet\User',
            'Doctrine\Tests\Models\Tweet\Tweet',
            'Doctrine\Tests\Models\Tweet\UserList',
        ),
        'ddc2504' => array(
            'Doctrine\Tests\Models\DDC2504\DDC2504RootClass',
            'Doctrine\Tests\Models\DDC2504\DDC2504ChildClass',
            'Doctrine\Tests\Models\DDC2504\DDC2504OtherClass',
        ),
        'ddc3346' => array(
            'Doctrine\Tests\Models\DDC3346\DDC3346Author',
            'Doctrine\Tests\Models\DDC3346\DDC3346Article',
        ),
        'quote' => array(
            'Doctrine\Tests\Models\Quote\Address',
            'Doctrine\Tests\Models\Quote\Group',
            'Doctrine\Tests\Models\Quote\NumericEntity',
            'Doctrine\Tests\Models\Quote\Phone',
            'Doctrine\Tests\Models\Quote\User'
        ),
        'vct_onetoone' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneEntity'
        ),
        'vct_onetoone_compositeid' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdEntity'
        ),
        'vct_onetoone_compositeid_foreignkey' => array(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToOneCompositeIdForeignKeyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningOneToOneCompositeIdForeignKeyEntity'
        ),
        'vct_onetomany' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneEntity'
        ),
        'vct_onetomany_compositeid' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdEntity'
        ),
        'vct_onetomany_compositeid_foreignkey' => array(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyCompositeIdForeignKeyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneCompositeIdForeignKeyEntity'
        ),
        'vct_onetomany_extralazy' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedOneToManyExtraLazyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToOneExtraLazyEntity'
        ),
        'vct_manytomany' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyEntity'
        ),
        'vct_manytomany_compositeid' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdEntity'
        ),
        'vct_manytomany_compositeid_foreignkey' => array(
            'Doctrine\Tests\Models\ValueConversionType\AuxiliaryEntity',
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyCompositeIdForeignKeyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyCompositeIdForeignKeyEntity'
        ),
        'vct_manytomany_extralazy' => array(
            'Doctrine\Tests\Models\ValueConversionType\InversedManyToManyExtraLazyEntity',
            'Doctrine\Tests\Models\ValueConversionType\OwningManyToManyExtraLazyEntity'
        ),
        'geonames' => array(
            'Doctrine\Tests\Models\GeoNames\Country',
            'Doctrine\Tests\Models\GeoNames\Admin1',
            'Doctrine\Tests\Models\GeoNames\Admin1AlternateName',
            'Doctrine\Tests\Models\GeoNames\City'
        ),
        'custom_id_object_type' => array(
            'Doctrine\Tests\Models\CustomType\CustomIdObjectTypeParent',
            'Doctrine\Tests\Models\CustomType\CustomIdObjectTypeChild',
        ),
        'pagination' => array(
            'Doctrine\Tests\Models\Pagination\Company',
            'Doctrine\Tests\Models\Pagination\Logo',
            'Doctrine\Tests\Models\Pagination\Department',
            'Doctrine\Tests\Models\Pagination\User',
            'Doctrine\Tests\Models\Pagination\User1',
        ),
        'versioned_many_to_one' => array(
            'Doctrine\Tests\Models\VersionedManyToOne\Category',
            'Doctrine\Tests\Models\VersionedManyToOne\Article',
        ),
    );

    /**
     * @param string $setName
     *
     * @return void
     */
    protected function useModelSet($setName)
    {
        $this->_usedModelSets[$setName] = true;
    }

    /**
     * Sweeps the database tables and clears the EntityManager.
     *
     * @return void
     */
    protected function tearDown()
    {
        $conn = static::$_sharedConn;

        // In case test is skipped, tearDown is called, but no setup may have run
        if ( ! $conn) {
            return;
        }

        $platform = $conn->getDatabasePlatform();

        $this->_sqlLoggerStack->enabled = false;

        if (isset($this->_usedModelSets['cms'])) {
            $conn->executeUpdate('DELETE FROM cms_users_groups');
            $conn->executeUpdate('DELETE FROM cms_groups');
            $conn->executeUpdate('DELETE FROM cms_addresses');
            $conn->executeUpdate('DELETE FROM cms_phonenumbers');
            $conn->executeUpdate('DELETE FROM cms_comments');
            $conn->executeUpdate('DELETE FROM cms_articles');
            $conn->executeUpdate('DELETE FROM cms_users');
            $conn->executeUpdate('DELETE FROM cms_emails');
        }

        if (isset($this->_usedModelSets['ecommerce'])) {
            $conn->executeUpdate('DELETE FROM ecommerce_carts_products');
            $conn->executeUpdate('DELETE FROM ecommerce_products_categories');
            $conn->executeUpdate('DELETE FROM ecommerce_products_related');
            $conn->executeUpdate('DELETE FROM ecommerce_carts');
            $conn->executeUpdate('DELETE FROM ecommerce_customers');
            $conn->executeUpdate('DELETE FROM ecommerce_features');
            $conn->executeUpdate('DELETE FROM ecommerce_products');
            $conn->executeUpdate('DELETE FROM ecommerce_shippings');
            $conn->executeUpdate('UPDATE ecommerce_categories SET parent_id = NULL');
            $conn->executeUpdate('DELETE FROM ecommerce_categories');
        }

        if (isset($this->_usedModelSets['company'])) {
            $conn->executeUpdate('DELETE FROM company_contract_employees');
            $conn->executeUpdate('DELETE FROM company_contract_managers');
            $conn->executeUpdate('DELETE FROM company_contracts');
            $conn->executeUpdate('DELETE FROM company_persons_friends');
            $conn->executeUpdate('DELETE FROM company_managers');
            $conn->executeUpdate('DELETE FROM company_employees');
            $conn->executeUpdate('UPDATE company_persons SET spouse_id = NULL');
            $conn->executeUpdate('DELETE FROM company_persons');
            $conn->executeUpdate('DELETE FROM company_raffles');
            $conn->executeUpdate('DELETE FROM company_auctions');
            $conn->executeUpdate('UPDATE company_organizations SET main_event_id = NULL');
            $conn->executeUpdate('DELETE FROM company_events');
            $conn->executeUpdate('DELETE FROM company_organizations');
        }

        if (isset($this->_usedModelSets['generic'])) {
            $conn->executeUpdate('DELETE FROM boolean_model');
            $conn->executeUpdate('DELETE FROM date_time_model');
            $conn->executeUpdate('DELETE FROM decimal_model');
            $conn->executeUpdate('DELETE FROM serialize_model');
        }

        if (isset($this->_usedModelSets['routing'])) {
            $conn->executeUpdate('DELETE FROM RoutingRouteLegs');
            $conn->executeUpdate('DELETE FROM RoutingRouteBooking');
            $conn->executeUpdate('DELETE FROM RoutingRoute');
            $conn->executeUpdate('DELETE FROM RoutingLeg');
            $conn->executeUpdate('DELETE FROM RoutingLocation');
        }

        if(isset($this->_usedModelSets['navigation'])) {
            $conn->executeUpdate('DELETE FROM navigation_tour_pois');
            $conn->executeUpdate('DELETE FROM navigation_photos');
            $conn->executeUpdate('DELETE FROM navigation_pois');
            $conn->executeUpdate('DELETE FROM navigation_tours');
            $conn->executeUpdate('DELETE FROM navigation_countries');
        }
        if (isset($this->_usedModelSets['directorytree'])) {
            $conn->executeUpdate('DELETE FROM ' . $platform->quoteIdentifier("file"));
            // MySQL doesn't know deferred deletions therefore only executing the second query gives errors.
            $conn->executeUpdate('DELETE FROM Directory WHERE parentDirectory_id IS NOT NULL');
            $conn->executeUpdate('DELETE FROM Directory');
        }
        if (isset($this->_usedModelSets['ddc117'])) {
            $conn->executeUpdate('DELETE FROM ddc117editor_ddc117translation');
            $conn->executeUpdate('DELETE FROM DDC117Editor');
            $conn->executeUpdate('DELETE FROM DDC117ApproveChanges');
            $conn->executeUpdate('DELETE FROM DDC117Link');
            $conn->executeUpdate('DELETE FROM DDC117Reference');
            $conn->executeUpdate('DELETE FROM DDC117ArticleDetails');
            $conn->executeUpdate('DELETE FROM DDC117Translation');
            $conn->executeUpdate('DELETE FROM DDC117Article');
        }
        if (isset($this->_usedModelSets['stockexchange'])) {
            $conn->executeUpdate('DELETE FROM exchange_bonds_stocks');
            $conn->executeUpdate('DELETE FROM exchange_bonds');
            $conn->executeUpdate('DELETE FROM exchange_stocks');
            $conn->executeUpdate('DELETE FROM exchange_markets');
        }
        if (isset($this->_usedModelSets['legacy'])) {
            $conn->executeUpdate('DELETE FROM legacy_users_cars');
            $conn->executeUpdate('DELETE FROM legacy_users_reference');
            $conn->executeUpdate('DELETE FROM legacy_articles');
            $conn->executeUpdate('DELETE FROM legacy_cars');
            $conn->executeUpdate('DELETE FROM legacy_users');
        }

        if (isset($this->_usedModelSets['customtype'])) {
            $conn->executeUpdate('DELETE FROM customtype_parent_friends');
            $conn->executeUpdate('DELETE FROM customtype_parents');
            $conn->executeUpdate('DELETE FROM customtype_children');
            $conn->executeUpdate('DELETE FROM customtype_uppercases');
        }

        if (isset($this->_usedModelSets['compositekeyinheritance'])) {
            $conn->executeUpdate('DELETE FROM JoinedChildClass');
            $conn->executeUpdate('DELETE FROM JoinedRootClass');
            $conn->executeUpdate('DELETE FROM SingleRootClass');
        }

        if (isset($this->_usedModelSets['taxi'])) {
            $conn->executeUpdate('DELETE FROM taxi_paid_ride');
            $conn->executeUpdate('DELETE FROM taxi_ride');
            $conn->executeUpdate('DELETE FROM taxi_car');
            $conn->executeUpdate('DELETE FROM taxi_driver');
        }

        if (isset($this->_usedModelSets['tweet'])) {
            $conn->executeUpdate('DELETE FROM tweet_tweet');
            $conn->executeUpdate('DELETE FROM tweet_user_list');
            $conn->executeUpdate('DELETE FROM tweet_user');
        }

        if (isset($this->_usedModelSets['cache'])) {
            $conn->executeUpdate('DELETE FROM cache_attraction_location_info');
            $conn->executeUpdate('DELETE FROM cache_attraction_contact_info');
            $conn->executeUpdate('DELETE FROM cache_attraction_info');
            $conn->executeUpdate('DELETE FROM cache_visited_cities');
            $conn->executeUpdate('DELETE FROM cache_flight');
            $conn->executeUpdate('DELETE FROM cache_attraction');
            $conn->executeUpdate('DELETE FROM cache_travel');
            $conn->executeUpdate('DELETE FROM cache_traveler');
            $conn->executeUpdate('DELETE FROM cache_traveler_profile_info');
            $conn->executeUpdate('DELETE FROM cache_traveler_profile');
            $conn->executeUpdate('DELETE FROM cache_city');
            $conn->executeUpdate('DELETE FROM cache_state');
            $conn->executeUpdate('DELETE FROM cache_country');
            $conn->executeUpdate('DELETE FROM cache_login');
            $conn->executeUpdate('DELETE FROM cache_complex_action');
            $conn->executeUpdate('DELETE FROM cache_token');
            $conn->executeUpdate('DELETE FROM cache_action');
            $conn->executeUpdate('DELETE FROM cache_client');
        }

        if (isset($this->_usedModelSets['ddc3346'])) {
            $conn->executeUpdate('DELETE FROM ddc3346_articles');
            $conn->executeUpdate('DELETE FROM ddc3346_users');
        }

        if (isset($this->_usedModelSets['quote'])) {
            $conn->executeUpdate('DELETE FROM ' . $platform->quoteIdentifier("quote-address"));
            $conn->executeUpdate('DELETE FROM ' . $platform->quoteIdentifier("quote-group"));
            $conn->executeUpdate('DELETE FROM ' . $platform->quoteIdentifier("quote-phone"));
            $conn->executeUpdate('DELETE FROM ' . $platform->quoteIdentifier("quote-user"));
        }

        if (isset($this->_usedModelSets['vct_onetoone'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_onetoone');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetoone');
        }

        if (isset($this->_usedModelSets['vct_onetoone_compositeid'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_onetoone_compositeid');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetoone_compositeid');
        }

        if (isset($this->_usedModelSets['vct_onetoone_compositeid_foreignkey'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_onetoone_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetoone_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_onetomany'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_manytoone');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetomany');
        }

        if (isset($this->_usedModelSets['vct_onetomany_compositeid'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_manytoone_compositeid');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetomany_compositeid');
        }

        if (isset($this->_usedModelSets['vct_onetomany_compositeid_foreignkey'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_manytoone_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetomany_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_onetomany_extralazy'])) {
            $conn->executeUpdate('DELETE FROM vct_owning_manytoone_extralazy');
            $conn->executeUpdate('DELETE FROM vct_inversed_onetomany_extralazy');
        }

        if (isset($this->_usedModelSets['vct_manytomany'])) {
            $conn->executeUpdate('DELETE FROM vct_xref_manytomany');
            $conn->executeUpdate('DELETE FROM vct_owning_manytomany');
            $conn->executeUpdate('DELETE FROM vct_inversed_manytomany');
        }

        if (isset($this->_usedModelSets['vct_manytomany_compositeid'])) {
            $conn->executeUpdate('DELETE FROM vct_xref_manytomany_compositeid');
            $conn->executeUpdate('DELETE FROM vct_owning_manytomany_compositeid');
            $conn->executeUpdate('DELETE FROM vct_inversed_manytomany_compositeid');
        }

        if (isset($this->_usedModelSets['vct_manytomany_compositeid_foreignkey'])) {
            $conn->executeUpdate('DELETE FROM vct_xref_manytomany_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_owning_manytomany_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_inversed_manytomany_compositeid_foreignkey');
            $conn->executeUpdate('DELETE FROM vct_auxiliary');
        }

        if (isset($this->_usedModelSets['vct_manytomany_extralazy'])) {
            $conn->executeUpdate('DELETE FROM vct_xref_manytomany_extralazy');
            $conn->executeUpdate('DELETE FROM vct_owning_manytomany_extralazy');
            $conn->executeUpdate('DELETE FROM vct_inversed_manytomany_extralazy');
        }
        if (isset($this->_usedModelSets['geonames'])) {
            $conn->executeUpdate('DELETE FROM geonames_admin1_alternate_name');
            $conn->executeUpdate('DELETE FROM geonames_admin1');
            $conn->executeUpdate('DELETE FROM geonames_city');
            $conn->executeUpdate('DELETE FROM geonames_country');
        }

        if (isset($this->_usedModelSets['custom_id_object_type'])) {
            $conn->executeUpdate('DELETE FROM custom_id_type_child');
            $conn->executeUpdate('DELETE FROM custom_id_type_parent');
        }

        if (isset($this->_usedModelSets['pagination'])) {
            $conn->executeUpdate('DELETE FROM pagination_logo');
            $conn->executeUpdate('DELETE FROM pagination_department');
            $conn->executeUpdate('DELETE FROM pagination_company');
            $conn->executeUpdate('DELETE FROM pagination_user');
        }

        if (isset($this->_usedModelSets['versioned_many_to_one'])) {
            $conn->executeUpdate('DELETE FROM versioned_many_to_one_article');
            $conn->executeUpdate('DELETE FROM versioned_many_to_one_category');
        }

        $this->_em->clear();
    }

    /**
     * @param array $classNames
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function setUpEntitySchema(array $classNames)
    {
        if ($this->_em === null) {
            throw new \RuntimeException("EntityManager not set, you have to call parent::setUp() before invoking this method.");
        }

        $classes = array();
        foreach ($classNames as $className) {
            if ( ! isset(static::$_entityTablesCreated[$className])) {
                static::$_entityTablesCreated[$className] = true;
                $classes[] = $this->_em->getClassMetadata($className);
            }
        }

        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }
    }

    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->setUpDBALTypes();

        $forceCreateTables = false;

        if ( ! isset(static::$_sharedConn)) {
            static::$_sharedConn = TestUtil::getConnection();

            if (static::$_sharedConn->getDriver() instanceof \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
                $forceCreateTables = true;
            }
        }

        if (isset($GLOBALS['DOCTRINE_MARK_SQL_LOGS'])) {
            if (in_array(static::$_sharedConn->getDatabasePlatform()->getName(), array("mysql", "postgresql"))) {
                static::$_sharedConn->executeQuery('SELECT 1 /*' . get_class($this) . '*/');
            } else if (static::$_sharedConn->getDatabasePlatform()->getName() == "oracle") {
                static::$_sharedConn->executeQuery('SELECT 1 /*' . get_class($this) . '*/ FROM dual');
            }
        }

        if ( ! $this->_em) {
            $this->_em = $this->_getEntityManager();
            $this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        }

        $classes = array();

        foreach ($this->_usedModelSets as $setName => $bool) {
            if ( ! isset(static::$_tablesCreated[$setName])/* || $forceCreateTables*/) {
                foreach (static::$_modelSets[$setName] as $className) {
                    $classes[] = $this->_em->getClassMetadata($className);
                }

                static::$_tablesCreated[$setName] = true;
            }
        }

        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }

        $this->_sqlLoggerStack->enabled = true;
    }

    /**
     * Gets an EntityManager for testing purposes.
     *
     * @param \Doctrine\ORM\Configuration   $config       The Configuration to pass to the EntityManager.
     * @param \Doctrine\Common\EventManager $eventManager The EventManager to pass to the EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function _getEntityManager($config = null, $eventManager = null) {
        // NOTE: Functional tests use their own shared metadata cache, because
        // the actual database platform used during execution has effect on some
        // metadata mapping behaviors (like the choice of the ID generation).
        if (is_null(self::$_metadataCacheImpl)) {
            if (isset($GLOBALS['DOCTRINE_CACHE_IMPL'])) {
                self::$_metadataCacheImpl = new $GLOBALS['DOCTRINE_CACHE_IMPL'];
            } else {
                self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
            }
        }

        if (is_null(self::$_queryCacheImpl)) {
            self::$_queryCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }

        $this->_sqlLoggerStack = new \Doctrine\DBAL\Logging\DebugStack();
        $this->_sqlLoggerStack->enabled = false;

        //FIXME: two different configs! $conn and the created entity manager have
        // different configs.
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::$_metadataCacheImpl);
        $config->setQueryCacheImpl(self::$_queryCacheImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        $enableSecondLevelCache = getenv('ENABLE_SECOND_LEVEL_CACHE');

        if ($this->isSecondLevelCacheEnabled || $enableSecondLevelCache) {

            $cacheConfig    = new \Doctrine\ORM\Cache\CacheConfiguration();
            $cache          = $this->getSharedSecondLevelCacheDriverImpl();
            $factory        = new DefaultCacheFactory($cacheConfig->getRegionsConfiguration(), $cache);

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

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(
            realpath(__DIR__ . '/Models/Cache'),
            realpath(__DIR__ . '/Models/GeoNames')
        ), true));

        $conn = static::$_sharedConn;
        $conn->getConfiguration()->setSQLLogger($this->_sqlLoggerStack);

        // get rid of more global state
        $evm = $conn->getEventManager();
        foreach ($evm->getListeners() AS $event => $listeners) {
            foreach ($listeners AS $listener) {
                $evm->removeEventListener(array($event), $listener);
            }
        }

        if ($enableSecondLevelCache) {
            $evm->addEventListener('loadClassMetadata', new CacheMetadataListener());
        }

        if (isset($GLOBALS['db_event_subscribers'])) {
            foreach (explode(",", $GLOBALS['db_event_subscribers']) AS $subscriberClass) {
                $subscriberInstance = new $subscriberClass();
                $evm->addEventSubscriber($subscriberInstance);
            }
        }

        if (isset($GLOBALS['debug_uow_listener'])) {
            $evm->addEventListener(array('onFlush'), new \Doctrine\ORM\Tools\DebugUnitOfWorkListener());
        }

        return \Doctrine\ORM\EntityManager::create($conn, $config);
    }

    /**
     * @param \Exception $e
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function onNotSuccessfulTest(\Exception $e)
    {
        if ($e instanceof \PHPUnit_Framework_AssertionFailedError) {
            throw $e;
        }

        if(isset($this->_sqlLoggerStack->queries) && count($this->_sqlLoggerStack->queries)) {
            $queries = "";
            for($i = count($this->_sqlLoggerStack->queries)-1; $i > max(count($this->_sqlLoggerStack->queries)-25, 0) && isset($this->_sqlLoggerStack->queries[$i]); $i--) {
                $query = $this->_sqlLoggerStack->queries[$i];
                $params = array_map(function($p) { if (is_object($p)) return get_class($p); else return "'".$p."'"; }, $query['params'] ?: array());
                $queries .= ($i+1).". SQL: '".$query['sql']."' Params: ".implode(", ", $params).PHP_EOL;
            }

            $trace = $e->getTrace();
            $traceMsg = "";
            foreach($trace AS $part) {
                if(isset($part['file'])) {
                    if(strpos($part['file'], "PHPUnit/") !== false) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= $part['file'].":".$part['line'].PHP_EOL;
                }
            }

            $message = "[".get_class($e)."] ".$e->getMessage().PHP_EOL.PHP_EOL."With queries:".PHP_EOL.$queries.PHP_EOL."Trace:".PHP_EOL.$traceMsg;

            throw new \Exception($message, (int)$e->getCode(), $e);
        }
        throw $e;
    }

    public function assertSQLEquals($expectedSql, $actualSql)
    {
        return $this->assertEquals(strtolower($expectedSql), strtolower($actualSql), "Lowercase comparison of SQL statements failed.");
    }

    /**
     * Using the SQL Logger Stack this method retrieves the current query count executed in this test.
     *
     * @return int
     */
    protected function getCurrentQueryCount()
    {
        return count($this->_sqlLoggerStack->queries);
    }

    /**
     * Configures DBAL types required in tests
     */
    protected function setUpDBALTypes()
    {
        if (Type::hasType('rot13')) {
            Type::overrideType('rot13', 'Doctrine\Tests\DbalTypes\Rot13Type');
        } else {
            Type::addType('rot13', 'Doctrine\Tests\DbalTypes\Rot13Type');
        }
    }
}

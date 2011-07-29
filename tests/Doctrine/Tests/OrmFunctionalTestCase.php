<?php

namespace Doctrine\Tests;

/**
 * Base testcase class for all functional ORM testcases.
 *
 * @since 2.0
 */
abstract class OrmFunctionalTestCase extends OrmTestCase
{
    /* The metadata cache shared between all functional tests. */
    private static $_metadataCacheImpl = null;
    /* The query cache shared between all functional tests. */
    private static $_queryCacheImpl = null;

    /* Shared connection when a TestCase is run alone (outside of it's functional suite) */
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

    /** The names of the model sets used in this testcase. */
    protected $_usedModelSets = array();

    /** Whether the database schema has already been created. */
    protected static $_tablesCreated = array();

    /** List of model sets and their classes. */
    protected static $_modelSets = array(
        'cms' => array(
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
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
    );

    protected function useModelSet($setName)
    {
        $this->_usedModelSets[$setName] = true;
    }
    
    /**
     * Sweeps the database tables and clears the EntityManager.
     */
    protected function tearDown()
    {
        $conn = static::$_sharedConn;

        $this->_sqlLoggerStack->enabled = false;

        if (isset($this->_usedModelSets['cms'])) {
            $conn->executeUpdate('DELETE FROM cms_users_groups');
            $conn->executeUpdate('DELETE FROM cms_groups');
            $conn->executeUpdate('DELETE FROM cms_addresses');
            $conn->executeUpdate('DELETE FROM cms_phonenumbers');
            $conn->executeUpdate('DELETE FROM cms_comments');
            $conn->executeUpdate('DELETE FROM cms_articles');
            $conn->executeUpdate('DELETE FROM cms_users');
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
            $conn->executeUpdate('DELETE FROM ' . $this->_em->getConnection()->getDatabasePlatform()->quoteIdentifier("file"));
            // MySQL doesnt know deferred deletions therefore only executing the second query gives errors.
            $conn->executeUpdate('DELETE FROM Directory WHERE parentDirectory_id IS NOT NULL');
            $conn->executeUpdate('DELETE FROM Directory');
        }
        if (isset($this->_usedModelSets['ddc117'])) {
            return;
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

        $this->_em->clear();
    }

    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     */
    protected function setUp()
    {
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
     * @param Configuration $config The Configuration to pass to the EntityManager.
     * @param EventManager $eventManager The EventManager to pass to the EntityManager.
     * @return EntityManager
     */
    protected function _getEntityManager($config = null, $eventManager = null) {
        // NOTE: Functional tests use their own shared metadata cache, because
        // the actual database platform used during execution has effect on some
        // metadata mapping behaviors (like the choice of the ID generation).
        if (is_null(self::$_metadataCacheImpl)) {
            self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
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

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());
        
        $conn = static::$_sharedConn;
        $conn->getConfiguration()->setSQLLogger($this->_sqlLoggerStack);

        // get rid of more global state
        $evm = $conn->getEventManager();
        foreach ($evm->getListeners() AS $event => $listeners) {
            foreach ($listeners AS $listener) {
                $evm->removeEventListener(array($event), $listener);
            }
        }
        
        if (isset($GLOBALS['db_event_subscribers'])) {
            foreach (explode(",", $GLOBALS['db_event_subscribers']) AS $subscriberClass) {
                $subscriberInstance = new $subscriberClass();
                $evm->addEventSubscriber($subscriberInstance);
            }
        }
        
        return \Doctrine\ORM\EntityManager::create($conn, $config);
    }

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

    /**
     * Using the SQL Logger Stack this method retrieves the current query count executed in this test.
     * 
     * @return int
     */
    protected function getCurrentQueryCount()
    {
        return count($this->_sqlLoggerStack->queries);
    }
}

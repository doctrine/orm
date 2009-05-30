<?php

namespace Doctrine\Tests;

/**
 * Base testcase class for all functional ORM testcases.
 *
 * @since 2.0
 */
class OrmFunctionalTestCase extends OrmTestCase
{
    /* The metadata cache shared between all functional tests. */
    private static $_metadataCacheImpl = null;
    /* The query cache shared between all functional tests. */
    private static $_queryCacheImpl = null;

    /* Shared connection when a TestCase is run alone (outside of it's functional suite) */
    private static $_sharedConn;
    
    /** The EntityManager for this testcase. */
    protected $_em;

    /** The SchemaTool. */
    protected $_schemaTool;

    /** The names of the model sets used in this testcase. */
    private $_usedModelSets = array();

    /** Whether the database schema has already been created. */
    private static $_tablesCreated = array();

    /** List of model sets and their classes. */
    private static $_modelSets = array(
        'cms' => array(
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
            'Doctrine\Tests\Models\CMS\CmsGroup',
            'Doctrine\Tests\Models\CMS\CmsArticle'
        ),
        'forum' => array(),
        'company' => array(
            'Doctrine\Tests\Models\Company\CompanyPerson',
            'Doctrine\Tests\Models\Company\CompanyEmployee',
            'Doctrine\Tests\Models\Company\CompanyManager'
        ),
        'ecommerce' => array()
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
        $conn = $this->sharedFixture['conn'];
        if (isset($this->_usedModelSets['cms'])) {
            $conn->exec('DELETE FROM cms_users_groups');
            $conn->exec('DELETE FROM cms_groups');
            $conn->exec('DELETE FROM cms_addresses');
            $conn->exec('DELETE FROM cms_phonenumbers');
            $conn->exec('DELETE FROM cms_articles');
            $conn->exec('DELETE FROM cms_users');
        }
        if (isset($this->_usedModelSets['company'])) {
            $conn->exec('DELETE FROM company_managers');
            $conn->exec('DELETE FROM company_employees');
            $conn->exec('DELETE FROM company_persons');
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
        
        if ( ! isset($this->sharedFixture['conn'])) {
            if ( ! isset(self::$_sharedConn)) {
                self::$_sharedConn = TestUtil::getConnection();
            }
            $this->sharedFixture['conn'] = self::$_sharedConn;
            if ($this->sharedFixture['conn']->getDriver() instanceof \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
                $forceCreateTables = true;
            }
        }
        if ( ! $this->_em) {
            $this->_em = $this->_getEntityManager();
            $this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        }

        $classes = array();
        foreach ($this->_usedModelSets as $setName => $bool) {
            if ( ! isset(self::$_tablesCreated[$setName]) || $forceCreateTables) {
                foreach (self::$_modelSets[$setName] as $className) {
                    $classes[] = $this->_em->getClassMetadata($className);
                }
                self::$_tablesCreated[$setName] = true;
            }
        }
        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }
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
            self::$_metadataCacheImpl = new \Doctrine\ORM\Cache\ArrayCache;
        }
        if (is_null(self::$_queryCacheImpl)) {
        	self::$_queryCacheImpl = new \Doctrine\ORM\Cache\ArrayCache;
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::$_metadataCacheImpl);
        $config->setQueryCacheImpl(self::$_queryCacheImpl);
        $eventManager = new \Doctrine\Common\EventManager();
        $conn = $this->sharedFixture['conn'];
        
        return \Doctrine\ORM\EntityManager::create($conn, $config, $eventManager);
    }
}
<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

abstract class UpdateSchemaTestCase extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em = null;

    /**
     *
     * @param  string $fixtureName
     * @return \Doctrine\ORM\Tools\SchemaTool
     */
    protected function _getSchemaTool($fixtureName)
    {
        return $this->_createSchemaTool($fixtureName, $this->_createPlatform());
    }

    abstract protected function _createPlatform();

    private function _createSchemaTool($fixtureName, $platform)
    {
        $fixture = include __DIR__."/DbFixture/".$fixtureName.".php";

        $sm = new UpdateSchemaMock($fixture);

        $this->_em = $this->_getTestEntityManager(null, null, null, false);
        $this->_em->getConnection()->setDatabasePlatform($platform);
        $this->_em->getConnection()->getDriver()->setSchemaManager($sm);

        return new SchemaTool($this->_em);
    }

    /**
     * @param  string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function _getMetadataFor($className)
    {
        return $this->_em->getClassMetadata($className);
    }
}

class UpdateSchemaMock extends \Doctrine\DBAL\Schema\AbstractSchemaManager
{
    private $_fixtureData;

    public function __construct($fixtureData)
    {
        $this->_fixtureData = $fixtureData;
    }

    public function listTables()
    {
        return array_keys($this->_fixtureData);
    }

    public function listTableColumns($tableName)
    {
        return $this->_fixtureData[$tableName];
    }
}
<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../TestInit.php';

class SchemaToolTest extends \Doctrine\Tests\OrmTestCase
{
    public function testGetCreateSchemaSql()
    {
        $driver = new \Doctrine\Tests\Mocks\DriverMock;
        $conn = new \Doctrine\Tests\Mocks\ConnectionMock(array(), $driver);
        $conn->setDatabasePlatform(new \Doctrine\DBAL\Platforms\MySqlPlatform());

        $em = $this->_getTestEntityManager($conn);

        $classes = array(
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
            $em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber'),
        );

        $exporter = new SchemaTool($em);
        $sql = $exporter->getCreateSchemaSql($classes);
        $this->assertEquals(count($sql), 8);
    }
}
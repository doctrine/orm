<?php

namespace Doctrine\Tests\ORM\Export;

use Doctrine\ORM\Export\ClassExporter;

require_once __DIR__ . '/../../TestInit.php';

class ClassExporterTest extends \Doctrine\Tests\OrmTestCase
{
    public function testGetExportClassesSql()
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

        $exporter = new ClassExporter($em);
        $sql = $exporter->getExportClassesSql($classes);
        $this->assertEquals(count($sql), 8);
    }
}
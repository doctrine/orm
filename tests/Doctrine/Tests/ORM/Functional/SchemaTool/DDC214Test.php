<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools;


require_once __DIR__ . '/../../../TestInit.php';

/**
 * WARNING: This test should be run as last test! It can affect others very easily!
 */
class DDC214Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp() {
        parent::setUp();

        $conn = $this->_em->getConnection();

        if (strpos($conn->getDriver()->getName(), "sqlite") !== false) {
            $this->markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }
    }

    /**
     * @group DDC-214
     */
    public function testCmsAddressModel()
    {
        $classes = array(
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
            'Doctrine\Tests\Models\CMS\CmsGroup',
            'Doctrine\Tests\Models\CMS\CmsArticle'
        );

        $this->assertCreatedSchemaNeedsNoUpdates($classes);
    }

    /**
     * @group DDC-214
     */
    public function testCompanyModel()
    {
        $classes = array(
            'Doctrine\Tests\Models\Company\CompanyPerson',
            'Doctrine\Tests\Models\Company\CompanyEmployee',
            'Doctrine\Tests\Models\Company\CompanyManager',
            'Doctrine\Tests\Models\Company\CompanyOrganization',
            'Doctrine\Tests\Models\Company\CompanyEvent',
            'Doctrine\Tests\Models\Company\CompanyAuction',
            'Doctrine\Tests\Models\Company\CompanyRaffle',
            'Doctrine\Tests\Models\Company\CompanyCar'
        );

        $this->assertCreatedSchemaNeedsNoUpdates($classes);
    }

    public function assertCreatedSchemaNeedsNoUpdates($classes)
    {
        $classMetadata = array();
        foreach ($classes AS $class) {
            $classMetadata[] = $this->_em->getClassMetadata($class);
        }

        $schemaTool = new Tools\SchemaTool($this->_em);
        $schemaTool->dropSchema($classMetadata);
        $schemaTool->createSchema($classMetadata);

        $sm = $this->_em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $schemaTool->getSchemaFromMetadata($classMetadata);

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $sql = $schemaDiff->toSql($this->_em->getConnection()->getDatabasePlatform());
        $this->assertEquals(0, count($sql));
    }
}
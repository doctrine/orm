<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\Tools;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * WARNING: This test should be run as last test! It can affect others very easily!
 */
class DDC214Test extends OrmFunctionalTestCase
{
    private $classes = [];
    private $schemaTool = null;

    public function setUp()
    {
        parent::setUp();

        $conn = $this->_em->getConnection();

        if (strpos($conn->getDriver()->getName(), "sqlite") !== false) {
            $this->markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }
        $this->schemaTool = new Tools\SchemaTool($this->_em);
    }

    /**
     * @group DDC-214
     */
    public function testCmsAddressModel()
    {
        $this->classes = [
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
            'Doctrine\Tests\Models\CMS\CmsGroup',
            'Doctrine\Tests\Models\CMS\CmsArticle',
            'Doctrine\Tests\Models\CMS\CmsEmail',
        ];

        $this->assertCreatedSchemaNeedsNoUpdates($this->classes);
    }

    /**
     * @group DDC-214
     */
    public function testCompanyModel()
    {
        $this->classes = [
            'Doctrine\Tests\Models\Company\CompanyPerson',
            'Doctrine\Tests\Models\Company\CompanyEmployee',
            'Doctrine\Tests\Models\Company\CompanyManager',
            'Doctrine\Tests\Models\Company\CompanyOrganization',
            'Doctrine\Tests\Models\Company\CompanyEvent',
            'Doctrine\Tests\Models\Company\CompanyAuction',
            'Doctrine\Tests\Models\Company\CompanyRaffle',
            'Doctrine\Tests\Models\Company\CompanyCar'
        ];

        $this->assertCreatedSchemaNeedsNoUpdates($this->classes);
    }

    public function assertCreatedSchemaNeedsNoUpdates($classes)
    {
        $classMetadata = [];
        foreach ($classes AS $class) {
            $classMetadata[] = $this->_em->getClassMetadata($class);
        }

        try {
            $this->schemaTool->createSchema($classMetadata);
        } catch(\Exception $e) {
            // was already created
        }

        $sm = $this->_em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema = $this->schemaTool->getSchemaFromMetadata($classMetadata);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $sql = $schemaDiff->toSql($this->_em->getConnection()->getDatabasePlatform());
        $sql = array_filter($sql, function($sql) { return strpos($sql, 'DROP') === false; });

        $this->assertEquals(0, count($sql), "SQL: " . implode(PHP_EOL, $sql));
    }
}

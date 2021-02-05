<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\Tools;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

use function array_filter;
use function count;
use function implode;
use function strpos;

use const PHP_EOL;

/**
 * WARNING: This test should be run as last test! It can affect others very easily!
 */
class DDC214Test extends OrmFunctionalTestCase
{
    private $classes    = [];
    private $schemaTool = null;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = $this->_em->getConnection();

        if (strpos($conn->getDriver()->getName(), 'sqlite') !== false) {
            $this->markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }

        $this->schemaTool = new Tools\SchemaTool($this->_em);
    }

    /**
     * @group DDC-214
     */
    public function testCmsAddressModel(): void
    {
        $this->classes = [
            Models\CMS\CmsUser::class,
            Models\CMS\CmsPhonenumber::class,
            Models\CMS\CmsAddress::class,
            Models\CMS\CmsGroup::class,
            Models\CMS\CmsArticle::class,
            Models\CMS\CmsEmail::class,
        ];

        $this->assertCreatedSchemaNeedsNoUpdates($this->classes);
    }

    /**
     * @group DDC-214
     */
    public function testCompanyModel(): void
    {
        $this->classes = [
            Models\Company\CompanyPerson::class,
            Models\Company\CompanyEmployee::class,
            Models\Company\CompanyManager::class,
            Models\Company\CompanyOrganization::class,
            Models\Company\CompanyEvent::class,
            Models\Company\CompanyAuction::class,
            Models\Company\CompanyRaffle::class,
            Models\Company\CompanyCar::class,
        ];

        $this->assertCreatedSchemaNeedsNoUpdates($this->classes);
    }

    public function assertCreatedSchemaNeedsNoUpdates($classes): void
    {
        $classMetadata = [];
        foreach ($classes as $class) {
            $classMetadata[] = $this->_em->getClassMetadata($class);
        }

        try {
            $this->schemaTool->createSchema($classMetadata);
        } catch (Exception $e) {
            // was already created
        }

        $sm = $this->_em->getConnection()->getSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema   = $this->schemaTool->getSchemaFromMetadata($classMetadata);

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        $sql = $schemaDiff->toSql($this->_em->getConnection()->getDatabasePlatform());
        $sql = array_filter($sql, static function ($sql) {
            return strpos($sql, 'DROP') === false;
        });

        $this->assertEquals(0, count($sql), 'SQL: ' . implode(PHP_EOL, $sql));
    }
}

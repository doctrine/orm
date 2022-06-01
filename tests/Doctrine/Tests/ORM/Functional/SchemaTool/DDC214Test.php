<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\Models\Company\CompanyOrganization;
use Doctrine\Tests\Models\Company\CompanyEvent;
use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\Company\CompanyRaffle;
use Doctrine\Tests\Models\Company\CompanyCar;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Tools;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

use function array_filter;
use function implode;
use function str_contains;

use const PHP_EOL;

/**
 * WARNING: This test should be run as last test! It can affect others very easily!
 */
class DDC214Test extends OrmFunctionalTestCase
{
    /** @psalm-var list<class-string> */
    private array $classes = [];

    private ?SchemaTool $schemaTool = null;

    protected function setUp(): void
    {
        parent::setUp();

        $conn = $this->_em->getConnection();

        if ($conn->getDatabasePlatform() instanceof SqlitePlatform) {
            self::markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }

        $this->schemaTool = new SchemaTool($this->_em);
    }

    /**
     * @group DDC-214
     */
    public function testCmsAddressModel(): void
    {
        $this->classes = [
            CmsUser::class,
            CmsPhonenumber::class,
            CmsAddress::class,
            CmsGroup::class,
            CmsArticle::class,
            CmsEmail::class,
        ];

        $this->assertCreatedSchemaNeedsNoUpdates($this->classes);
    }

    /**
     * @group DDC-214
     */
    public function testCompanyModel(): void
    {
        $this->classes = [
            CompanyPerson::class,
            CompanyEmployee::class,
            CompanyManager::class,
            CompanyOrganization::class,
            CompanyEvent::class,
            CompanyAuction::class,
            CompanyRaffle::class,
            CompanyCar::class,
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
        } catch (Exception) {
            // was already created
        }

        $sm = $this->createSchemaManager();

        $fromSchema = $sm->createSchema();
        $toSchema   = $this->schemaTool->getSchemaFromMetadata($classMetadata);
        $comparator = $sm->createComparator();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);

        $sql = $schemaDiff->toSql($this->_em->getConnection()->getDatabasePlatform());
        $sql = array_filter($sql, static fn($sql) => ! str_contains($sql, 'DROP'));

        self::assertCount(0, $sql, 'SQL: ' . implode(PHP_EOL, $sql));
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\ComparatorConfig;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function array_filter;
use function class_exists;
use function implode;
use function str_contains;

use const PHP_EOL;

/**
 * WARNING: This test should be run as last test! It can affect others very easily!
 */
class DDC214Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $conn = $this->_em->getConnection();

        if ($conn->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }
    }

    #[Group('DDC-214')]
    public function testCmsAddressModel(): void
    {
        $this->assertCreatedSchemaNeedsNoUpdates(
            Models\CMS\CmsUser::class,
            Models\CMS\CmsPhonenumber::class,
            Models\CMS\CmsAddress::class,
            Models\CMS\CmsGroup::class,
            Models\CMS\CmsArticle::class,
            Models\CMS\CmsEmail::class,
        );
    }

    #[Group('DDC-214')]
    public function testCompanyModel(): void
    {
        $this->assertCreatedSchemaNeedsNoUpdates(
            Models\Company\CompanyPerson::class,
            Models\Company\CompanyEmployee::class,
            Models\Company\CompanyManager::class,
            Models\Company\CompanyOrganization::class,
            Models\Company\CompanyEvent::class,
            Models\Company\CompanyAuction::class,
            Models\Company\CompanyRaffle::class,
            Models\Company\CompanyCar::class,
        );
    }

    /** @param class-string ...$classes */
    public function assertCreatedSchemaNeedsNoUpdates(string ...$classes): void
    {
        $this->createSchemaForModels(...$classes);

        $sm = $this->createSchemaManager();

        $fromSchema = $sm->introspectSchema();
        $toSchema   = $this->getSchemaForModels(...$classes);

        if (class_exists(ComparatorConfig::class)) {
            $comparator = $sm->createComparator((new ComparatorConfig())->withReportModifiedIndexes(false));
        } else {
            $comparator = $sm->createComparator();
        }

        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);

        $sql = $this->_em->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);

        $sql = array_filter($sql, static fn ($sql) => ! str_contains($sql, 'DROP'));

        self::assertCount(0, $sql, 'SQL: ' . implode(PHP_EOL, $sql));
    }
}

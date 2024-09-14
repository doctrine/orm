<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function implode;
use function method_exists;
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

        if ($conn->getDatabasePlatform() instanceof SqlitePlatform) {
            self::markTestSkipped('SQLite does not support ALTER TABLE statements.');
        }
    }

    /** @group DDC-214 */
    public function testCmsAddressModel(): void
    {
        $this->assertCreatedSchemaNeedsNoUpdates(
            Models\CMS\CmsUser::class,
            Models\CMS\CmsPhonenumber::class,
            Models\CMS\CmsAddress::class,
            Models\CMS\CmsGroup::class,
            Models\CMS\CmsArticle::class,
            Models\CMS\CmsEmail::class
        );
    }

    /** @group DDC-214 */
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
            Models\Company\CompanyCar::class
        );
    }

    /** @param class-string ...$classes */
    public function assertCreatedSchemaNeedsNoUpdates(string ...$classes): void
    {
        $this->createSchemaForModels(...$classes);

        $sm = $this->createSchemaManager();

        $method     = method_exists(AbstractSchemaManager::class, 'introspectSchema') ?
            'introspectSchema' :
            'createSchema';
        $fromSchema = $sm->$method();
        $toSchema   = $this->getSchemaForModels(...$classes);

        if (method_exists($sm, 'createComparator')) {
            $comparator = $sm->createComparator();
        } else {
            $comparator = new Comparator();
        }

        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);

        $sql = method_exists(AbstractPlatform::class, 'getAlterSchemaSQL') ?
            $this->_em->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff) :
            $schemaDiff->toSql($this->_em->getConnection()->getDatabasePlatform());

        $sql = array_filter($sql, static function ($sql) {
            return ! str_contains($sql, 'DROP');
        });

        self::assertCount(0, $sql, 'SQL: ' . implode(PHP_EOL, $sql));
    }
}

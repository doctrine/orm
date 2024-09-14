<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\Models;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function implode;
use function str_starts_with;

class PostgreSqlSchemaToolTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        if (! $platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of postgresql.');
        }
    }

    public function testPostgresMetadataSequenceIncrementedBy10(): void
    {
        $address = $this->_em->getClassMetadata(Models\CMS\CmsAddress::class);

        self::assertEquals(1, $address->sequenceGeneratorDefinition['allocationSize']);
    }

    /** @group DDC-1657 */
    public function testUpdateSchemaWithPostgreSQLSchema(): void
    {
        $sql = $this->getUpdateSchemaSqlForModels(
            DDC1657Screen::class,
            DDC1657Avatar::class
        );
        $sql = array_filter($sql, static function ($sql) {
            return str_starts_with($sql, 'DROP SEQUENCE stonewood.');
        });

        self::assertCount(0, $sql, implode("\n", $sql));
    }
}

/**
 * @Entity
 * @Table(name="stonewood.screen")
 */
class DDC1657Screen
{
    /**
     * Identifier
     *
     * @var int
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(name="pk", type="integer", nullable=false)
     */
    private $pk;

    /**
     * Title
     *
     * @var string
     * @Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * Path
     *
     * @var string
     * @Column(name="path", type="string", length=255, nullable=false)
     */
    private $path;

    /**
     * Register date
     *
     * @var Date
     * @Column(name="ddate", type="date", nullable=false)
     */
    private $ddate;

    /**
     * Avatar
     *
     * @var Stonewood\Model\Entity\Avatar
     * @ManyToOne(targetEntity="DDC1657Avatar")
     * @JoinColumn(name="pk_avatar", referencedColumnName="pk", nullable=true, onDelete="CASCADE")
     */
    private $avatar;
}

/**
 * @Entity
 * @Table(name="stonewood.avatar")
 */
class DDC1657Avatar
{
    /**
     * Identifier
     *
     * @var int
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(name="pk", type="integer", nullable=false)
     */
    private $pk;
}

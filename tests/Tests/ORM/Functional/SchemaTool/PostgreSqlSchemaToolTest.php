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
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

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

    #[Group('DDC-1657')]
    public function testUpdateSchemaWithPostgreSQLSchema(): void
    {
        $sql = $this->getUpdateSchemaSqlForModels(
            DDC1657Screen::class,
            DDC1657Avatar::class,
        );
        $sql = array_filter($sql, static fn ($sql) => str_starts_with($sql, 'DROP SEQUENCE stonewood.'));

        self::assertCount(0, $sql, implode("\n", $sql));
    }
}

#[Table(name: 'stonewood.screen')]
#[Entity]
class DDC1657Screen
{
    /**
     * Identifier
     */
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    #[Column(name: 'pk', type: 'integer', nullable: false)]
    private int $pk;

    /**
     * Title
     */
    #[Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private string $title;

    /**
     * Path
     */
    #[Column(name: 'path', type: 'string', length: 255, nullable: false)]
    private string $path;

    /**
     * Register date
     *
     * @var Date
     */
    #[Column(name: 'ddate', type: 'date', nullable: false)]
    private $ddate;

    /**
     * Avatar
     *
     * @var Stonewood\Model\Entity\Avatar
     */
    #[ManyToOne(targetEntity: 'DDC1657Avatar')]
    #[JoinColumn(name: 'pk_avatar', referencedColumnName: 'pk', nullable: true, onDelete: 'CASCADE')]
    private $avatar;
}

#[Table(name: 'stonewood.avatar')]
#[Entity]
class DDC1657Avatar
{
    /**
     * Identifier
     */
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    #[Column(name: 'pk', type: 'integer', nullable: false)]
    private int $pk;
}

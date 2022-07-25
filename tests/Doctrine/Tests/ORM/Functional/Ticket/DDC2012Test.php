<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function explode;
use function implode;
use function is_array;
use function method_exists;
use function sprintf;
use function strtolower;

/**
 * @group DDC-2012
 * @group non-cacheable
 */
class DDC2012Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Type::addType(DDC2012TsVectorType::MYTYPE, DDC2012TsVectorType::class);

        DDC2012TsVectorType::$calls = [];

        $this->createSchemaForModels(
            DDC2012Item::class,
            DDC2012ItemPerson::class,
        );
    }

    public function testIssue(): void
    {
        $item      = new DDC2012ItemPerson();
        $item->tsv = ['word1', 'word2', 'word3'];

        $this->_em->persist($item);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find($item::class, $item->id);

        self::assertArrayHasKey('convertToDatabaseValueSQL', DDC2012TsVectorType::$calls);
        self::assertArrayHasKey('convertToDatabaseValue', DDC2012TsVectorType::$calls);
        self::assertArrayHasKey('convertToPHPValue', DDC2012TsVectorType::$calls);

        self::assertCount(1, DDC2012TsVectorType::$calls['convertToDatabaseValueSQL']);
        self::assertCount(1, DDC2012TsVectorType::$calls['convertToDatabaseValue']);
        self::assertCount(1, DDC2012TsVectorType::$calls['convertToPHPValue']);

        self::assertInstanceOf(DDC2012Item::class, $item);
        self::assertEquals(['word1', 'word2', 'word3'], $item->tsv);

        $item->tsv = ['word1', 'word2'];

        $this->_em->persist($item);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find($item::class, $item->id);

        self::assertCount(2, DDC2012TsVectorType::$calls['convertToDatabaseValueSQL']);
        self::assertCount(2, DDC2012TsVectorType::$calls['convertToDatabaseValue']);
        self::assertCount(2, DDC2012TsVectorType::$calls['convertToPHPValue']);

        self::assertInstanceOf(DDC2012Item::class, $item);
        self::assertEquals(['word1', 'word2'], $item->tsv);
    }
}

/**
 * @Table(name="ddc2010_item")
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type_id", type="smallint")
 * @DiscriminatorMap({
 *      1 = "DDC2012ItemPerson",
 *      2 = "DDC2012Item"
 * })
 */
class DDC2012Item
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @psalm-var list<string>
     * @Column(name="tsv", type="tsvector", length=255, nullable=true)
     */
    public $tsv;
}

/**
 * @Table(name="ddc2010_item_person")
 * @Entity
 */
class DDC2012ItemPerson extends DDC2012Item
{
}

class DDC2012TsVectorType extends Type
{
    public const MYTYPE = 'tsvector';

    /** @psalm-var array<string, list<array{value: mixed, platform: AbstractPlatform}>> */
    public static $calls = [];

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (method_exists($platform, 'getStringTypeDeclarationSQL')) {
            return $platform->getStringTypeDeclarationSQL($column);
        }

        return $platform->getVarcharTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        self::$calls[__FUNCTION__][] = [
            'value'     => $value,
            'platform'  => $platform,
        ];

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<string>
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        self::$calls[__FUNCTION__][] = [
            'value'     => $value,
            'platform'  => $platform,
        ];

        return explode(' ', strtolower($value));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        self::$calls[__FUNCTION__][] = [
            'sqlExpr'   => $sqlExpr,
            'platform'  => $platform,
        ];

        // changed to upper expression to keep the test compatible with other Databases
        //sprintf('to_tsvector(%s)', $sqlExpr);

        return sprintf('UPPER(%s)', $sqlExpr);
    }

    public function getName(): string
    {
        return self::MYTYPE;
    }
}

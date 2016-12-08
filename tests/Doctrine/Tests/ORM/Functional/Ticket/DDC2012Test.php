<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @group DDC-2012
 * @group non-cacheable
 */
class DDC2012Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        Type::addType(DDC2012TsVectorType::MYTYPE, DDC2012TsVectorType::class);

        DDC2012TsVectorType::$calls = [];

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC2012Item::class),
            $this->_em->getClassMetadata(DDC2012ItemPerson::class),
            ]
        );
    }

    public function testIssue()
    {
        $item       = new DDC2012ItemPerson();
        $item->tsv  = ['word1', 'word2', 'word3'];

        $this->_em->persist($item);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find(get_class($item), $item->id);

        $this->assertArrayHasKey('convertToDatabaseValueSQL', DDC2012TsVectorType::$calls);
        $this->assertArrayHasKey('convertToDatabaseValue', DDC2012TsVectorType::$calls);
        $this->assertArrayHasKey('convertToPHPValue', DDC2012TsVectorType::$calls);

        $this->assertCount(1, DDC2012TsVectorType::$calls['convertToDatabaseValueSQL']);
        $this->assertCount(1, DDC2012TsVectorType::$calls['convertToDatabaseValue']);
        $this->assertCount(1, DDC2012TsVectorType::$calls['convertToPHPValue']);

        $this->assertInstanceOf(DDC2012Item::class, $item);
        $this->assertEquals(['word1', 'word2', 'word3'], $item->tsv);


        $item->tsv = ['word1', 'word2'];

        $this->_em->persist($item);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find(get_class($item), $item->id);

        $this->assertCount(2, DDC2012TsVectorType::$calls['convertToDatabaseValueSQL']);
        $this->assertCount(2, DDC2012TsVectorType::$calls['convertToDatabaseValue']);
        $this->assertCount(2, DDC2012TsVectorType::$calls['convertToPHPValue']);

        $this->assertInstanceOf(DDC2012Item::class, $item);
        $this->assertEquals(['word1', 'word2'], $item->tsv);
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
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(name="tsv", type="tsvector", nullable=true)
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
    const MYTYPE = 'tsvector';

    public static $calls = [];

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_array($value)) {
            $value = implode(" ", $value);
        }

        self::$calls[__FUNCTION__][] = [
            'value'     => $value,
            'platform'  => $platform,
        ];

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        self::$calls[__FUNCTION__][] = [
            'value'     => $value,
            'platform'  => $platform,
        ];

        return explode(" ", strtolower($value));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        self::$calls[__FUNCTION__][] = [
            'sqlExpr'   => $sqlExpr,
            'platform'  => $platform,
        ];

        // changed to upper expression to keep the test compatible with other Databases
        //sprintf('to_tsvector(%s)', $sqlExpr);

        return $platform->getUpperExpression($sqlExpr);
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::MYTYPE;
    }
}

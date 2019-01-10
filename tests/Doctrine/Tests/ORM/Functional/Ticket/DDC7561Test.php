<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * @group DDC-7561
 */
class DDC7561Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        Type::addType(
            DDC7561NonNullableValueType::class,
            DDC7561NonNullableValueType::class
        );

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC7561AbstractOrder::class),
                $this->_em->getClassMetadata(DDC7561CardOrder::class),
                $this->_em->getClassMetadata(DDC7561WithdrawOrder::class),
            ]
        );
    }

    public function testExpectsDDC7561CardOrderFetching()
    {
        $expectedOrder = new DDC7561CardOrder(1, '1000');

        $this->_em->persist($expectedOrder);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC7561AbstractOrder::class);

        $actualOrder = $repository->find(1);

        $this->assertEquals($expectedOrder, $actualOrder);
    }
}

/**
 * @Entity
 * @Table(name="ddc_7561_orders")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "card" = "DDC7561CardOrder",
 *     "withdraw" = "DDC7561WithdrawOrder"
 * })
 */
abstract class DDC7561AbstractOrder {
    /**
     * @Id()
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

/**
 * @Entity
 * @Table(name="ddc_7561_card_orders")
 */
class DDC7561CardOrder extends DDC7561AbstractOrder {
}

/**
 * @Entity
 * @Table(name="ddc_7561_withdraw_orders")
 */
class DDC7561WithdrawOrder extends DDC7561AbstractOrder {
    /**
     * @var DDC7561NonNullableValue
     * @Column(type="Doctrine\Tests\ORM\Functional\Ticket\DDC7561NonNullableValueType")
     */
    private $nonNullableValue;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        parent::__construct($id);
        $this->nonNullableValue = new DDC7561NonNullableValue('pending');
    }
}

class DDC7561NonNullableValueType extends Type
{
    /**
     * @return string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return $value;
    }

    /**
     * @return DDC7561NonNullableValue
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): DDC7561NonNullableValue
    {
        return new DDC7561NonNullableValue($value);
    }

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::class;
    }
}

class DDC7561NonNullableValue {
    /**
     * @var string
     */
    private $value;

    /**
     * DDC7561ConfirmationStatus constructor.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
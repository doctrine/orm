<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * @group DDC-1707
 */
class DDC7561Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        Type::addType(
            'ddc_7561_withdraw_order_confirmation_status',
            DDC7561DDC7561ConfirmationStatusType::class
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
        $expectedOrder = new DDC7561CardOrder(1, '1000', 'USD');

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
     * @GeneratedValue()
     * @Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * DDC7561AbstractOrder constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}

/**
 * @Entity
 * @Table(name="ddc_7561_card_orders")
 */
class DDC7561CardOrder extends DDC7561AbstractOrder {
    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $currencyIsoCode;

    /**
     * DDC7561CardOrder constructor.
     * @param int $id
     * @param string $amount
     * @param string $currencyIsoCode
     */
    public function __construct(int $id, string $amount, string $currencyIsoCode)
    {
        parent::__construct($id);

        $this->amount = $amount;
        $this->currencyIsoCode = $currencyIsoCode;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrencyIsoCode(): string
    {
        return $this->currencyIsoCode;
    }
}

/**
 * @Entity
 * @Table(name="ddc_7561_withdraw_orders")
 */
class DDC7561WithdrawOrder extends DDC7561AbstractOrder {
    /**
     * @Column(type="decimal", precision=10, scale=2)
     *
     * @var string
     */
    private $amount;

    /**
     * @Column(type="string", length=3)
     * @var string
     */
    private $currencyIsoCode;

    /**
     * @var DDC7561ConfirmationStatus
     * @Column(type="ddc_7561_withdraw_order_confirmation_status")
     */
    private $DDC7561ConfirmationStatus;

    /**
     * DDC7561WithdrawOrder constructor.
     * @param int $id
     * @param string $amount
     * @param string $currencyIsoCode
     */
    public function __construct(int $id, string $amount, string $currencyIsoCode)
    {
        parent::__construct($id);
        $this->amount = $amount;
        $this->currencyIsoCode = $currencyIsoCode;
        $this->DDC7561ConfirmationStatus = new DDC7561ConfirmationStatus('pending');
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrencyIsoCode(): string
    {
        return $this->currencyIsoCode;
    }

    /**
     * @return DDC7561ConfirmationStatus
     */
    public function getDDC7561ConfirmationStatus(): DDC7561ConfirmationStatus
    {
        return $this->DDC7561ConfirmationStatus;
    }
}

class DDC7561DDC7561ConfirmationStatusType extends Type
{
    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return string
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if (!$value instanceof DDC7561ConfirmationStatus) {
            throw new ConversionException(\sprintf(
                'Value must be instance of "%s", instance "%s" given',
                DDC7561ConfirmationStatus::class,
                \is_object($value) ? \get_class($value) : \gettype($value)
            ));
        }

        /* @var DDC7561ConfirmationStatus $value */
        return $value->getValue();
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return DDC7561ConfirmationStatus
     *
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): DDC7561ConfirmationStatus
    {
        try {
            return new DDC7561ConfirmationStatus($value);
        } catch (\Throwable $e) {
            throw new ConversionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array            $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'ddc_7561_withdraw_order_confirmation_status';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'ddc_7561_withdraw_order_confirmation_status';
    }
}

class DDC7561ConfirmationStatus {
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
        if (\in_array($value, ['pending', 'approved', 'declined'])) {
            throw new \InvalidArgumentException();
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
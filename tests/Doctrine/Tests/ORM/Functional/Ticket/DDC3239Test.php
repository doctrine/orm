<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3239
 * @group non-cacheable
 */
class DDC3239Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (Type::hasType('ddc3239_currency_code')) {
            $this->fail(
                'Type ddc3239_currency_code exists for testing DDC-3239 only, ' .
                'but it has already been registered for some reason'
            );
        }

        Type::addType('ddc3239_currency_code', __NAMESPACE__ . '\DDC3239CurrencyCode');

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3239Currency::CLASSNAME),
            $this->_em->getClassMetadata(DDC3239Transaction::CLASSNAME),
        ));
    }

    public function testIssue()
    {
        $currency = new DDC3239Currency('BYR');

        $this->_em->persist($currency);
        $this->_em->flush();

        $amount = 50;
        $transaction = new DDC3239Transaction($amount, $currency);

        $this->_em->persist($transaction);
        $this->_em->flush();
        $this->_em->close();

        $fetchedCurrency = $this->_em->find(DDC3239Currency::CLASSNAME, 'BYR');
        $this->assertEquals(1, count($fetchedCurrency->transactions));

    }
}

/**
 * @Table(name="ddc3239_currency")
 * @Entity
 */
class DDC3239Currency
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="ddc3239_currency_code")
     */
    public $code;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC3239Transaction", mappedBy="currency")
     */
    public $transactions;

    public function __construct($code)
    {
        $this->code = $code;
    }
}

/**
 * @Table(name="ddc3239_transaction")
 * @Entity
 */
class DDC3239Transaction
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var int
     *
     * @Column(type="integer")
     */
    public $amount;

    /**
     * @var \Doctrine\Tests\ORM\Functional\Ticket\DDC3239Currency
     *
     * @ManyToOne(targetEntity="DDC3239Currency", inversedBy="transactions")
     * @JoinColumn(name="currency_id", referencedColumnName="code", nullable=false)
     */
    public $currency;

    public function __construct($amount, DDC3239Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }
}

class DDC3239CurrencyCode extends Type
{
    private static $map = array(
        'BYR' => 974,
    );

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getSmallIntTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return self::$map[$value];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return array_search($value, self::$map);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ddc3239_currency_code';
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;

/**
 * @group DDC-2494
 * @group non-cacheable
 */
class DDC3192Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (Type::hasType('ddc3192_currency_code')) {
            $this->fail(
                'Type ddc3192_currency_code exists for testing DDC-3192 only, ' .
                'but it has already been registered for some reason'
            );
        }

        Type::addType('ddc3192_currency_code', __NAMESPACE__ . '\DDC3192CurrencyCode');

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3192Currency::CLASSNAME),
            $this->_em->getClassMetadata(DDC3192Transaction::CLASSNAME),
        ));
    }

    public function testIssue()
    {
        $currency = new DDC3192Currency('BYR');

        $this->_em->persist($currency);
        $this->_em->flush();

        $amount = 50;
        $transaction = new DDC3192Transaction($amount, $currency);

        $this->_em->persist($transaction);
        $this->_em->flush();
        $this->_em->close();

        $resultByPersister = $this->_em->find(DDC3192Transaction::CLASSNAME, $transaction->id);

        // This works: DDC2494 makes persister set type mapping info to ResultSetMapping
        $this->assertEquals('BYR', $resultByPersister->currency->code);

        $this->_em->close();

        $query = $this->_em->createQuery();
        $query->setDQL('SELECT t FROM ' . DDC3192Transaction::CLASSNAME . ' t WHERE t.id = ?1');
        $query->setParameter(1, $transaction->id);

        $resultByQuery = $query->getSingleResult();

        // This is fixed here: before the fix it used to return 974.
        // because unlike the BasicEntityPersister, SQLWalker doesn't set type info
        $this->assertEquals('BYR', $resultByQuery->currency->code);
    }
}

/**
 * @Table(name="ddc3192_currency")
 * @Entity
 */
class DDC3192Currency
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="ddc3192_currency_code")
     */
    public $code;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC3192Transaction", mappedBy="currency")
     */
    public $transactions;

    public function __construct($code)
    {
        $this->code = $code;
    }
}

/**
 * @Table(name="ddc3192_transaction")
 * @Entity
 */
class DDC3192Transaction
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
     * @var \Doctrine\Tests\ORM\Functional\Ticket\DDC3192Currency
     *
     * @ManyToOne(targetEntity="DDC3192Currency", inversedBy="transactions")
     * @JoinColumn(name="currency_id", referencedColumnName="code", nullable=false)
     */
    public $currency;

    public function __construct($amount, DDC3192Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }
}

class DDC3192CurrencyCode extends Type
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
        return 'ddc3192_currency_code';
    }
}

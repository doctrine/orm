<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @group DDC-2494
 */
class DDC2494Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        DDC2494TinyIntType::$calls = array();

        Type::addType('ddc2494_tinyint', __NAMESPACE__ . '\DDC2494TinyIntType');

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC2494Currency::CLASSNAME),
            $this->_em->getClassMetadata(DDC2494Campaign::CLASSNAME),
        ));
    }

    public function testIssue()
    {
        $currency = new DDC2494Currency(1, 2);

        $this->_em->persist($currency);
        $this->_em->flush();

        $campaign = new DDC2494Campaign($currency);

        $this->_em->persist($campaign);
        $this->_em->flush();
        $this->_em->close();

        $this->assertArrayHasKey('convertToDatabaseValue', DDC2494TinyIntType::$calls);
        $this->assertCount(3, DDC2494TinyIntType::$calls['convertToDatabaseValue']);

        $item = $this->_em->find(DDC2494Campaign::CLASSNAME, $campaign->getId());

        $this->assertInstanceOf(DDC2494Campaign::CLASSNAME, $item);
        $this->assertInstanceOf(DDC2494Currency::CLASSNAME, $item->getCurrency());

        $queryCount = $this->getCurrentQueryCount();

        $this->assertInstanceOf('\Doctrine\Common\Proxy\Proxy', $item->getCurrency());
        $this->assertFalse($item->getCurrency()->__isInitialized());

        $this->assertArrayHasKey('convertToPHPValue', DDC2494TinyIntType::$calls);
        $this->assertCount(1, DDC2494TinyIntType::$calls['convertToPHPValue']);

        $this->assertInternalType('integer', $item->getCurrency()->getId());
        $this->assertCount(1, DDC2494TinyIntType::$calls['convertToPHPValue']);
        $this->assertFalse($item->getCurrency()->__isInitialized());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->assertInternalType('integer', $item->getCurrency()->getTemp());
        $this->assertCount(3, DDC2494TinyIntType::$calls['convertToPHPValue']);
        $this->assertTrue($item->getCurrency()->__isInitialized());

        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}

/**
 * @Table(name="ddc2494_currency")
 * @Entity
 */
class DDC2494Currency
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer", type="ddc2494_tinyint")
     */
    protected $id;

    /**
     * @Column(name="temp", type="ddc2494_tinyint", nullable=false)
     */
    protected $temp;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC2494Campaign", mappedBy="currency")
     */
    protected $campaigns;

    public function __construct($id, $temp)
    {
        $this->id   = $id;
        $this->temp = $temp;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTemp()
    {
        return $this->temp;
    }

    public function getCampaigns()
    {
        return $this->campaigns;
    }
}

/**
 * @Table(name="ddc2494_campaign")
 * @Entity
 */
class DDC2494Campaign
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var \Doctrine\Tests\ORM\Functional\Ticket\DDC2494Currency
     *
     * @ManyToOne(targetEntity="DDC2494Currency", inversedBy="campaigns")
     * @JoinColumn(name="currency_id", referencedColumnName="id", nullable=false)
     */
    protected $currency;

    public function __construct(DDC2494Currency $currency)
    {
        $this->currency = $currency;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Doctrine\Tests\ORM\Functional\Ticket\DDC2494Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}

class DDC2494TinyIntType extends Type
{
    public static $calls = array();

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
        $return = (string) $value;

        self::$calls[__FUNCTION__][] = array(
            'value'     => $value,
            'return'    => $return,
            'platform'  => $platform,
        );

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $return = (integer) $value;

        self::$calls[__FUNCTION__][] = array(
            'value'     => $value,
            'return'    => $return,
            'platform'  => $platform,
        );

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ddc2494_tinyint';
    }
}

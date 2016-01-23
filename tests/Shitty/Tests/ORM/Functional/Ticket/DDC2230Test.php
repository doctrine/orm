<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\NotifyPropertyChanged;
use Shitty\Common\PropertyChangedListener;
use Shitty\ORM\Tools\ToolsException;
use Shitty\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2230
 */
class DDC2230Test extends OrmFunctionalTestCase
{
    protected function setup()
    {
        parent::setup();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2230User'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2230Address'),
            ));
        } catch (ToolsException $e) {}
    }

    public function testNotifyTrackingNotCalledOnUninitializedProxies()
    {
        $insertedUser          = new DDC2230User();
        $insertedUser->address = new DDC2230Address();

        $this->_em->persist($insertedUser);
        $this->_em->persist($insertedUser->address);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(__NAMESPACE__ . '\\DDC2230User', $insertedUser->id);

        $this->_em->clear();

        $mergedUser = $this->_em->merge($user);

        /* @var $address \Shitty\Common\Proxy\Proxy */
        $address = $mergedUser->address;

        $this->assertInstanceOf('Doctrine\\ORM\\Proxy\\Proxy', $address);
        $this->assertFalse($address->__isInitialized());
    }

    public function testNotifyTrackingCalledOnProxyInitialization()
    {
        $insertedAddress = new DDC2230Address();

        $this->_em->persist($insertedAddress);
        $this->_em->flush();
        $this->_em->clear();

        $addressProxy = $this->_em->getReference(__NAMESPACE__ . '\\DDC2230Address', $insertedAddress->id);

        /* @var $addressProxy \Shitty\Common\Proxy\Proxy|\Shitty\Tests\ORM\Functional\Ticket\DDC2230Address */
        $this->assertFalse($addressProxy->__isInitialized());
        $this->assertNull($addressProxy->listener);

        $addressProxy->__load();

        $this->assertSame($this->_em->getUnitOfWork(), $addressProxy->listener);
    }
}

/** @Entity */
class DDC2230User
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC2230Address")
     */
    public $address;
}

/**
 * @Entity
 * @ChangeTrackingPolicy("NOTIFY")
 */
class DDC2230Address implements NotifyPropertyChanged
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /**
     * @var \Shitty\Common\PropertyChangedListener
     */
    public $listener;

    /** {@inheritDoc} */
    function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listener = $listener;
    }
}


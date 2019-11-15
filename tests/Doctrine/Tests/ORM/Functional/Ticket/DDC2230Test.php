<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-2230
 */
class DDC2230Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC2230User::class),
                $this->_em->getClassMetadata(DDC2230Address::class),
                ]
            );
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

        $user = $this->_em->find(DDC2230User::class, $insertedUser->id);

        $this->_em->clear();

        $mergedUser = $this->_em->merge($user);

        /* @var $address Proxy */
        $address = $mergedUser->address;

        $this->assertInstanceOf(Proxy::class, $address);
        $this->assertFalse($address->__isInitialized());
        $this->assertHasDeprecationMessages();
    }

    public function testNotifyTrackingCalledOnProxyInitialization()
    {
        $insertedAddress = new DDC2230Address();

        $this->_em->persist($insertedAddress);
        $this->_em->flush();
        $this->_em->clear();

        $addressProxy = $this->_em->getReference(DDC2230Address::class, $insertedAddress->id);

        /* @var $addressProxy Proxy|\Doctrine\Tests\ORM\Functional\Ticket\DDC2230Address */
        $this->assertFalse($addressProxy->__isInitialized());
        $this->assertNull($addressProxy->listener);

        $addressProxy->__load();

        $this->assertSame($this->_em->getUnitOfWork(), $addressProxy->listener);
        $this->assertNotHasDeprecationMessages();
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
     * @var \Doctrine\Common\PropertyChangedListener
     */
    public $listener;

    /** {@inheritDoc} */
    function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listener = $listener;
    }
}


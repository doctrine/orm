<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2230
 */
class DDC2230Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC2230User::class),
                $this->em->getClassMetadata(DDC2230Address::class),
                ]
            );
        } catch (ToolsException $e) {}
    }

    public function testNotifyTrackingNotCalledOnUninitializedProxies()
    {
        $insertedUser          = new DDC2230User();
        $insertedUser->address = new DDC2230Address();

        $this->em->persist($insertedUser);
        $this->em->persist($insertedUser->address);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(DDC2230User::class, $insertedUser->id);

        $this->em->clear();

        $mergedUser = $this->em->merge($user);

        /* @var $address Proxy */
        $address = $mergedUser->address;

        self::assertInstanceOf(Proxy::class, $address);
        self::assertFalse($address->__isInitialized());
    }

    public function testNotifyTrackingCalledOnProxyInitialization()
    {
        $insertedAddress = new DDC2230Address();

        $this->em->persist($insertedAddress);
        $this->em->flush();
        $this->em->clear();

        $addressProxy = $this->em->getReference(DDC2230Address::class, $insertedAddress->id);

        /* @var $addressProxy Proxy|\Doctrine\Tests\ORM\Functional\Ticket\DDC2230Address */
        self::assertFalse($addressProxy->__isInitialized());
        self::assertNull($addressProxy->listener);

        $addressProxy->__load();

        self::assertSame($this->em->getUnitOfWork(), $addressProxy->listener);
    }
}

/** @ORM\Entity */
class DDC2230User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC2230Address")
     */
    public $address;
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("NOTIFY")
 */
class DDC2230Address implements NotifyPropertyChanged
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
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


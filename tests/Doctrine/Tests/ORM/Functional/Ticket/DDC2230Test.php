<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-2230
 */
class DDC2230Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema([
                $this->em->getClassMetadata(DDC2230User::class),
                $this->em->getClassMetadata(DDC2230Address::class),
            ]);
        } catch (ToolsException $e) {}
    }

    public function testNotifyTrackingCalledOnProxyInitialization()
    {
        $insertedAddress = new DDC2230Address();

        $this->em->persist($insertedAddress);
        $this->em->flush();
        $this->em->clear();

        $addressProxy = $this->em->getReference(DDC2230Address::class, $insertedAddress->id);

        /* @var $addressProxy GhostObjectInterface|\Doctrine\Tests\ORM\Functional\Ticket\DDC2230Address */
        self::assertFalse($addressProxy->isProxyInitialized());
        self::assertNull($addressProxy->listener);

        $addressProxy->initializeProxy();

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


<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

#[Group('DDC-2230')]
class DDC2230Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC2230User::class,
            DDC2230Address::class,
        );
    }

    public function testNotifyTrackingCalledOnProxyInitialization(): void
    {
        $insertedAddress = new DDC2230Address();

        $this->_em->persist($insertedAddress);
        $this->_em->flush();
        $this->_em->clear();

        $addressProxy = $this->_em->getReference(DDC2230Address::class, $insertedAddress->id);
        assert($addressProxy instanceof Proxy || $addressProxy instanceof DDC2230Address);

        self::assertFalse($addressProxy->__isInitialized());
        self::assertNull($addressProxy->listener);

        $addressProxy->__load();

        self::assertSame($this->_em->getUnitOfWork(), $addressProxy->listener);
    }
}

#[Entity]
class DDC2230User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var DDC2230Address */
    #[OneToOne(targetEntity: 'DDC2230Address')]
    public $address;
}

#[Entity]
#[ChangeTrackingPolicy('NOTIFY')]
class DDC2230Address implements NotifyPropertyChanged
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var \Doctrine\Common\PropertyChangedListener */
    public $listener;

    /** {@inheritDoc} */
    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->listener = $listener;
    }
}

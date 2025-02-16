<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2692')]
class DDC2692Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC2692Foo::class);

        $this->_em->clear();
    }

    public function testIsListenerCalledOnlyOnceOnPreFlush(): void
    {
        $listener = new class implements EventSubscriber
        {
            /** @var int */
            public $registeredCalls = 0;

            /**
             * {@inheritDoc}
             */
            public function getSubscribedEvents(): array
            {
                return [Events::preFlush];
            }

            public function preFlush(PreFlushEventArgs $args): void
            {
                ++$this->registeredCalls;
            }
        };

        $this->_em->getEventManager()->addEventSubscriber($listener);

        $this->_em->persist(new DDC2692Foo());
        $this->_em->persist(new DDC2692Foo());

        $this->_em->flush();
        $this->_em->clear();

        self::assertSame(1, $listener->registeredCalls);
    }
}
#[Table(name: 'ddc_2692_foo')]
#[Entity]
class DDC2692Foo
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

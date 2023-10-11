<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10869Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10869Entity::class,
        ]);
    }

    public function testPostPersistListenerUpdatingObjectFieldWhileOtherInsertPending(): void
    {
        $entity1 = new GH10869Entity();
        $this->_em->persist($entity1);

        $entity2 = new GH10869Entity();
        $this->_em->persist($entity2);

        $this->_em->getEventManager()->addEventListener(Events::postPersist, new class {
            public function postPersist(PostPersistEventArgs $args): void
            {
                $object = $args->getObject();

                $objectManager = $args->getObjectManager();
                $object->field = 'test ' . $object->id;
                $objectManager->flush();
            }
        });

        $this->_em->flush();
        $this->_em->clear();

        self::assertSame('test ' . $entity1->id, $entity1->field);
        self::assertSame('test ' . $entity2->id, $entity2->field);

        $entity1Reloaded = $this->_em->find(GH10869Entity::class, $entity1->id);
        self::assertSame($entity1->field, $entity1Reloaded->field);

        $entity2Reloaded = $this->_em->find(GH10869Entity::class, $entity2->id);
        self::assertSame($entity2->field, $entity2Reloaded->field);
    }
}

#[ORM\Entity]
class GH10869Entity
{
    /** @var ?int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var ?string */
    #[ORM\Column(type: 'text', nullable: true)]
    public $field;
}

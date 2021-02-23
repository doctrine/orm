<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * @group DDC-1707
 */
class DDC1707Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1509File::class),
                    $this->em->getClassMetadata(DDC1509Picture::class),
                ]
            );
        } catch (Exception $ignored) {
        }
    }

    public function testPostLoadOnChild() : void
    {
        $class   = $this->em->getClassMetadata(DDC1707Child::class);
        $entity  = new DDC1707Child();
        $event   = new LifecycleEventArgs($entity, $this->em);
        $invoker = new ListenersInvoker($this->em);
        $invoke  = $invoker->getSubscribedSystems($class, Events::postLoad);

        $invoker->invoke($class, Events::postLoad, $entity, $event, $invoke);

        self::assertTrue($entity->postLoad);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"c": DDC1707Child::class})
 * @ORM\HasLifecycleCallbacks
 */
abstract class DDC1707Base
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    protected $id;

    public $postLoad = false;

    /**
     * @ORM\PostLoad
     */
    public function onPostLoad()
    {
        $this->postLoad = true;
    }
}
/**
 * @ORM\Entity
 */
class DDC1707Child extends DDC1707Base
{
}

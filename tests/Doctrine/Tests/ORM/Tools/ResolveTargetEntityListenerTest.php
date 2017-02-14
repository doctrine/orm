<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmTestCase;

class ResolveTargetEntityListenerTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var ResolveTargetEntityListener
     */
    private $listener;

    /**
     * @var ClassMetadataFactory
     */
    private $factory;

    public function setUp()
    {
        $annotationDriver = $this->createAnnotationDriver();

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->factory = $this->em->getMetadataFactory();
        $this->listener = new ResolveTargetEntityListener();
    }

    /**
     * @group DDC-1544
     */
    public function testResolveTargetEntityListenerCanResolveTargetEntity()
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTargetInterface::class, ResolveTargetEntity::class, []);
        $this->listener->addResolveTargetEntity(TargetInterface::class, TargetEntity::class, []);
        $evm->addEventSubscriber($this->listener);

        $cm   = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = $cm->associationMappings;

        self::assertSame(TargetEntity::class, $meta['manyToMany']['targetEntity']);
        self::assertSame(ResolveTargetEntity::class, $meta['manyToOne']['targetEntity']);
        self::assertSame(ResolveTargetEntity::class, $meta['oneToMany']['targetEntity']);
        self::assertSame(TargetEntity::class, $meta['oneToOne']['targetEntity']);

        self::assertSame($cm, $this->factory->getMetadataFor(ResolveTargetInterface::class));
    }

    /**
     * @group DDC-3385
     * @group 1181
     * @group 385
     */
    public function testResolveTargetEntityListenerCanRetrieveTargetEntityByInterfaceName()
    {
        $this->listener->addResolveTargetEntity(ResolveTargetInterface::class, ResolveTargetEntity::class, []);

        $this->em->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(ResolveTargetInterface::class);

        self::assertSame($this->factory->getMetadataFor(ResolveTargetEntity::class), $cm);
    }

    /**
     * @group DDC-2109
     */
    public function testAssertTableColumnsAreNotAddedInManyToMany()
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTargetInterface::class, ResolveTargetEntity::class, []);
        $this->listener->addResolveTargetEntity(TargetInterface::class, TargetEntity::class, []);

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $cm = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = $cm->associationMappings['manyToMany'];

        self::assertSame(TargetEntity::class, $meta['targetEntity']);
    }

    /**
     * @group 1572
     * @group functional
     *
     * @coversNothing
     */
    public function testDoesResolveTargetEntitiesInDQLAlsoWithInterfaces()
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTargetInterface::class, ResolveTargetEntity::class, []);

        $evm->addEventSubscriber($this->listener);

        self::assertStringMatchesFormat(
            'SELECT %A FROM "ResolveTargetEntity" %A',
            $this
                ->em
                ->createQuery('SELECT f FROM Doctrine\Tests\ORM\Tools\ResolveTargetInterface f')
                ->getSQL()
        );
    }
}

interface ResolveTargetInterface
{
    public function getId();
}

interface TargetInterface extends ResolveTargetInterface
{
}

/**
 * @ORM\Entity
 */
class ResolveTargetEntity implements ResolveTargetInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\ORM\Tools\TargetInterface")
     */
    private $manyToMany;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Tools\ResolveTargetInterface", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @ORM\OneToMany(targetEntity="Doctrine\Tests\ORM\Tools\ResolveTargetInterface", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @ORM\OneToOne(targetEntity="Doctrine\Tests\ORM\Tools\TargetInterface")
     * @ORM\JoinColumn(name="target_entity_id", referencedColumnName="id")
     */
    private $oneToOne;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity
 */
class TargetEntity implements TargetInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

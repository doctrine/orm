<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\OrmTestCase;
use function iterator_to_array;

class ResolveTargetEntityListenerTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ResolveTargetEntityListener */
    private $listener;

    /** @var ClassMetadataFactory */
    private $factory;

    public function setUp() : void
    {
        $annotationDriver = $this->createAnnotationDriver();

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->factory  = $this->em->getMetadataFactory();
        $this->listener = new ResolveTargetEntityListener();
    }

    /**
     * @group DDC-1544
     */
    public function testResolveTargetEntityListenerCanResolveTargetEntity() : void
    {
        $evm = $this->em->getEventManager();

        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class);
        $this->listener->addResolveTargetEntity(Target::class, TargetEntity::class);

        $evm->addEventSubscriber($this->listener);

        $cm   = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = iterator_to_array($cm->getDeclaredPropertiesIterator());

        self::assertSame(TargetEntity::class, $meta['manyToMany']->getTargetEntity());
        self::assertSame(ResolveTargetEntity::class, $meta['manyToOne']->getTargetEntity());
        self::assertSame(ResolveTargetEntity::class, $meta['oneToMany']->getTargetEntity());
        self::assertSame(TargetEntity::class, $meta['oneToOne']->getTargetEntity());

        self::assertSame($cm, $this->factory->getMetadataFor(ResolveTarget::class));
    }

    /**
     * @group DDC-3385
     * @group 1181
     * @group 385
     */
    public function testResolveTargetEntityListenerCanRetrieveTargetEntityByInterfaceName() : void
    {
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class);

        $this->em->getEventManager()->addEventSubscriber($this->listener);

        $cm = $this->factory->getMetadataFor(ResolveTarget::class);

        self::assertSame($this->factory->getMetadataFor(ResolveTargetEntity::class), $cm);
    }

    /**
     * @group DDC-2109
     */
    public function testAssertTableColumnsAreNotAddedInManyToMany() : void
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class);
        $this->listener->addResolveTargetEntity(Target::class, TargetEntity::class);

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $cm   = $this->factory->getMetadataFor(ResolveTargetEntity::class);
        $meta = $cm->getProperty('manyToMany');

        self::assertSame(TargetEntity::class, $meta->getTargetEntity());
    }

    /**
     * @group 1572
     * @group functional
     * @coversNothing
     */
    public function testDoesResolveTargetEntitiesInDQLAlsoWithInterfaces() : void
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(ResolveTarget::class, ResolveTargetEntity::class);

        $evm->addEventSubscriber($this->listener);

        self::assertStringMatchesFormat(
            'SELECT %A FROM "ResolveTargetEntity" %A',
            $this
                ->em
                ->createQuery('SELECT f FROM Doctrine\Tests\ORM\Tools\ResolveTarget f')
                ->getSQL()
        );
    }
}

interface ResolveTarget
{
    public function getId();
}

interface Target extends ResolveTarget
{
}

/**
 * @ORM\Entity
 */
class ResolveTargetEntity implements ResolveTarget
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\ManyToMany(targetEntity=Target::class) */
    private $manyToMany;

    /** @ORM\ManyToOne(targetEntity=ResolveTarget::class, inversedBy="oneToMany") */
    private $manyToOne;

    /** @ORM\OneToMany(targetEntity=ResolveTarget::class, mappedBy="manyToOne") */
    private $oneToMany;

    /**
     * @ORM\OneToOne(targetEntity=Target::class)
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
class TargetEntity implements Target
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

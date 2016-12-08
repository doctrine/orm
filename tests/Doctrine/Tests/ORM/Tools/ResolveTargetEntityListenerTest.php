<?php

namespace Doctrine\Tests\ORM\Tools;

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

        $this->em = $this->_getTestEntityManager();
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

        $this->assertSame(TargetEntity::class, $meta['manyToMany']['targetEntity']);
        $this->assertSame(ResolveTargetEntity::class, $meta['manyToOne']['targetEntity']);
        $this->assertSame(ResolveTargetEntity::class, $meta['oneToMany']['targetEntity']);
        $this->assertSame(TargetEntity::class, $meta['oneToOne']['targetEntity']);

        $this->assertSame($cm, $this->factory->getMetadataFor(ResolveTargetInterface::class));
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

        $this->assertSame($this->factory->getMetadataFor(ResolveTargetEntity::class), $cm);
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

        $this->assertSame(TargetEntity::class, $meta['targetEntity']);
        $this->assertEquals(['resolvetargetentity_id', 'targetinterface_id'], $meta['joinTableColumns']);
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

        $this->assertStringMatchesFormat(
            'SELECT%AFROM ResolveTargetEntity%A',
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
 * @Entity
 */
class ResolveTargetEntity implements ResolveTargetInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\ORM\Tools\TargetInterface")
     */
    private $manyToMany;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Tools\ResolveTargetInterface", inversedBy="oneToMany")
     */
    private $manyToOne;

    /**
     * @OneToMany(targetEntity="Doctrine\Tests\ORM\Tools\ResolveTargetInterface", mappedBy="manyToOne")
     */
    private $oneToMany;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Tools\TargetInterface")
     * @JoinColumn(name="target_entity_id", referencedColumnName="id")
     */
    private $oneToOne;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class TargetEntity implements TargetInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

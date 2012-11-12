<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;

require_once __DIR__ . '/../../TestInit.php';

class ResolveTargetEntityListenerTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var EntityManager
     */
    private $em = null;

    /**
     * @var ResolveTargetEntityListener
     */
    private $listener = null;

    /**
     * @var ClassMetadataFactory
     */
    private $factory = null;

    public function setUp()
    {
        $annotationDriver = $this->createAnnotationDriver();

        $this->em = $this->_getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->factory = new ClassMetadataFactory;
        $this->factory->setEntityManager($this->em);
        $this->listener = new ResolveTargetEntityListener;
    }

    /**
     * @group DDC-1544
     */
    public function testResolveTargetEntityListenerCanResolveTargetEntity()
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(
            'Doctrine\Tests\ORM\Tools\ResolveTargetInterface',
            'Doctrine\Tests\ORM\Tools\ResolveTargetEntity',
            array()
        );
        $this->listener->addResolveTargetEntity(
            'Doctrine\Tests\ORM\Tools\TargetInterface',
            'Doctrine\Tests\ORM\Tools\TargetEntity',
            array()
        );
        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $cm = $this->factory->getMetadataFor('Doctrine\Tests\ORM\Tools\ResolveTargetEntity');
        $meta = $cm->associationMappings;
        $this->assertSame('Doctrine\Tests\ORM\Tools\TargetEntity', $meta['manyToMany']['targetEntity']);
        $this->assertSame('Doctrine\Tests\ORM\Tools\ResolveTargetEntity', $meta['manyToOne']['targetEntity']);
        $this->assertSame('Doctrine\Tests\ORM\Tools\ResolveTargetEntity', $meta['oneToMany']['targetEntity']);
        $this->assertSame('Doctrine\Tests\ORM\Tools\TargetEntity', $meta['oneToOne']['targetEntity']);
    }

    /**
     * @group DDC-2109
     */
    public function testAssertTableColumnsAreNotAddedInManyToMany()
    {
        $evm = $this->em->getEventManager();
        $this->listener->addResolveTargetEntity(
            'Doctrine\Tests\ORM\Tools\ResolveTargetInterface',
            'Doctrine\Tests\ORM\Tools\ResolveTargetEntity',
            array()
        );
        $this->listener->addResolveTargetEntity(
            'Doctrine\Tests\ORM\Tools\TargetInterface',
            'Doctrine\Tests\ORM\Tools\TargetEntity',
            array()
        );

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $cm = $this->factory->getMetadataFor('Doctrine\Tests\ORM\Tools\ResolveTargetEntity');
        $meta = $cm->associationMappings['manyToMany'];

        $this->assertSame('Doctrine\Tests\ORM\Tools\TargetEntity', $meta['targetEntity']);
        $this->assertEquals(array('resolvetargetentity_id', 'targetinterface_id'), $meta['joinTableColumns']);
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

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Generic\DateTimeModel;
use Doctrine\Common\Collections\Criteria;

/**
 * @group DDC-2106
 */
class DDC2106Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2106Entity'),
        ));
    }

    public function testDetachedEntityAsId()
    {
        // We want an uninitialized PersistentCollection $entity->children
        $entity = new DDC2106Entity();
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->detach($entity);
        $entity = $this->_em->getRepository(__NAMESPACE__ . '\DDC2106Entity')->findOneBy(array());

        // ... and a managed entity without id
        $entityWithoutId = new DDC2106Entity();
        $this->_em->persist($entityWithoutId);

        $criteria = Criteria::create()->where(Criteria::expr()->eq('parent', $entityWithoutId));
        $entity->children->matching($criteria)->count();
    }
}

/**
 * @Entity
 */
class DDC2106Entity
{
    /**
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(type="integer")
     */
    public $id;

    /** @ManyToOne(targetEntity="DDC2106Entity", inversedBy="children") */
    public $parent;

    /**
     * @OneToMany(targetEntity="DDC2106Entity", mappedBy="parent", cascade={"persist"})
     */
    public $children;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection;
    }
}


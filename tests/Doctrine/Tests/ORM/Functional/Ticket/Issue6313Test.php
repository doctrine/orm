<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Common\Collections\ArrayCollection;

final class Issue6313Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(Issue6313Test_MainEntity::class),
                $this->_em->getClassMetadata(Issue6313Test_SubEntity::class),
                $this->_em->getClassMetadata(Issue6313Test_SubEntity_CollectionElement::class),
            ]
        );
    }

    public function testOrphanWithCollectionShouldBeDeletedOnOneToManyRelation()
    {
        $main = new Issue6313Test_MainEntity();

        $sub_entity = new Issue6313Test_SubEntity();
        $sub_entity->addElement(new Issue6313Test_SubEntity_CollectionElement());
        $sub_entity->addElement(new Issue6313Test_SubEntity_CollectionElement());

        $main->addSubEntity($sub_entity);

        $this->_em->persist($main);
        $this->_em->flush();
        $this->_em->clear();

        $main = $this->_em->find(Issue6313Test_MainEntity::class, $main->id);
        $sub_entity = $this->_em->find(Issue6313Test_SubEntity::class, $sub_entity->id);
        $main->removePort($sub_entity);
        $this->_em->flush();
        $this->_em->clear();

        $main = $this->_em->find(Issue6313Test_MainEntity::class, $main->id);

        self::assertCount(0, $main->sub_entities);
    }
}

/**
 * @Entity
 */
class Issue6313Test_MainEntity
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="Issue6313Test_SubEntity", mappedBy="main", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    public $sub_entities;

    public function __construct()
    {
        $this->sub_entities = new ArrayCollection();
    }

    public function addSubEntity(Issue6313Test_SubEntity $sub_entity)
    {
        $this->sub_entities->add($sub_entity);
        $sub_entity->main = $this;
    }

    public function removePort(Issue6313Test_SubEntity $sub_entity)
    {
        $this->sub_entities->removeElement($sub_entity);
        $sub_entity->main = null;
    }
}

/**
 * @Entity
 */
class Issue6313Test_SubEntity
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var Issue6313Test_MainEntity
     *
     * @ManyToOne(targetEntity="Issue6313Test_MainEntity", inversedBy="sub_entities")
     * @JoinColumn(name="main_id", referencedColumnName="id")
     */
    public $main;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="Issue6313Test_SubEntity_CollectionElement", mappedBy="sub_entity", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    public $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    public function addElement(Issue6313Test_SubEntity_CollectionElement $element)
    {
        $this->elements->add($element);
        $element->sub_entity = $this;
    }
}

/**
 * @Entity
 */
class Issue6313Test_SubEntity_CollectionElement
{
    /**
     * @var integer
     *
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @var SubEntity
     *
     * @ManyToOne(targetEntity="Issue6313Test_SubEntity", inversedBy="elements")
     * @JoinColumn(name="sub_entity_id", referencedColumnName="id")
     */
    public $sub_entity;
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 */
class Ticket69 extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(Lemma::class),
                    $this->em->getClassMetadata(Relation::class),
                    $this->em->getClassMetadata(RelationType::class),
                ]
            );
        } catch (Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testIssue() : void
    {
        //setup
        $lemma1 = new Lemma();
        $lemma1->setLemma('foo');

        $lemma2 = new Lemma();
        $lemma2->setLemma('bar');

        $lemma3 = new Lemma();
        $lemma3->setLemma('batz');

        $lemma4 = new Lemma();
        $lemma4->setLemma('bla');

        $type1 = new RelationType();
        $type1->setType('nonsense');
        $type1->setAbbreviation('non');

        $type2 = new RelationType();
        $type2->setType('quatsch');
        $type2->setAbbreviation('qu');

        $relation1 = new Relation();
        $relation1->setParent($lemma1);
        $relation1->setChild($lemma2);
        $relation1->setType($type1);

        $relation2 = new Relation();
        $relation2->setParent($lemma1);
        $relation2->setChild($lemma3);
        $relation2->setType($type1);

        $relation3 = new Relation();
        $relation3->setParent($lemma1);
        $relation3->setChild($lemma4);
        $relation3->setType($type2);

        $lemma1->addRelation($relation1);
        $lemma1->addRelation($relation2);
        $lemma1->addRelation($relation3);

        $this->em->persist($type1);
        $this->em->persist($type2);
        $this->em->persist($lemma1);
        $this->em->persist($lemma2);
        $this->em->persist($lemma3);
        $this->em->persist($lemma4);

        $this->em->flush();
        $this->em->clear();
        //end setup

        // test One To Many
        $query = $this->em->createQuery("SELECT l FROM Doctrine\Tests\ORM\Functional\Ticket\Lemma l Where l.lemma = 'foo'");
        $res   = $query->getResult();
        $lemma = $res[0];

        self::assertEquals('foo', $lemma->getLemma());
        self::assertInstanceOf(Lemma::class, $lemma);
        $relations = $lemma->getRelations();

        foreach ($relations as $relation) {
            self::assertInstanceOf(Relation::class, $relation);
            self::assertTrue($relation->getType()->getType() !== '');
        }

        $this->em->clear();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="lemma")
 */
class Lemma
{
    public const CLASS_NAME = self::class;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="lemma_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="lemma_name", unique=true, length=255)
     *
     * @var string
     */
    private $lemma;


    /**
     * @ORM\OneToMany(targetEntity=Relation::class, mappedBy="parent", cascade={"persist"})
     *
     * @var kateglo\application\utilities\collections\ArrayCollection
     */
    private $relations;

    public function __construct()
    {
        $this->types     = new ArrayCollection();
        $this->relations = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $lemma
     */
    public function setLemma($lemma)
    {
        $this->lemma = $lemma;
    }

    /**
     * @return string
     */
    public function getLemma()
    {
        return $this->lemma;
    }

    public function addRelation(Relation $relation)
    {
        $this->relations[] = $relation;
        $relation->setParent($this);
    }

    public function removeRelation(Relation $relation)
    {
        /** @var Relation $removed */
        $removed = $this->relations->removeElement($relation);
        if ($removed !== null) {
            $removed->removeParent();
        }
    }

    /**
     * @return kateglo\application\utilities\collections\ArrayCollection
     */
    public function getRelations()
    {
        return $this->relations;
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="relation")
 */
class Relation
{
    public const CLASS_NAME = self::class;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="relation_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Lemma::class, inversedBy="relations")
     * @ORM\JoinColumn(name="relation_parent_id", referencedColumnName="lemma_id")
     *
     * @var Lemma
     */
    private $parent;

    /**
     * @ORM\OneToOne(targetEntity=Lemma::class)
     * @ORM\JoinColumn(name="relation_child_id", referencedColumnName="lemma_id")
     *
     * @var Lemma
     */
    private $child;

    /**
     * @ORM\ManyToOne(targetEntity=RelationType::class, inversedBy="relations")
     * @ORM\JoinColumn(name="relation_type_id", referencedColumnName="relation_type_id")
     *
     * @var RelationType
     */
    private $type;

    public function setParent(Lemma $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Phrase
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function removeParent()
    {
        if ($this->lemma !== null) {
            /** @var Lemma $phrase */
            $lemma        = $this->parent;
            $this->parent = null;
            $lemma->removeRelation($this);
        }
    }

    public function setChild(Lemma $child)
    {
        $this->child = $child;
    }

    /**
     * @return Lemma
     */
    public function getChild()
    {
        return $this->child;
    }

    public function setType(RelationType $type)
    {
        $this->type = $type;
    }

    /**
     * @return RelationType
     */
    public function getType()
    {
        return $this->type;
    }

    public function removeType()
    {
        if ($this->type !== null) {
            /** @var RelationType $phrase */
            $type       = $this->type;
            $this->type = null;
            $type->removeRelation($this);
        }
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="relation_type")
 */
class RelationType
{
    public const CLASS_NAME = self::class;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="relation_type_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="relation_type_name", unique=true, length=255)
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="string", name="relation_type_abbreviation", unique=true, length=255)
     *
     * @var string
     */
    private $abbreviation;

    /**
     * @ORM\OneToMany(targetEntity=Relation::class, mappedBy="type", cascade={"persist"})
     *
     * @var kateglo\application\utilities\collections\ArrayCollection
     */
    private $relations;

    public function __construct()
    {
        $relations = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $abbreviation
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    public function addRelation(Relation $relation)
    {
        $this->relations[] = $relation;
        $relation->setType($this);
    }

    public function removeRelation(Relation $relation)
    {
        /** @var Relation $removed */
        $removed = $this->relations->removeElement($relation);
        if ($removed !== null) {
            $removed->removeLemma();
        }
    }

    /**
     * @return kateglo\application\utilities\collections\ArrayCollection
     */
    public function getRelations()
    {
        return $this->relations;
    }
}

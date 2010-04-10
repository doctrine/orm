<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 *
 * @author robo
 */
class AdvancedAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase {
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                    $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\Lemma'),
                    $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\Relation'),
                    $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Ticket\RelationType')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testIssue()
    {
        //setup
        $lemma1 = new Lemma;
        $lemma1->setLemma('foo');

        $lemma2 = new Lemma;
        $lemma2->setLemma('bar');

        $lemma3 = new Lemma;
        $lemma3->setLemma('batz');

        $lemma4 = new Lemma;
        $lemma4->setLemma('bla');

        $type1 = new RelationType;
        $type1->setType('nonsense');
        $type1->setAbbreviation('non');

        $type2 = new RelationType;
        $type2->setType('quatsch');
        $type2->setAbbreviation('qu');

        $relation1 = new Relation;
        $relation1->setParent($lemma1);
        $relation1->setChild($lemma2);
        $relation1->setType($type1);

        $relation2 = new Relation;
        $relation2->setParent($lemma1);
        $relation2->setChild($lemma3);
        $relation2->setType($type1);
    
        $relation3 = new Relation;
        $relation3->setParent($lemma1);
        $relation3->setChild($lemma4);
        $relation3->setType($type2);

        $lemma1->addRelation($relation1);
        $lemma1->addRelation($relation2);
        $lemma1->addRelation($relation3);

        $this->_em->persist($type1);
        $this->_em->persist($type2);
        $this->_em->persist($lemma1);
        $this->_em->persist($lemma2);
        $this->_em->persist($lemma3);
        $this->_em->persist($lemma4);

        $this->_em->flush();
        $this->_em->clear();
        //end setup

        // test One To Many
        $query = $this->_em->createQuery("SELECT l FROM Doctrine\Tests\ORM\Functional\Ticket\Lemma l Where l.lemma = 'foo'");
        $res = $query->getResult();
        $lemma = $res[0];

        $this->assertEquals('foo', $lemma->getLemma());
        $this->assertTrue($lemma instanceof Lemma);
        $relations = $lemma->getRelations();

        foreach($relations as $relation) {
            $this->assertTrue($relation instanceof Relation);
            $this->assertTrue($relation->getType()->getType() != '');
        }

        $this->_em->clear();

    }
}

/**
 * @Entity
 * @Table(name="lemma")
 */
class Lemma {

    const CLASS_NAME = __CLASS__;

    /**
     * @var int
     * @Id
     * @Column(type="integer", name="lemma_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * @var string
     * @Column(type="string", name="lemma_name", unique=true, length=255)
     */
    private $lemma;


    /**
     * @var kateglo\application\utilities\collections\ArrayCollection
     * @OneToMany(targetEntity="Relation", mappedBy="parent", cascade={"persist"})
     */
    private $relations;

    public function __construct() {
        $this->types = new \Doctrine\Common\Collections\ArrayCollection();
        $this->relations = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     *
     * @param string $lemma
     * @return void
     */
    public function setLemma($lemma) {
        $this->lemma = $lemma;
    }

    /**
     *
     * @return string
     */
    public function getLemma() {
        return $this->lemma;
    }


    /**
     *
     * @param Relation $relation
     * @return void
     */
    public function addRelation(Relation $relation) {
        $this->relations[] = $relation;
        $relation->setParent($this);
    }

    /**
     *
     * @param Relation $relation
     * @return void
     */
    public function removeRelation(Relation $relation) {
        /*@var $removed Relation */
        $removed = $this->relations->removeElement($relation);
        if ($removed !== null) {
            $removed->removeParent();
        }
    }

    /**
     *
     * @return kateglo\application\utilities\collections\ArrayCollection
     */
    public function getRelations() {
        return $this->relations;
    }

}

/**
 *
 * @Entity
 * @Table(name="relation")
 */
class Relation {

    const CLASS_NAME = __CLASS__;

    /**
     * @var int
     * @Id
     * @Column(type="integer", name="relation_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Lemma
     * @ManyToOne(targetEntity="Lemma", inversedBy="relations")
     * @JoinColumn(name="relation_parent_id", referencedColumnName="lemma_id")
     */
    private $parent;

    /**
     * @var Lemma
     * @OneToOne(targetEntity="Lemma")
     * @JoinColumn(name="relation_child_id", referencedColumnName="lemma_id")
     */
    private $child;

    /**
     * @var RelationType
     * @ManyToOne(targetEntity="RelationType", inversedBy="relations")
     * @JoinColumn(name="relation_type_id", referencedColumnName="relation_type_id")
     */
    private $type;

    /**
     *
     * @param Lemma $parent
     * @return void
     */
    public function setParent(Lemma $parent) {
        $this->parent = $parent;
    }

    /**
     *
     * @return Phrase
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     *
     * @return void
     */
    public function removeParent() {
        if ($this->lemma !== null) {
            /*@var $phrase Lemma */
            $lemma = $this->parent;
            $this->parent = null;
            $lemma->removeRelation($this);
        }
    }

    /**
     *
     * @param Lemma $child
     * @return void
     */
    public function setChild(Lemma $child) {
        $this->child = $child;
    }

    /**
     *
     * @return Lemma
     */
    public function getChild() {
        return $this->child;
    }

    /**
     *
     * @param RelationType $type
     * @return void
     */
    public function setType(RelationType $type) {
        $this->type = $type;
    }

    /**
     *
     * @return RelationType
     */
    public function getType() {
        return $this->type;
    }

    /**
     *
     * @return void
     */
    public function removeType() {
        if ($this->type !== null) {
            /*@var $phrase RelationType */
            $type = $this->type;
            $this->type = null;
            $type->removeRelation($this);
        }
    }
}

/**
 *
 * @Entity
 * @Table(name="relation_type")
 */
class RelationType {

    const CLASS_NAME = __CLASS__;

    /**
     *
     * @var int
     * @Id
     * @Column(type="integer", name="relation_type_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * @var string
     * @Column(type="string", name="relation_type_name", unique=true, length=255)
     */
    private $type;

    /**
     *
     * @var string
     * @Column(type="string", name="relation_type_abbreviation", unique=true, length=255)
     */
    private $abbreviation;

    /**
     * @var kateglo\application\utilities\collections\ArrayCollection
     * @OneToMany(targetEntity="Relation", mappedBy="type", cascade={"persist"})
     */
    private $relations;

    public function __construct() {
        $relations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     *
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     *
     * @param string $abbreviation
     * @return void
     */
    public function setAbbreviation($abbreviation) {
        $this->abbreviation = $abbreviation;
    }

    /**
     *
     * @return string
     */
    public function getAbbreviation() {
        return $this->abbreviation;
    }

    /**
     *
     * @param Relation $relation
     * @return void
     */
    public function addRelation(Relation $relation) {
        $this->relations[] = $relation;
        $relation->setType($this);
    }

    /**
     *
     * @param Relation $relation
     * @return void
     */
    public function removeRelation(Relation $relation) {
        /*@var $removed Relation */
        $removed = $this->relations->removeElement($relation);
        if ($removed !== null) {
            $removed->removeLemma();
        }
    }

    /**
     *
     * @return kateglo\application\utilities\collections\ArrayCollection
     */
    public function getRelations() {
        return $this->relations;
    }
}


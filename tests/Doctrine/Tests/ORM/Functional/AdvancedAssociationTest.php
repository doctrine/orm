<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 *
 * @author robo
 */
class AdvancedAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Phrase'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\PhraseType'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Definition'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Lemma'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Type')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testIssue()
    {
        //setup
        $phrase = new Phrase;
        $phrase->setPhrase('lalala');

        $type = new PhraseType;
        $type->setType('nonsense');
        $type->setAbbreviation('non');

        $def1 = new Definition;
        $def1->setDefinition('def1');
        $def2 = new Definition;
        $def2->setDefinition('def2');

        $phrase->setType($type);
        $phrase->addDefinition($def1);
        $phrase->addDefinition($def2);

        $this->_em->persist($phrase);
        $this->_em->persist($type);

        $this->_em->flush();
        $this->_em->clear();
        //end setup

        // test1 - lazy-loading many-to-one after find()
        $phrase2 = $this->_em->find('Doctrine\Tests\ORM\Functional\Phrase', $phrase->getId());
        $this->assertTrue(is_numeric($phrase2->getType()->getId()));

        $this->_em->clear();

        // test2 - eager load in DQL query
        $query = $this->_em->createQuery("SELECT p,t FROM Doctrine\Tests\ORM\Functional\Phrase p JOIN p.type t");
        $res = $query->getResult();
        $this->assertEquals(1, count($res));
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\PhraseType', $res[0]->getType());
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $res[0]->getType()->getPhrases());
        $this->assertFalse($res[0]->getType()->getPhrases()->isInitialized());

        $this->_em->clear();

        // test2 - eager load in DQL query with double-join back and forth
        $query = $this->_em->createQuery("SELECT p,t,pp FROM Doctrine\Tests\ORM\Functional\Phrase p JOIN p.type t JOIN t.phrases pp");
        $res = $query->getResult();
        $this->assertEquals(1, count($res));
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\PhraseType', $res[0]->getType());
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $res[0]->getType()->getPhrases());
        $this->assertTrue($res[0]->getType()->getPhrases()->isInitialized());

        $this->_em->clear();

        // test3 - lazy-loading one-to-many after find()
        $phrase3 = $this->_em->find('Doctrine\Tests\ORM\Functional\Phrase', $phrase->getId());
        $definitions = $phrase3->getDefinitions();
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $definitions);
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Definition', $definitions[0]);

        $this->_em->clear();

        // test4 - lazy-loading after DQL query
        $query = $this->_em->createQuery("SELECT p FROM Doctrine\Tests\ORM\Functional\Phrase p");
        $res = $query->getResult();
        $definitions = $res[0]->getDefinitions();

        $this->assertEquals(1, count($res));

        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Definition', $definitions[0]);
        $this->assertEquals(2, $definitions->count());
    }

    public function testManyToMany()
    {
        $lemma = new Lemma;
        $lemma->setLemma('abu');

        $type = new Type();
        $type->setType('nonsense');
        $type->setAbbreviation('non');

        $lemma->addType($type);

        $this->_em->persist($lemma);
        $this->_em->persist($type);
        $this->_em->flush();

        // test5 ManyToMany
        $query = $this->_em->createQuery("SELECT l FROM Doctrine\Tests\ORM\Functional\Lemma l");
        $res = $query->getResult();
        $types = $res[0]->getTypes();

        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Type', $types[0]);
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
	 * @ManyToMany(targetEntity="Type", mappedBy="lemmas", cascade={"persist"})
	 */
	private $types;

	public function __construct() {
		$this->types = new \Doctrine\Common\Collections\ArrayCollection();
	}


	/**
	 *
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 *
	 * @param string $lemma
	 * @return void
	 */
	public function setLemma($lemma){
		$this->lemma = $lemma;
	}

	/**
	 *
	 * @return string
	 */
	public function getLemma(){
		return $this->lemma;
	}

	/**
     *
     * @param kateglo\application\models\Type $type
     * @return void
     */
	public function addType(Type $type){
        if (!$this->types->contains($type)) {
            $this->types[] = $type;
            $type->addLemma($this);
        }
    }

    /**
     *
     * @param kateglo\application\models\Type $type
     * @return void
     */
    public function removeType(Type $type)
    {
        $removed = $this->sources->removeElement($type);
        if ($removed !== null) {
            $removed->removeLemma($this);
        }
    }

    /**
     *
     * @return kateglo\application\helpers\collections\ArrayCollection
     */
    public function getTypes()
    {
        return $this->types;
    }
}

/**
 * @Entity
 * @Table(name="type")
 */
class Type {

	const CLASS_NAME = __CLASS__;

	/**
	 *
	 * @var int
	 * @Id
	 * @Column(type="integer", name="type_id")
	 * @GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 *
	 * @var string
	 * @Column(type="string", name="type_name", unique=true)
	 */
	private $type;

	/**
	 *
	 * @var string
	 * @Column(type="string", name="type_abbreviation", unique=true)
	 */
	private $abbreviation;

	/**
	 * @var kateglo\application\helpers\collections\ArrayCollection
	 * @ManyToMany(targetEntity="Lemma")
	 * @JoinTable(name="lemma_type",
	 * 		joinColumns={@JoinColumn(name="type_id", referencedColumnName="type_id")},
	 * 		inverseJoinColumns={@JoinColumn(name="lemma_id", referencedColumnName="lemma_id")}
	 * )
	 */
	private $lemmas;

	public function __construct(){
		$this->lemmas = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 *
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type){
		$this->type = $type;
	}

	/**
	 *
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}

	/**
	 *
	 * @param string $abbreviation
	 * @return void
	 */
	public function setAbbreviation($abbreviation){
		$this->abbreviation = $abbreviation;
	}

	/**
	 *
	 * @return string
	 */
	public function getAbbreviation(){
		return $this->abbreviation;
	}

	/**
	 *
	 * @param kateglo\application\models\Lemma $lemma
	 * @return void
	 */
	public function addLemma(Lemma $lemma)
	{
		if (!$this->lemmas->contains($lemma)) {
			$this->lemmas[] = $lemma;
			$lemma->addType($this);
		}
	}

	/**
	 *
	 * @param kateglo\application\models\Lemma $lemma
	 * @return void
	 */
	public function removeLEmma(Lemma $lemma)
	{
		$removed = $this->lemmas->removeElement($lemma);
		if ($removed !== null) {
			$removed->removeType($this);
		}
	}

	/**
	 *
	 * @return kateglo\application\helpers\collections\ArrayCollection
	 */
	public function getCategories()
	{
		return $this->categories;
	}

}


/**
 * @Entity
 * @Table(name="phrase")
 */
class Phrase {

    const CLASS_NAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer", name="phrase_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", name="phrase_name", unique=true, length=255)
     */
    private $phrase;

    /**
     * @ManyToOne(targetEntity="PhraseType")
     * @JoinColumn(name="phrase_type_id", referencedColumnName="phrase_type_id")
     */
    private $type;

    /**
     * @OneToMany(targetEntity="Definition", mappedBy="phrase", cascade={"persist"})
     */
    private $definitions;

    public function __construct() {
        $this->definitions = new \Doctrine\Common\Collections\ArrayCollection;
    }

    /**
     *
     * @param Definition $definition
     * @return void
     */
    public function addDefinition(Definition $definition){
        $this->definitions[] = $definition;
        $definition->setPhrase($this);
    }

    /**
     * @return int
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param string $phrase
     * @return void
     */
    public function setPhrase($phrase){
        $this->phrase = $phrase;
    }

    /**
     * @return string
     */
    public function getPhrase(){
        return $this->phrase;
    }

    /**
     *
     * @param PhraseType $type
     * @return void
     */
    public function setType(PhraseType $type){
        $this->type = $type;
    }

    /**
     *
     * @return PhraseType
     */
    public function getType(){
        return $this->type;
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getDefinitions(){
        return $this->definitions;
    }
}

/**
 * @Entity
 * @Table(name="phrase_type")
 */
class PhraseType {

    const CLASS_NAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer", name="phrase_type_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", name="phrase_type_name", unique=true)
     */
    private $type;

    /**
     * @Column(type="string", name="phrase_type_abbreviation", unique=true)
     */
    private $abbreviation;

    /**
     * @OneToMany(targetEntity="Phrase", mappedBy="type")
     */
    private $phrases;

    public function __construct() {
        $this->phrases = new \Doctrine\Common\Collections\ArrayCollection;
    }

    /**
     * @return int
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type){
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(){
        return $this->type;
    }

    /**
     * @param string $abbreviation
     * @return void
     */
    public function setAbbreviation($abbreviation){
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return string
     */
    public function getAbbreviation(){
        return $this->abbreviation;
    }

    /**
     * @param ArrayCollection $phrases
     * @return void
     */
    public function setPhrases($phrases){
        $this->phrases = $phrases;
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getPhrases(){
        return $this->phrases;
    }

}

/**
 * @Entity
 * @Table(name="definition")
 */
class Definition {

    const CLASS_NAME = __CLASS__;

    /**
     * @Id
     * @Column(type="integer", name="definition_id")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Phrase")
     * @JoinColumn(name="definition_phrase_id", referencedColumnName="phrase_id")
     */
    private $phrase;

    /**
     * @Column(type="text", name="definition_text")
     */
    private $definition;

    /**
     * @return int
     */
    public function getId(){
        return $this->id;
    }

    /**
     * @param Phrase $phrase
     * @return void
     */
    public function setPhrase(Phrase $phrase){
        $this->phrase = $phrase;
    }

    /**
     * @return Phrase
     */
    public function getPhrase(){
        return $this->phrase;
    }

    public function removePhrase() {
        if ($this->phrase !== null) {
            /*@var $phrase kateglo\application\models\Phrase */
            $phrase = $this->phrase;
            $this->phrase = null;
            $phrase->removeDefinition($this);
        }
    }

    /**
     * @param string $definition
     * @return void
     */
    public function setDefinition($definition){
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getDefinition(){
        return $this->definition;
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC117Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Article'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Reference'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Translation'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117ArticleDetails'),
            ));
        } catch(\Exception $e) {

        }
    }

    /**
     * @group DDC-117
     */
    public function testAssociationOnlyCompositeKey()
    {
        $article1 = new DDC117Article("Foo");
        $article2 = new DDC117Article("Bar");

        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();

        $reference = new DDC117Reference($article1, $article2, "Test-Description");
        $this->_em->persist($reference);
        $this->_em->flush();

        $mapRef = $this->_em->find(__NAMESPACE__."\DDC117Reference", array('source' => 1, 'target' => 2));
        $this->assertSame($reference, $mapRef);

        $this->_em->clear();

        $dql = "SELECT r, s FROM ".__NAMESPACE__."\DDC117Reference r JOIN r.source s WHERE r.source = ?1";
        $ref = $this->_em->createQuery($dql)->setParameter(1, 1)->getSingleResult();
        
        $this->_em->clear();

        $refRep = $this->_em->find(__NAMESPACE__."\DDC117Reference", array('source' => 1, 'target' => 2));

        $this->assertType(__NAMESPACE__."\DDC117Reference", $refRep);
        $this->assertType(__NAMESPACE__."\DDC117Article", $refRep->target());
        $this->assertType(__NAMESPACE__."\DDC117Article", $refRep->source());

        $this->assertSame($refRep, $this->_em->find(__NAMESPACE__."\DDC117Reference", array('source' => 1, 'target' => 2)));

        $this->_em->remove($refRep);
        $this->_em->flush();
    }

    /**
     * @group DDC-117
     */
    public function testInverseSideAccess()
    {
        $article1 = new DDC117Article("Foo");
        $article2 = new DDC117Article("Bar");

        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();

        $reference = new DDC117Reference($article1, $article2, "Test-Description");
        $this->_em->persist($reference);
        $this->_em->flush();
        $this->_em->clear();

        $article1 = $this->_em->find(__NAMESPACE__."\DDC117Article", $article1->id());

        $this->assertEquals(1, count($article1->references()));
        foreach ($article1->references() AS $reference) {
            $this->assertType(__NAMESPACE__."\DDC117Reference", $reference);
            $this->assertSame($article1, $reference->source());
        }

        $this->_em->clear();

        $dql = 'SELECT a, r FROM '. __NAMESPACE__ . '\DDC117Article a INNER JOIN a.references r WHERE a.id = ?1';
        $articleDql = $this->_em->createQuery($dql)
                                ->setParameter(1, $article1->id())
                                ->getSingleResult();

        $this->assertEquals(1, count($article1->references()));
        foreach ($article1->references() AS $reference) {
            $this->assertType(__NAMESPACE__."\DDC117Reference", $reference);
            $this->assertSame($article1, $reference->source());
        }
    }

    /**
     * @group DDC-117
     */
    public function testMixedCompositeKey()
    {
        $article1 = new DDC117Article("Foo");
        $this->_em->persist($article1);
        $this->_em->flush();

        $translation = new DDC117Translation($article1, "en", "Bar");
        $this->_em->persist($translation);
        $this->_em->flush();

        $this->assertSame($translation, $this->_em->find(__NAMESPACE__ . '\DDC117Translation', array('article' => $article1->id(), 'language' => 'en')));

        $this->_em->clear();

        $translation = $this->_em->find(__NAMESPACE__ . '\DDC117Translation', array('article' => $article1->id(), 'language' => 'en'));
        $this->assertType(__NAMESPACE__ . '\DDC117Translation', $translation);

        $this->_em->clear();

        $dql = 'SELECT t, a FROM ' . __NAMESPACE__ . '\DDC117Translation t JOIN t.article a WHERE t.article = ?1 AND t.language = ?2';
        $dqlTrans = $this->_em->createQuery($dql)
                              ->setParameter(1, $article1->id())
                              ->setParameter(2, 'en')
                              ->getSingleResult();

        $this->assertType(__NAMESPACE__ . '\DDC117Translation', $translation);
    }

    /**
     * @group DDC-117
     */
    public function testOneToOneForeignObjectId()
    {
        $article1 = new DDC117Article("Foo");
        $this->_em->persist($article1);
        $this->_em->flush();

        $details = new DDC117ArticleDetails($article1, "Very long text");
        $this->_em->persist($details);
        $this->_em->flush();
    }
}

/**
 * @Entity
 */
class DDC117Article
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column */
    private $title;

    /**
     * @OneToMany(targetEntity="DDC117Reference", mappedBy="source")
     */
    private $references;

    /**
     * @OneToOne(targetEntity="DDC117ArticleDetails", mappedBy="article")
     */
    private $details;

    public function __construct($title)
    {
        $this->title = $title;
        $this->references = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setDetails($details)
    {
        $this->details = $details;
    }

    public function id()
    {
        return $this->id;
    }

    public function addReference($reference)
    {
        $this->references[] = $reference;
    }

    public function references()
    {
        return $this->references;
    }
}

/**
 * @Entity
 */
class DDC117ArticleDetails
{
    /** @Id @OneToOne(targetEntity="DDC117Article", inversedBy="details") */
    private $article;

    /**
     * @Column(type="text")
     */
    private $text;

    public function __construct($article, $text)
    {
        $this->article = $article;
        $article->setDetails($this);

        $this->text = $text;
    }
}

/**
 * @Entity
 */
class DDC117Reference
{
    /**
     * @Id @ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     */
    private $source;

    /**
     * @Id @ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     */
    private $target;

    /**
     * @column(type="string")
     */
    private $description;

    /**
     * @column(type="datetime")
     */
    private $created;

    public function __construct($source, $target, $description)
    {
        $source->addReference($this);
        $target->addReference($this);

        $this->source = $source;
        $this->target = $target;
        $this->description = $description;
        $this->created = new \DateTime("now");
    }

    public function source()
    {
        return $this->source;
    }

    public function target()
    {
        return $this->target;
    }
}

/**
 * @Entity
 */
class DDC117Translation
{
    /**
     * @Id @ManyToOne(targetEntity="DDC117Article")
     */
    private $article;

    /**
     * @Id @column(type="string")
     */
    private $language;

    /**
     * @column(type="string")
     */
    private $title;

    public function __construct($article, $language, $title)
    {
        $this->article = $article;
        $this->language = $language;
        $this->title = $title;
    }
}
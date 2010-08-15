<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC117Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $article1;
    private $article2;
    private $reference;
    private $translation;
    private $articleDetails;

    protected function setUp() {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Article'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Reference'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117Translation'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC117ArticleDetails'),
            ));
        } catch(\Exception $e) {

        }

        $this->article1 = new DDC117Article("Foo");
        $this->article2 = new DDC117Article("Bar");

        $this->_em->persist($this->article1);
        $this->_em->persist($this->article2);
        $this->_em->flush();

        $this->reference = new DDC117Reference($this->article1, $this->article2, "Test-Description");
        $this->_em->persist($this->reference);

        $this->translation = new DDC117Translation($this->article1, "en", "Bar");
        $this->_em->persist($this->translation);

        $this->articleDetails = new DDC117ArticleDetails($this->article1, "Very long text");
        $this->_em->persist($this->articleDetails);
        $this->_em->flush();

        $this->_em->clear();
    }

    /**
     * @group DDC-117
     */
    public function testAssociationOnlyCompositeKey()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $mapRef = $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria);
        $this->assertType(__NAMESPACE__."\DDC117Reference", $mapRef);
        $this->assertType(__NAMESPACE__."\DDC117Article", $mapRef->target());
        $this->assertType(__NAMESPACE__."\DDC117Article", $mapRef->source());
        $this->assertSame($mapRef, $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria));

        $this->_em->clear();

        $dql = "SELECT r, s FROM ".__NAMESPACE__."\DDC117Reference r JOIN r.source s WHERE r.source = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 1)->getSingleResult();

        $this->assertType(__NAMESPACE__."\DDC117Reference", $mapRef);
        $this->assertType(__NAMESPACE__."\DDC117Article", $mapRef->target());
        $this->assertType(__NAMESPACE__."\DDC117Article", $mapRef->source());
        $this->assertSame($dqlRef, $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria));

        $this->_em->clear();

        $dql = "SELECT r, s FROM ".__NAMESPACE__."\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        $this->assertType(__NAMESPACE__."\DDC117Reference", $dqlRef);
        $this->assertType(__NAMESPACE__."\DDC117Article", $dqlRef->target());
        $this->assertType(__NAMESPACE__."\DDC117Article", $dqlRef->source());
        $this->assertSame($dqlRef, $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria));

        $dql = "SELECT r, s FROM ".__NAMESPACE__."\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        $this->_em->contains($dqlRef);
    }

    /**
     * @group DDC-117
     */
    public function testUpdateAssocationEntity()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $mapRef = $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria);
        $this->assertNotNull($mapRef);
        $mapRef->setDescription("New Description!!");
        $this->_em->flush();
        $this->_em->clear();

        $mapRef = $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria);

        $this->assertEquals('New Description!!', $mapRef->getDescription());
    }

    /**
     * @group DDC-117
     */
    public function testFetchDql()
    {
        $dql = "SELECT r, s FROM ".__NAMESPACE__."\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $refs = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getResult();

        $this->assertTrue(count($refs) > 0, "Has to contain at least one Reference.");
        foreach ($refs AS $ref) {
            $this->assertType(__NAMESPACE__."\DDC117Reference", $ref, "Contains only Reference instances.");
            $this->assertTrue($this->_em->contains($ref), "Contains Reference in the IdentityMap.");
        }
    }

    /**
     * @group DDC-117
     */
    public function testRemoveCompositeElement()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $refRep = $this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria);

        $this->_em->remove($refRep);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria));
    }

    /**
     * @group DDC-117
     */
    public function testDqlRemoveCompositeElement()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $dql = "DELETE ".__NAMESPACE__."\DDC117Reference r WHERE r.source = ?1 AND r.target = ?2";
        $this->_em->createQuery($dql)
                  ->setParameter(1, $this->article1->id())
                  ->setParameter(2, $this->article2->id())
                  ->execute();

        $this->assertNull($this->_em->find(__NAMESPACE__."\DDC117Reference", $idCriteria));
    }

    /**
     * @group DDC-117
     */
    public function testInverseSideAccess()
    {
        $this->article1 = $this->_em->find(__NAMESPACE__."\DDC117Article", $this->article1->id());

        $this->assertEquals(1, count($this->article1->references()));
        foreach ($this->article1->references() AS $this->reference) {
            $this->assertType(__NAMESPACE__."\DDC117Reference", $this->reference);
            $this->assertSame($this->article1, $this->reference->source());
        }

        $this->_em->clear();

        $dql = 'SELECT a, r FROM '. __NAMESPACE__ . '\DDC117Article a INNER JOIN a.references r WHERE a.id = ?1';
        $articleDql = $this->_em->createQuery($dql)
                                ->setParameter(1, $this->article1->id())
                                ->getSingleResult();

        $this->assertEquals(1, count($this->article1->references()));
        foreach ($this->article1->references() AS $this->reference) {
            $this->assertType(__NAMESPACE__."\DDC117Reference", $this->reference);
            $this->assertSame($this->article1, $this->reference->source());
        }
    }

    /**
     * @group DDC-117
     */
    public function testMixedCompositeKey()
    {
        $idCriteria = array('article' => $this->article1->id(), 'language' => 'en');

        $this->translation = $this->_em->find(__NAMESPACE__ . '\DDC117Translation', $idCriteria);
        $this->assertType(__NAMESPACE__ . '\DDC117Translation', $this->translation);

        $this->assertSame($this->translation, $this->_em->find(__NAMESPACE__ . '\DDC117Translation', $idCriteria));

        $this->_em->clear();

        $dql = 'SELECT t, a FROM ' . __NAMESPACE__ . '\DDC117Translation t JOIN t.article a WHERE t.article = ?1 AND t.language = ?2';
        $dqlTrans = $this->_em->createQuery($dql)
                              ->setParameter(1, $this->article1->id())
                              ->setParameter(2, 'en')
                              ->getSingleResult();

        $this->assertType(__NAMESPACE__ . '\DDC117Translation', $this->translation);
    }

    /**
     * @group DDC-117
     */
    public function testMixedCompositeKeyViolateUniqueness()
    {
        $this->article1 = $this->_em->find(__NAMESPACE__ . '\DDC117Article', $this->article1->id());
        $this->article1->addTranslation('en', 'Bar');
        $this->article1->addTranslation('en', 'Baz');

        $this->setExpectedException('Exception');
        $this->_em->flush();
    }

    /**
     * @group DDC-117
     */
    public function testOneToOneForeignObjectId()
    {
        $this->article1 = new DDC117Article("Foo");
        $this->_em->persist($this->article1);
        $this->_em->flush();

        $this->articleDetails = new DDC117ArticleDetails($this->article1, "Very long text");
        $this->_em->persist($this->articleDetails);
        $this->_em->flush();

        $this->articleDetails->update("not so very long text!");
        $this->_em->flush();
        $this->_em->clear();

        /* @var $article DDC117Article */
        $article = $this->_em->find(get_class($this->article1), $this->article1->id());
        $this->assertEquals('not so very long text!', $article->getText());
    }
}

/**
 * @Entity
 */
class DDC117Article
{
    /** @Id @Column(type="integer", name="article_id") @GeneratedValue */
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

    /**
     * @OneToMany(targetEntity="DDC117Translation", mappedBy="article", cascade={"persist"})
     */
    private $translations;

    public function __construct($title)
    {
        $this->title = $title;
        $this->references = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function addTranslation($language, $title)
    {
        $this->translations[] = new DDC117Translation($this, $language, $title);
    }

    public function getText()
    {
        return $this->details->getText();
    }
}

/**
 * @Entity
 */
class DDC117ArticleDetails
{
    /**
     * @Id
     * @OneToOne(targetEntity="DDC117Article", inversedBy="details")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
     */
    private $article;

    /**
     * @Column(type="text")
     */
    private $text;

    public function __construct($article, $text)
    {
        $this->article = $article;
        $article->setDetails($this);

        $this->update($text);
    }

    public function update($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}

/**
 * @Entity
 */
class DDC117Reference
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     * @JoinColumn(name="source_id", referencedColumnName="article_id")
     */
    private $source;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article", inversedBy="references")
     * @JoinColumn(name="target_id", referencedColumnName="article_id")
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

    public function setDescription($desc)
    {
        $this->description = $desc;
    }

    public function getDescription()
    {
        return $this->description;
    }
}

/**
 * @Entity
 */
class DDC117Translation
{
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC117Article")
     * @JoinColumn(name="article_id", referencedColumnName="article_id")
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
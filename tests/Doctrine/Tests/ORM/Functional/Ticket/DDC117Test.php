<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117Reference;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\Models\DDC117\DDC117ApproveChanges;
use Doctrine\Tests\Models\DDC117\DDC117Editor;

require_once __DIR__ . '/../../../TestInit.php';

class DDC117Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $article1;
    private $article2;
    private $reference;
    private $translation;
    private $articleDetails;

    protected function setUp() {
        $this->useModelSet('ddc117');
        parent::setUp();

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

        $mapRef = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $mapRef);
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $mapRef->target());
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $mapRef->source());
        $this->assertSame($mapRef, $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria));

        $this->_em->clear();

        $dql = "SELECT r, s FROM "."Doctrine\Tests\Models\DDC117\DDC117Reference r JOIN r.source s WHERE r.source = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 1)->getSingleResult();

        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $mapRef);
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $mapRef->target());
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $mapRef->source());
        $this->assertSame($dqlRef, $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria));

        $this->_em->clear();

        $dql = "SELECT r, s FROM "."Doctrine\Tests\Models\DDC117\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $dqlRef);
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $dqlRef->target());
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Article", $dqlRef->source());
        $this->assertSame($dqlRef, $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria));

        $dql = "SELECT r, s FROM "."Doctrine\Tests\Models\DDC117\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        $this->_em->contains($dqlRef);
    }

    /**
     * @group DDC-117
     */
    public function testUpdateAssocationEntity()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $mapRef = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);
        $this->assertNotNull($mapRef);
        $mapRef->setDescription("New Description!!");
        $this->_em->flush();
        $this->_em->clear();

        $mapRef = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);

        $this->assertEquals('New Description!!', $mapRef->getDescription());
    }

    /**
     * @group DDC-117
     */
    public function testFetchDql()
    {
        $dql = "SELECT r, s FROM "."Doctrine\Tests\Models\DDC117\DDC117Reference r JOIN r.source s WHERE s.title = ?1";
        $refs = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getResult();

        $this->assertTrue(count($refs) > 0, "Has to contain at least one Reference.");

        foreach ($refs AS $ref) {
            $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $ref, "Contains only Reference instances.");
            $this->assertTrue($this->_em->contains($ref), "Contains Reference in the IdentityMap.");
        }
    }

    /**
     * @group DDC-117
     */
    public function testRemoveCompositeElement()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $refRep = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);

        $this->_em->remove($refRep);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria));
    }

    /**
     * @group DDC-117
     */
    public function testDqlRemoveCompositeElement()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $dql = "DELETE "."Doctrine\Tests\Models\DDC117\DDC117Reference r WHERE r.source = ?1 AND r.target = ?2";
        $this->_em->createQuery($dql)
                  ->setParameter(1, $this->article1->id())
                  ->setParameter(2, $this->article2->id())
                  ->execute();

        $this->assertNull($this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria));
    }

    /**
     * @group DDC-117
     */
    public function testInverseSideAccess()
    {
        $this->article1 = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Article", $this->article1->id());

        $this->assertEquals(1, count($this->article1->references()));

        foreach ($this->article1->references() AS $this->reference) {
            $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $this->reference);
            $this->assertSame($this->article1, $this->reference->source());
        }

        $this->_em->clear();

        $dql = 'SELECT a, r FROM '. 'Doctrine\Tests\Models\DDC117\DDC117Article a INNER JOIN a.references r WHERE a.id = ?1';
        $articleDql = $this->_em->createQuery($dql)
                                ->setParameter(1, $this->article1->id())
                                ->getSingleResult();

        $this->assertEquals(1, count($this->article1->references()));

        foreach ($this->article1->references() AS $this->reference) {
            $this->assertInstanceOf("Doctrine\Tests\Models\DDC117\DDC117Reference", $this->reference);
            $this->assertSame($this->article1, $this->reference->source());
        }
    }

    /**
     * @group DDC-117
     */
    public function testMixedCompositeKey()
    {
        $idCriteria = array('article' => $this->article1->id(), 'language' => 'en');

        $this->translation = $this->_em->find('Doctrine\Tests\Models\DDC117\DDC117Translation', $idCriteria);
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Translation', $this->translation);

        $this->assertSame($this->translation, $this->_em->find('Doctrine\Tests\Models\DDC117\DDC117Translation', $idCriteria));

        $this->_em->clear();

        $dql = 'SELECT t, a FROM ' . 'Doctrine\Tests\Models\DDC117\DDC117Translation t JOIN t.article a WHERE t.article = ?1 AND t.language = ?2';
        $dqlTrans = $this->_em->createQuery($dql)
                              ->setParameter(1, $this->article1->id())
                              ->setParameter(2, 'en')
                              ->getSingleResult();

        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Translation', $this->translation);
    }

    /**
     * @group DDC-117
     */
    public function testMixedCompositeKeyViolateUniqueness()
    {
        $this->article1 = $this->_em->find('Doctrine\Tests\Models\DDC117\DDC117Article', $this->article1->id());
        $this->article1->addTranslation('en', 'Bar');
        $this->article1->addTranslation('en', 'Baz');

        $exceptionThrown = false;
        try {
            // exception depending on the underyling Database Driver
            $this->_em->flush();
        } catch(\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, "The underlying database driver throws an exception.");
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

    /**
     * @group DDC-117
     */
    public function testOneToOneCascadeRemove()
    {
        $article = $this->_em->find(get_class($this->article1), $this->article1->id());
        $this->_em->remove($article);
        $this->_em->flush();

        $this->assertFalse($this->_em->contains($article->getDetails()));
    }

    /**
     * @group DDC-117
     */
    public function testOneToOneCascadePersist()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->prefersSequences()) {
            $this->markTestSkipped('Test only works with databases that prefer sequences as ID strategy.');
        }

        $this->article1 = new DDC117Article("Foo");

        $this->articleDetails = new DDC117ArticleDetails($this->article1, "Very long text");

        $this->_em->persist($this->article1);
        $this->_em->flush();
    }

    /**
     * @group DDC-117
     */
    public function testReferencesToForeignKeyEntities()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());
        $reference = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);

        $idCriteria = array('article' => $this->article1->id(), 'language' => 'en');
        $translation = $this->_em->find('Doctrine\Tests\Models\DDC117\DDC117Translation', $idCriteria);

        $approveChanges = new DDC117ApproveChanges($reference->source()->getDetails(), $reference, $translation);
        $this->_em->persist($approveChanges);
        $this->_em->flush();
        $this->_em->clear();

        $approveChanges = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117ApproveChanges", $approveChanges->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117ArticleDetails', $approveChanges->getArticleDetails());
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Reference', $approveChanges->getReference());
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Translation', $approveChanges->getTranslation());
    }

    /**
     * @group DDC-117
     */
    public function testLoadOneToManyCollectionOfForeignKeyEntities()
    {
        /* @var $article DDC117Article */
        $article = $this->_em->find(get_class($this->article1), $this->article1->id());

        $translations = $article->getTranslations();
        $this->assertFalse($translations->isInitialized());
        $this->assertContainsOnly('Doctrine\Tests\Models\DDC117\DDC117Translation', $translations);
        $this->assertTrue($translations->isInitialized());
    }

    /**
     * @group DDC-117
     */
    public function testLoadManyToManyCollectionOfForeignKeyEntities()
    {
        $editor = $this->loadEditorFixture();

        $this->assertFalse($editor->reviewingTranslations->isInitialized());
        $this->assertContainsOnly("Doctrine\Tests\Models\DDC117\DDC117Translation", $editor->reviewingTranslations);
        $this->assertTrue($editor->reviewingTranslations->isInitialized());

        $this->_em->clear();

        $dql = "SELECT e, t FROM Doctrine\Tests\Models\DDC117\DDC117Editor e JOIN e.reviewingTranslations t WHERE e.id = ?1";
        $editor = $this->_em->createQuery($dql)->setParameter(1, $editor->id)->getSingleResult();
        $this->assertTrue($editor->reviewingTranslations->isInitialized());
        $this->assertContainsOnly("Doctrine\Tests\Models\DDC117\DDC117Translation", $editor->reviewingTranslations);
    }

    /**
     * @group DDC-117
     */
    public function testClearManyToManyCollectionOfForeignKeyEntities()
    {
        $editor = $this->loadEditorFixture();
        $this->assertEquals(3, count($editor->reviewingTranslations));

        $editor->reviewingTranslations->clear();
        $this->_em->flush();
        $this->_em->clear();

        $editor = $this->_em->find(get_class($editor), $editor->id);
        $this->assertEquals(0, count($editor->reviewingTranslations));
    }

    /**
     * @group DDC-117
     */
    public function testLoadInverseManyToManyCollection()
    {
        $editor = $this->loadEditorFixture();
        
        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Translation', $editor->reviewingTranslations[0]);

        $reviewedBy = $editor->reviewingTranslations[0]->getReviewedByEditors();
        $this->assertEquals(1, count($reviewedBy));
        $this->assertSame($editor, $reviewedBy[0]);

        $this->_em->clear();

        $dql = "SELECT t, e FROM Doctrine\Tests\Models\DDC117\DDC117Translation t ".
               "JOIN t.reviewedByEditors e WHERE t.article = ?1 AND t.language = ?2";
        $trans = $this->_em->createQuery($dql)
                           ->setParameter(1, $this->translation->getArticleId())
                           ->setParameter(2, $this->translation->getLanguage())
                           ->getSingleResult();

        $this->assertInstanceOf('Doctrine\Tests\Models\DDC117\DDC117Translation', $trans);
        $this->assertContainsOnly('Doctrine\Tests\Models\DDC117\DDC117Editor', $trans->reviewedByEditors);
        $this->assertEquals(1, count($trans->reviewedByEditors));
    }

    /**
     * @group DDC-117
     */
    public function testLoadOneToManyOfSourceEntityWithAssociationIdentifier()
    {
        $editor = $this->loadEditorFixture();

        $editor->addLastTranslation($editor->reviewingTranslations[0]);
        $this->_em->flush();
        $this->_em->clear();

        $editor = $this->_em->find(get_class($editor), $editor->id);
        $lastTranslatedBy = $editor->reviewingTranslations[0]->getLastTranslatedBy();
        $lastTranslatedBy->count();

        $this->assertEquals(1, count($lastTranslatedBy));
    }

    /**
     * @return DDC117Editor
     */
    private function loadEditorFixture()
    {
        $editor = new DDC117Editor("beberlei");

        /* @var $article1 DDC117Article */
        $article1 = $this->_em->find(get_class($this->article1), $this->article1->id());
        foreach ($article1->getTranslations() AS $translation) {
            $editor->reviewingTranslations[] = $translation;
        }

        /* @var $article2 DDC117Article */
        $article2 = $this->_em->find(get_class($this->article2), $this->article2->id());
        $article2->addTranslation("de", "Vanille-Krapferl"); // omnomnom
        $article2->addTranslation("fr", "Sorry can't speak french!");

        foreach ($article2->getTranslations() AS $translation) {
            $this->_em->persist($translation); // otherwise persisting the editor won't work, reachability!
            $editor->reviewingTranslations[] = $translation;
        }

        $this->_em->persist($editor);
        $this->_em->flush();
        $this->_em->clear();

        return $this->_em->find(get_class($editor), $editor->id);
    }

    /**
     * @group DDC-1519
     */
    public function testMergeForeignKeyIdentifierEntity()
    {
        $idCriteria = array('source' => $this->article1->id(), 'target' => $this->article2->id());

        $refRep = $this->_em->find("Doctrine\Tests\Models\DDC117\DDC117Reference", $idCriteria);

        $this->_em->detach($refRep);
        $refRep = $this->_em->merge($refRep);

        $this->assertEquals($this->article1->id(), $refRep->source()->id());
        $this->assertEquals($this->article2->id(), $refRep->target()->id());
    }
}

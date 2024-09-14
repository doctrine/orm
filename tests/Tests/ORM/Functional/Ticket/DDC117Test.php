<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\DDC117\DDC117ApproveChanges;
use Doctrine\Tests\Models\DDC117\DDC117Article;
use Doctrine\Tests\Models\DDC117\DDC117ArticleDetails;
use Doctrine\Tests\Models\DDC117\DDC117Editor;
use Doctrine\Tests\Models\DDC117\DDC117Link;
use Doctrine\Tests\Models\DDC117\DDC117Reference;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

use function assert;
use function count;
use function get_class;

/** @group DDC-117 */
class DDC117Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    /** @var DDC117Article */
    private $article1;

    /** @var DDC117Article */
    private $article2;

    /** @var DDC117Reference */
    private $reference;

    /** @var DDC117Translation */
    private $translation;

    /** @var DDC117ArticleDetails */
    private $articleDetails;

    protected function setUp(): void
    {
        $this->useModelSet('ddc117');

        parent::setUp();

        $this->article1 = new DDC117Article('Foo');
        $this->article2 = new DDC117Article('Bar');

        $this->_em->persist($this->article1);
        $this->_em->persist($this->article2);
        $this->_em->flush();

        $link = new DDC117Link($this->article1, $this->article2, 'Link-Description');
        $this->_em->persist($link);

        $this->reference = new DDC117Reference($this->article1, $this->article2, 'Test-Description');
        $this->_em->persist($this->reference);

        $this->translation = new DDC117Translation($this->article1, 'en', 'Bar');
        $this->_em->persist($this->translation);

        $this->articleDetails = new DDC117ArticleDetails($this->article1, 'Very long text');
        $this->_em->persist($this->articleDetails);
        $this->_em->flush();

        $this->_em->clear();
    }

    /** @group DDC-117 */
    public function testAssociationOnlyCompositeKey(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];

        $mapRef = $this->_em->find(DDC117Reference::class, $idCriteria);
        self::assertInstanceOf(DDC117Reference::class, $mapRef);
        self::assertInstanceOf(DDC117Article::class, $mapRef->target());
        self::assertInstanceOf(DDC117Article::class, $mapRef->source());
        self::assertSame($mapRef, $this->_em->find(DDC117Reference::class, $idCriteria));

        $this->_em->clear();

        $dql    = 'SELECT r, s FROM ' . DDC117Reference::class . ' r JOIN r.source s WHERE r.source = ?1';
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 1)->getSingleResult();

        self::assertInstanceOf(DDC117Reference::class, $mapRef);
        self::assertInstanceOf(DDC117Article::class, $mapRef->target());
        self::assertInstanceOf(DDC117Article::class, $mapRef->source());
        self::assertSame($dqlRef, $this->_em->find(DDC117Reference::class, $idCriteria));

        $this->_em->clear();

        $dql    = 'SELECT r, s FROM ' . DDC117Reference::class . ' r JOIN r.source s WHERE s.title = ?1';
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        self::assertInstanceOf(DDC117Reference::class, $dqlRef);
        self::assertInstanceOf(DDC117Article::class, $dqlRef->target());
        self::assertInstanceOf(DDC117Article::class, $dqlRef->source());
        self::assertSame($dqlRef, $this->_em->find(DDC117Reference::class, $idCriteria));

        $dql    = 'SELECT r, s FROM ' . DDC117Reference::class . ' r JOIN r.source s WHERE s.title = ?1';
        $dqlRef = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getSingleResult();

        $this->_em->contains($dqlRef);
    }

    /** @group DDC-117 */
    public function testUpdateAssociationEntity(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];

        $mapRef = $this->_em->find(DDC117Reference::class, $idCriteria);
        self::assertNotNull($mapRef);
        $mapRef->setDescription('New Description!!');
        $this->_em->flush();
        $this->_em->clear();

        $mapRef = $this->_em->find(DDC117Reference::class, $idCriteria);

        self::assertEquals('New Description!!', $mapRef->getDescription());
    }

    /** @group DDC-117 */
    public function testFetchDql(): void
    {
        $dql  = 'SELECT r, s FROM Doctrine\Tests\Models\DDC117\DDC117Reference r JOIN r.source s WHERE s.title = ?1';
        $refs = $this->_em->createQuery($dql)->setParameter(1, 'Foo')->getResult();

        self::assertGreaterThan(0, $refs, 'Has to contain at least one Reference.');

        foreach ($refs as $ref) {
            self::assertInstanceOf(DDC117Reference::class, $ref, 'Contains only Reference instances.');
            self::assertTrue($this->_em->contains($ref), 'Contains Reference in the IdentityMap.');
        }
    }

    /** @group DDC-117 */
    public function testRemoveCompositeElement(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];

        $refRep = $this->_em->find(DDC117Reference::class, $idCriteria);

        $this->_em->remove($refRep);
        $this->_em->flush();
        $this->_em->clear();

        self::assertNull($this->_em->find(DDC117Reference::class, $idCriteria));
    }

    /**
     * @group DDC-117
     * @group non-cacheable
     */
    public function testDqlRemoveCompositeElement(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];

        $dql = 'DELETE Doctrine\Tests\Models\DDC117\DDC117Reference r WHERE r.source = ?1 AND r.target = ?2';
        $this->_em->createQuery($dql)
                  ->setParameter(1, $this->article1->id())
                  ->setParameter(2, $this->article2->id())
                  ->execute();

        self::assertNull($this->_em->find(DDC117Reference::class, $idCriteria));
    }

    /** @group DDC-117 */
    public function testInverseSideAccess(): void
    {
        $this->article1 = $this->_em->find(DDC117Article::class, $this->article1->id());

        self::assertCount(1, $this->article1->references());

        foreach ($this->article1->references() as $this->reference) {
            self::assertInstanceOf(DDC117Reference::class, $this->reference);
            self::assertSame($this->article1, $this->reference->source());
        }

        $this->_em->clear();

        $dql        = 'SELECT a, r FROM Doctrine\Tests\Models\DDC117\DDC117Article a INNER JOIN a.references r WHERE a.id = ?1';
        $articleDql = $this->_em->createQuery($dql)
                                ->setParameter(1, $this->article1->id())
                                ->getSingleResult();

        self::assertCount(1, $this->article1->references());

        foreach ($this->article1->references() as $this->reference) {
            self::assertInstanceOf(DDC117Reference::class, $this->reference);
            self::assertSame($this->article1, $this->reference->source());
        }
    }

    /** @group DDC-117 */
    public function testMixedCompositeKey(): void
    {
        $idCriteria = ['article' => $this->article1->id(), 'language' => 'en'];

        $this->translation = $this->_em->find(DDC117Translation::class, $idCriteria);
        self::assertInstanceOf(DDC117Translation::class, $this->translation);

        self::assertSame($this->translation, $this->_em->find(DDC117Translation::class, $idCriteria));

        $this->_em->clear();

        $dql      = 'SELECT t, a FROM Doctrine\Tests\Models\DDC117\DDC117Translation t JOIN t.article a WHERE t.article = ?1 AND t.language = ?2';
        $dqlTrans = $this->_em->createQuery($dql)
                              ->setParameter(1, $this->article1->id())
                              ->setParameter(2, 'en')
                              ->getSingleResult();

        self::assertInstanceOf(DDC117Translation::class, $this->translation);
    }

    /** @group DDC-117 */
    public function testMixedCompositeKeyViolateUniqueness(): void
    {
        $this->article1 = $this->_em->find(DDC117Article::class, $this->article1->id());
        $this->article1->addTranslation('en', 'Bar');
        $this->article1->addTranslation('en', 'Baz');

        // exception depending on the underlying Database Driver
        $this->expectException(Exception::class);
        $this->_em->flush();
    }

    /** @group DDC-117 */
    public function testOneToOneForeignObjectId(): void
    {
        $this->article1 = new DDC117Article('Foo');
        $this->_em->persist($this->article1);
        $this->_em->flush();

        $this->articleDetails = new DDC117ArticleDetails($this->article1, 'Very long text');
        $this->_em->persist($this->articleDetails);
        $this->_em->flush();

        $this->articleDetails->update('not so very long text!');
        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em->find(get_class($this->article1), $this->article1->id());
        assert($article instanceof DDC117Article);
        self::assertEquals('not so very long text!', $article->getText());
    }

    /** @group DDC-117 */
    public function testOneToOneCascadeRemove(): void
    {
        $article = $this->_em->find(get_class($this->article1), $this->article1->id());
        $this->_em->remove($article);
        $this->_em->flush();

        self::assertFalse($this->_em->contains($article->getDetails()));
    }

    /** @group DDC-117 */
    public function testOneToOneCascadePersist(): void
    {
        $this->article1       = new DDC117Article('Foo');
        $this->articleDetails = new DDC117ArticleDetails($this->article1, 'Very long text');

        $this->_em->persist($this->article1);
        $this->_em->flush();

        self::assertSame($this->articleDetails, $this->_em->find(DDC117ArticleDetails::class, $this->article1));
    }

    /** @group DDC-117 */
    public function testReferencesToForeignKeyEntities(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];
        $reference  = $this->_em->find(DDC117Reference::class, $idCriteria);

        $idCriteria  = ['article' => $this->article1->id(), 'language' => 'en'];
        $translation = $this->_em->find(DDC117Translation::class, $idCriteria);

        $approveChanges = new DDC117ApproveChanges($reference->source()->getDetails(), $reference, $translation);
        $this->_em->persist($approveChanges);
        $this->_em->flush();
        $this->_em->clear();

        $approveChanges = $this->_em->find(DDC117ApproveChanges::class, $approveChanges->getId());

        self::assertInstanceOf(DDC117ArticleDetails::class, $approveChanges->getArticleDetails());
        self::assertInstanceOf(DDC117Reference::class, $approveChanges->getReference());
        self::assertInstanceOf(DDC117Translation::class, $approveChanges->getTranslation());
    }

    /** @group DDC-117 */
    public function testLoadOneToManyCollectionOfForeignKeyEntities(): void
    {
        $article = $this->_em->find(get_class($this->article1), $this->article1->id());
        assert($article instanceof DDC117Article);

        $translations = $article->getTranslations();
        self::assertFalse($translations->isInitialized());
        self::assertContainsOnly(DDC117Translation::class, $translations);
        self::assertTrue($translations->isInitialized());
    }

    /** @group DDC-117 */
    public function testLoadManyToManyCollectionOfForeignKeyEntities(): void
    {
        $editor = $this->loadEditorFixture();

        self::assertFalse($editor->reviewingTranslations->isInitialized());
        self::assertContainsOnly(DDC117Translation::class, $editor->reviewingTranslations);
        self::assertTrue($editor->reviewingTranslations->isInitialized());

        $this->_em->clear();

        $dql    = 'SELECT e, t FROM Doctrine\Tests\Models\DDC117\DDC117Editor e JOIN e.reviewingTranslations t WHERE e.id = ?1';
        $editor = $this->_em->createQuery($dql)->setParameter(1, $editor->id)->getSingleResult();
        self::assertTrue($editor->reviewingTranslations->isInitialized());
        self::assertContainsOnly(DDC117Translation::class, $editor->reviewingTranslations);
    }

    /** @group DDC-117 */
    public function testClearManyToManyCollectionOfForeignKeyEntities(): void
    {
        $editor = $this->loadEditorFixture();
        self::assertCount(3, $editor->reviewingTranslations);

        $editor->reviewingTranslations->clear();
        $this->_em->flush();
        $this->_em->clear();

        $editor = $this->_em->find(get_class($editor), $editor->id);
        self::assertCount(0, $editor->reviewingTranslations);
    }

    /** @group DDC-117 */
    public function testLoadInverseManyToManyCollection(): void
    {
        $editor = $this->loadEditorFixture();

        self::assertInstanceOf(DDC117Translation::class, $editor->reviewingTranslations[0]);

        $reviewedBy = $editor->reviewingTranslations[0]->getReviewedByEditors();
        self::assertCount(1, $reviewedBy);
        self::assertSame($editor, $reviewedBy[0]);

        $this->_em->clear();

        $dql   = 'SELECT t, e FROM Doctrine\Tests\Models\DDC117\DDC117Translation t ' .
               'JOIN t.reviewedByEditors e WHERE t.article = ?1 AND t.language = ?2';
        $trans = $this->_em->createQuery($dql)
                           ->setParameter(1, $this->translation->getArticleId())
                           ->setParameter(2, $this->translation->getLanguage())
                           ->getSingleResult();

        self::assertInstanceOf(DDC117Translation::class, $trans);
        self::assertContainsOnly(DDC117Editor::class, $trans->reviewedByEditors);
        self::assertCount(1, $trans->reviewedByEditors);
    }

    /** @group DDC-117 */
    public function testLoadOneToManyOfSourceEntityWithAssociationIdentifier(): void
    {
        $editor = $this->loadEditorFixture();

        $editor->addLastTranslation($editor->reviewingTranslations[0]);
        $this->_em->flush();
        $this->_em->clear();

        $editor           = $this->_em->find(get_class($editor), $editor->id);
        $lastTranslatedBy = $editor->reviewingTranslations[0]->getLastTranslatedBy();
        $lastTranslatedBy->count();

        self::assertCount(1, $lastTranslatedBy);
    }

    private function loadEditorFixture(): DDC117Editor
    {
        $editor = new DDC117Editor('beberlei');

        $article1 = $this->_em->find(get_class($this->article1), $this->article1->id());
        assert($article1 instanceof DDC117Article);
        foreach ($article1->getTranslations() as $translation) {
            $editor->reviewingTranslations[] = $translation;
        }

        $article2 = $this->_em->find(get_class($this->article2), $this->article2->id());
        assert($article2 instanceof DDC117Article);
        $article2->addTranslation('de', 'Vanille-Krapferl'); // omnomnom
        $article2->addTranslation('fr', "Sorry can't speak french!");

        foreach ($article2->getTranslations() as $translation) {
            $this->_em->persist($translation); // otherwise persisting the editor won't work, reachability!
            $editor->reviewingTranslations[] = $translation;
        }

        $this->_em->persist($editor);
        $this->_em->flush();
        $this->_em->clear();

        return $this->_em->find(get_class($editor), $editor->id);
    }

    /** @group DDC-1519 */
    public function testMergeForeignKeyIdentifierEntity(): void
    {
        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];

        $refRep = $this->_em->find(DDC117Reference::class, $idCriteria);

        $this->_em->clear(DDC117Reference::class);
        $refRep = $this->_em->merge($refRep);

        self::assertEquals($this->article1->id(), $refRep->source()->id());
        self::assertEquals($this->article2->id(), $refRep->target()->id());
    }

    /** @group DDC-1652 */
    public function testArrayHydrationWithCompositeKey(): void
    {
        $dql    = 'SELECT r,s,t FROM Doctrine\Tests\Models\DDC117\DDC117Reference r INNER JOIN r.source s INNER JOIN r.target t';
        $before = count($this->_em->createQuery($dql)->getResult());

        $this->article1 = $this->_em->find(DDC117Article::class, $this->article1->id());
        $this->article2 = $this->_em->find(DDC117Article::class, $this->article2->id());

        $this->reference = new DDC117Reference($this->article2, $this->article1, 'Test-Description');
        $this->_em->persist($this->reference);

        $this->reference = new DDC117Reference($this->article1, $this->article1, 'Test-Description');
        $this->_em->persist($this->reference);

        $this->reference = new DDC117Reference($this->article2, $this->article2, 'Test-Description');
        $this->_em->persist($this->reference);

        $this->_em->flush();

        $dql  = 'SELECT r,s,t FROM Doctrine\Tests\Models\DDC117\DDC117Reference r INNER JOIN r.source s INNER JOIN r.target t';
        $data = $this->_em->createQuery($dql)->getArrayResult();

        self::assertCount($before + 3, $data);
    }

    /** @group DDC-2246 */
    public function testGetEntityState(): void
    {
        if ($this->isSecondLevelCacheEnabled) {
            self::markTestIncomplete('Second level cache - not supported yet');
        }

        $this->article1 = $this->_em->find(DDC117Article::class, $this->article1->id());
        $this->article2 = $this->_em->find(DDC117Article::class, $this->article2->id());

        $this->reference = new DDC117Reference($this->article2, $this->article1, 'Test-Description');

        self::assertEquals(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($this->reference));

        $idCriteria = ['source' => $this->article1->id(), 'target' => $this->article2->id()];
        $reference  = $this->_em->find(DDC117Reference::class, $idCriteria);

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($reference));
    }

    /** @group DDC-117 */
    public function testIndexByOnCompositeKeyField(): void
    {
        $article = $this->_em->find(DDC117Article::class, $this->article1->id());

        self::assertInstanceOf(DDC117Article::class, $article);
        self::assertCount(1, $article->getLinks());
        self::assertTrue($article->getLinks()->offsetExists($this->article2->id()));
    }
}

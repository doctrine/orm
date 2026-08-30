<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Exception\StrictLoadingViolation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\StrictLoading\LazyLoad;
use Doctrine\ORM\StrictLoading\LazyLoadKind;
use Doctrine\ORM\StrictLoading\StrictLoading;
use Doctrine\ORM\StrictLoading\StrictLoadingMode;
use Doctrine\ORM\StrictLoading\StrictLoadingViolationHandler;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Proof of concept for strict loading, see
 * https://github.com/doctrine/orm/discussions/10931
 */
class StrictLoadingTest extends OrmFunctionalTestCase
{
    /** @var list<int> */
    private array $userIds = [];

    /** @var list<int> */
    private array $articleIds = [];

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        foreach (['alice', 'bob'] as $index => $username) {
            $user           = new CmsUser();
            $user->username = $username;
            $user->name     = $username;
            $user->status   = 'dev';

            $phonenumber              = new CmsPhonenumber();
            $phonenumber->phonenumber = '12345' . $index;
            $user->addPhonenumber($phonenumber);

            $article        = new CmsArticle();
            $article->topic = 'Topic of ' . $username;
            $article->text  = 'Text of ' . $username;
            $article->setAuthor($user);

            $this->_em->persist($user);
            $this->_em->persist($article);

            $this->_em->flush();

            $this->userIds[]    = $user->id;
            $this->articleIds[] = $article->id;
        }

        $this->_em->clear();
    }

    protected function tearDown(): void
    {
        $this->_em->getConfiguration()->getStrictLoading()->setMode(StrictLoadingMode::Disabled);

        parent::tearDown();
    }

    private function strictLoading(StrictLoadingMode $mode = StrictLoadingMode::All): StrictLoading
    {
        $strictLoading = $this->_em->getConfiguration()->getStrictLoading();
        $strictLoading->setMode($mode);
        $strictLoading->reset();

        return $strictLoading;
    }

    public function testLazyLoadingIsAllowedByDefault(): void
    {
        self::assertSame(
            StrictLoadingMode::Disabled,
            $this->_em->getConfiguration()->getStrictLoading()->getMode(),
        );

        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);

        self::assertTrue($this->_em->isUninitializedObject($article->user));
        self::assertSame('alice', $article->user->username);
    }

    public function testLoadingAToOneReferenceIsAViolation(): void
    {
        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);
        self::assertTrue($this->_em->isUninitializedObject($article->user));

        $this->strictLoading();

        $this->expectException(StrictLoadingViolation::class);
        $this->expectExceptionMessage('lazily loading entity ' . CmsUser::class . '(' . $this->userIds[0] . ')');

        // @phpstan-ignore expr.resultUnused
        $article->user->username;
    }

    public function testARejectedLazyLoadLeavesTheEntityUsable(): void
    {
        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);

        $strictLoading = $this->strictLoading();

        try {
            // @phpstan-ignore expr.resultUnused
            $article->user->username;
            self::fail('Expected a strict loading violation.');
        } catch (StrictLoadingViolation) {
        }

        // The failed initialization attempt must not have corrupted the entity:
        // it is still an uninitialized reference and can be loaded afterwards.
        self::assertTrue($this->_em->isUninitializedObject($article->user));
        self::assertSame($this->userIds[0], $article->user->id);

        $username = $strictLoading->allow(static fn (): string => $article->user->username);

        self::assertSame('alice', $username);
        self::assertFalse($this->_em->isUninitializedObject($article->user));
    }

    public function testLoadingACollectionIsAViolation(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userIds[0]);

        $this->strictLoading();

        $this->expectException(StrictLoadingViolation::class);
        $this->expectExceptionMessage('lazily loading collection ' . CmsUser::class . '#articles');

        $user->articles->toArray();
    }

    public function testARejectedCollectionLoadLeavesTheCollectionUsable(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userIds[0]);

        $strictLoading = $this->strictLoading();

        try {
            $user->articles->toArray();
            self::fail('Expected a strict loading violation.');
        } catch (StrictLoadingViolation) {
        }

        self::assertFalse($user->articles->isInitialized());

        $articles = $strictLoading->allow(static fn (): array => $user->articles->toArray());

        self::assertCount(1, $articles);
        self::assertTrue($user->articles->isInitialized());
    }

    public function testFetchJoinedDataIsNotAViolation(): void
    {
        $article = $this->_em->createQuery(
            'SELECT a, u FROM ' . CmsArticle::class . ' a JOIN a.user u WHERE a.id = :id',
        )->setParameter('id', $this->articleIds[0])->getSingleResult();

        $user = $this->_em->createQuery(
            'SELECT u, a FROM ' . CmsUser::class . ' u LEFT JOIN u.articles a WHERE u.id = :id',
        )->setParameter('id', $this->userIds[0])->getSingleResult();

        $this->strictLoading();

        self::assertSame('alice', $article->user->username);
        self::assertCount(1, $user->articles);
    }

    public function testEagerFetchModeIsNotAViolation(): void
    {
        $query = $this->_em->createQuery('SELECT a FROM ' . CmsArticle::class . ' a')
            ->setFetchMode(CmsArticle::class, 'user', ClassMetadata::FETCH_EAGER);

        $articles = $query->getResult();

        $this->strictLoading();

        foreach ($articles as $article) {
            self::assertNotSame('', $article->user->username);
        }
    }

    public function testNPlusOneOnlyModeAllowsASingleLazyLoad(): void
    {
        $this->strictLoading(StrictLoadingMode::NPlusOneOnly);

        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);

        self::assertSame('alice', $article->user->username);
    }

    public function testNPlusOneOnlyModeRejectsTheSecondLazyLoad(): void
    {
        $articles = $this->_em->createQuery('SELECT a FROM ' . CmsArticle::class . ' a ORDER BY a.id')->getResult();

        $this->strictLoading(StrictLoadingMode::NPlusOneOnly);

        self::assertSame('alice', $articles[0]->user->username);

        $this->expectException(StrictLoadingViolation::class);
        $this->expectExceptionMessage('which makes it an N+1 query');

        // @phpstan-ignore expr.resultUnused
        $articles[1]->user->username;
    }

    public function testLoggingHandlerLetsTheLoadHappen(): void
    {
        $users = $this->_em->createQuery('SELECT u FROM ' . CmsUser::class . ' u ORDER BY u.id')->getResult();

        $handler       = new CollectedViolations();
        $strictLoading = $this->strictLoading();
        $strictLoading->setViolationHandler($handler);

        foreach ($users as $user) {
            self::assertCount(1, $user->articles);
        }

        self::assertCount(2, $handler->violations);
        self::assertSame(LazyLoadKind::Collection, $handler->violations[0]->kind);
        self::assertSame(CmsUser::class, $handler->violations[0]->className);
        self::assertSame('articles', $handler->violations[0]->fieldName);
    }

    public function testFlushIsNeverAViolation(): void
    {
        $user = $this->_em->find(CmsUser::class, $this->userIds[0]);

        $this->strictLoading();

        $user->name = 'changed';
        $this->_em->flush();

        self::assertFalse($user->articles->isInitialized());
    }

    public function testCascadingARemovalIsNeverAViolation(): void
    {
        $user    = $this->_em->find(CmsUser::class, $this->userIds[0]);
        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);

        $this->strictLoading();

        // CmsUser::$phonenumbers uses orphanRemoval, so removing the user has to
        // load the collection - which must not be reported as a violation.
        self::assertFalse($user->phonenumbers->isInitialized());

        $this->_em->remove($article);
        $this->_em->remove($user);
        $this->_em->flush();

        self::assertTrue($user->phonenumbers->isInitialized());
        self::assertNull($this->_em->find(CmsUser::class, $this->userIds[0]));
    }

    public function testExplicitInitializationIsNeverAViolation(): void
    {
        $article = $this->_em->find(CmsArticle::class, $this->articleIds[0]);
        $user    = $this->_em->find(CmsUser::class, $this->userIds[1]);

        $this->strictLoading();

        $this->_em->refresh($user);
        $this->_em->initializeObject($article->user);
        $this->_em->initializeObject($user->articles);

        self::assertFalse($this->_em->isUninitializedObject($article->user));
        self::assertTrue($user->articles->isInitialized());
    }

    public function testExtraLazyCountIsReportedSeparately(): void
    {
        $metadata                                         = $this->_em->getClassMetadata(CmsUser::class);
        $previousFetchMode                                = $metadata->associationMappings['articles']->fetch;
        $metadata->associationMappings['articles']->fetch = ClassMetadata::FETCH_EXTRA_LAZY;

        try {
            $user = $this->_em->find(CmsUser::class, $this->userIds[0]);

            $handler       = new CollectedViolations();
            $strictLoading = $this->strictLoading();
            $strictLoading->setViolationHandler($handler);

            self::assertSame(1, $user->articles->count());

            self::assertCount(1, $handler->violations);
            self::assertSame(LazyLoadKind::CollectionQuery, $handler->violations[0]->kind);
            self::assertSame('count', $handler->violations[0]->operation);
        } finally {
            $metadata->associationMappings['articles']->fetch = $previousFetchMode;
        }
    }

    public function testStrictLoadingCanBeLimitedToAPartOfTheRequest(): void
    {
        $strictLoading = $this->_em->getConfiguration()->getStrictLoading();

        // "Controller": fetch everything that the "view" is going to need.
        $user = $this->_em->createQuery(
            'SELECT u, a FROM ' . CmsUser::class . ' u LEFT JOIN u.articles a WHERE u.id = :id',
        )->setParameter('id', $this->userIds[0])->getSingleResult();

        // "View": no database access allowed from here on.
        $strictLoading->setMode(StrictLoadingMode::All);

        $rendered = $user->name;

        foreach ($user->articles as $article) {
            $rendered .= ' ' . $article->topic;
        }

        self::assertSame('alice Topic of alice', $rendered);
    }
}

final class CollectedViolations implements StrictLoadingViolationHandler
{
    /** @var list<LazyLoad> */
    public array $violations = [];

    public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
    {
        $this->violations[] = $lazyLoad;
    }
}

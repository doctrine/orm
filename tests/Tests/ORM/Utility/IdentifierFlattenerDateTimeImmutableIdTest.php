<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Tests\Models\DateTimeCompositeKey\Article;
use Doctrine\Tests\Models\DateTimeCompositeKey\ArticleAudit;
use Doctrine\Tests\OrmFunctionalTestCase;

use function dirname;

/**
 * Test the IdentifierFlattener utility class
 *
 * @requires PHP 8.1
 * @covers \Doctrine\ORM\Utility\IdentifierFlattener
 */
class IdentifierFlattenerDateTimeImmutableIdTest extends OrmFunctionalTestCase
{
    /**
     * @var EntityRepository
     */
    private $articleRepository;

    /**
     * @var EntityRepository
     */
    private $articleAuditRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_em = $this->getEntityManager(null, new AttributeDriver([dirname(__DIR__, 2) . '/Models/Enums']));
        $this->createSchemaForModels(
            Article::class,
            ArticleAudit::class
        );
        $this->articleRepository      = $this->_em->getRepository(Article::class);
        $this->articleAuditRepository = $this->_em->getRepository(ArticleAudit::class);
    }

    /** @group utilities */
    public function testFlattenIdentifierWithDateTimeId(): void
    {
        $article = new Article('Some title', 'Lorem ipsum');
        $article->changeTitle('New title');
        $article->changeContent('Lorem ipsum dolor sit amet');

        $this->storeEntities($article);

        $persistedAudit = $this->articleRepository->find($article->getId())->getAudit();
        $firstChange    = $persistedAudit->first();
        $class          = $this->_em->getClassMetadata(ArticleAudit::class);
        $id             = $class->getIdentifierValues($firstChange);
        self::assertCount(2, $persistedAudit);
        self::assertCount(3, $id);
    }

    /** @group utilities */
    public function testFindEntityWithCompositeId(): void
    {
        $article       = new Article('Some title', 'Lorem ipsum');
        $fakeAudit     = new ArticleAudit(
            $timestamp = new DateTimeImmutable('2022-03-02'),
            'title',
            $article
        );

        $this->storeEntities($article, $fakeAudit);

        $persistedAudit = $this->articleAuditRepository->find([
            'article' => $article,
            'issuedAt' => $timestamp,
            'changedKey' => 'title',
        ]);
        self::assertNotNull($persistedAudit);
    }

    private function storeEntities(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
        $this->_em->clear();
    }
}

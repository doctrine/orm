<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Utility;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Tests\Models\DateTimeCompositeKey\Article;
use Doctrine\Tests\Models\DateTimeCompositeKey\ArticleAudit;
use Doctrine\Tests\OrmFunctionalTestCase;

use function dirname;
use function sleep;

/**
 * Test the IdentifierFlattener utility class
 *
 * @requires PHP 8.1
 * @covers \Doctrine\ORM\Utility\IdentifierFlattener
 */
class IdentifierFlattenerDateTimeIdTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_em = $this->getEntityManager(null, new AttributeDriver([dirname(__DIR__, 2) . '/Models/Enums']));

        $this->createSchemaForModels(
            Article::class,
            ArticleAudit::class
        );
    }

    /** @group utilities */
    public function testFlattenIdentifierWithDateTimeId(): void
    {
        $article = new Article('Some title');
        $article->changeTitle('New title');
        sleep(1);
        $article->changeTitle('Newest title');

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $persistedArticle = $this->_em
            ->getRepository(Article::class)
            ->find($article->getId());

        $persistedAudit = $persistedArticle->getAudit();
        $firstChange    = $persistedAudit->first();
        self::assertCount(2, $persistedAudit);
        $class = $this->_em->getClassMetadata(ArticleAudit::class);

        $id = $class->getIdentifierValues($firstChange);
        self::assertCount(2, $id);
    }
}

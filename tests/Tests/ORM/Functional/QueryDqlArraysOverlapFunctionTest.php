<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Exception\NotSupported;
use Doctrine\Tests\DbalTypes\StringArrayType;
use Doctrine\Tests\Models\StringArray\Article;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;
use function sprintf;

class QueryDqlArraysOverlapFunctionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! method_exists(AbstractPlatform::class, 'getArraysOverlapExpression')) {
            self::markTestSkipped('ARRAYS_OVERLAP() requires doctrine/dbal 4.6 or newer.');
        }

        try {
            $this->_em->getConnection()->getDatabasePlatform()->getArraysOverlapExpression('a', 'b');
        } catch (NotSupported) {
            self::markTestSkipped('The platform does not support testing whether arrays overlap.');
        }

        self::registerDbalType(StringArrayType::NAME, StringArrayType::class);

        $this->createSchemaForModels(Article::class);
        $this->_em->createQuery('DELETE FROM ' . Article::class . ' a')->execute();

        $this->_em->persist(new Article(1, 'PHP and SQL', ['php', 'sql']));
        $this->_em->persist(new Article(2, 'Java', ['java']));
        $this->_em->persist(new Article(3, 'Untagged', null));
        $this->_em->persist(new Article(4, 'SQL', ['sql']));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testOverlapInWhereClause(): void
    {
        $titles = $this->_em->createQuery(sprintf(
            'SELECT a.title FROM %s a WHERE ARRAYS_OVERLAP(a.tags, :tags) = true ORDER BY a.id',
            Article::class,
        ))
            ->setParameter('tags', ['sql', 'python'], StringArrayType::NAME)
            ->getSingleColumnResult();

        self::assertSame(['PHP and SQL', 'SQL'], $titles);
    }

    public function testOverlapInSelectClause(): void
    {
        $result = $this->_em->createQuery(sprintf(
            'SELECT a.id, ARRAYS_OVERLAP(a.tags, :tags) AS overlap FROM %s a ORDER BY a.id',
            Article::class,
        ))
            ->setParameter('tags', ['php'], StringArrayType::NAME)
            ->getArrayResult();

        self::assertSame([
            ['id' => 1, 'overlap' => true],
            ['id' => 2, 'overlap' => false],
            ['id' => 3, 'overlap' => null],
            ['id' => 4, 'overlap' => false],
        ], $result);
    }
}

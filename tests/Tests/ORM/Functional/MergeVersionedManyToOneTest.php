<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\VersionedManyToOne\Article;
use Doctrine\Tests\Models\VersionedManyToOne\Category;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group MergeVersionedOneToMany */
class MergeVersionedManyToOneTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('versioned_many_to_one');

        parent::setUp();
    }

    /**
     * This test case asserts that a detached and unmodified entity could be merge without firing
     * OptimisticLockException.
     */
    public function testSetVersionOnCreate(): void
    {
        $category = new Category();
        $article  = new Article();

        $article->name     = 'Article';
        $article->category = $category;

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $articleMerged = $this->_em->merge($article);

        $articleMerged->name = 'Article Merged';

        $this->_em->flush();
        self::assertEquals(2, $articleMerged->version);
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\VersionedManyToOne\Article;
use Doctrine\Tests\Models\VersionedManyToOne\Category;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group MergeVersionedOneToMany
 */
class MergeVersionedManyToOneTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('versioned_many_to_one');

        parent::setUp();
    }

    /**
     * This test case asserts that a detached and unmodified entity could be merge without firing
     * OptimisticLockException.
     */
    public function testSetVersionOnCreate()
    {
        $category = new Category();
        $article  = new Article();

        $article->name     = 'Article';
        $article->category = $category;

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $articleMerged = $this->em->merge($article);

        $articleMerged->name = 'Article Merged';

        $this->em->flush();
        self::assertEquals(2, $articleMerged->version);
    }
}

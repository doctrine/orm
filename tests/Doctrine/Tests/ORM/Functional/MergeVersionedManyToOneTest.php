<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\VersionedManyToOne\Article;
use Doctrine\Tests\Models\VersionedManyToOne\Category;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group MergeVersionedOneToMany
 */
class MergeVersionedManyToOneTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

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

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $articleMerged = $this->_em->merge($article);

        $articleMerged->name = 'Article Merged';

        $this->_em->flush();
        $this->assertEquals(2, $articleMerged->version);
        $this->assertHasDeprecationMessages();
    }
}

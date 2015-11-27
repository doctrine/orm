<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\Models\VersionedOneToMany\Article;
use Doctrine\Tests\Models\VersionedOneToMany\Category;
use Doctrine\Tests\Models\VersionedOneToMany\Tag;

/**
 *
 * @group MergeVersionedOneToMany
 */
class MergeVersionedOneToManyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(Category::class),
                    $this->_em->getClassMetadata(Article::class),
                ]
            );
        } catch (ORMException $e) {
        }
    }

    /**
     * This test case tests that a versionable entity, that has a oneToOne relationship as it's id can be created
     *  without this bug fix (DDC-3318), you could not do this
     */
    public function testSetVersionOnCreate()
    {
        $category = new Category();
        $category->name = 'Category';

        $article = new Article();
        $article->name = 'Article';
        $article->category = $category;

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $mergeSucceed = false;
        try {
            $articleMerged = $this->_em->merge($article);
            $mergeSucceed = true;
        } catch (OptimisticLockException $e) {
        }
        $this->assertTrue($mergeSucceed);

        $articleMerged->name = 'Article Merged';

        $flushSucceed = false;
        try {
            $this->_em->flush();
            $flushSucceed = true;
        } catch (OptimisticLockException $e) {
        }
        $this->assertTrue($flushSucceed);
    }
}

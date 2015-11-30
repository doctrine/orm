<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\Models\VersionedOneToMany\Article;
use Doctrine\Tests\Models\VersionedOneToMany\Category;

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
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToMany\Category'),
                    $this->_em->getClassMetadata('Doctrine\Tests\Models\VersionedOneToMany\Article'),
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

        $articleMerged = $this->_em->merge($article);

        $articleMerged->name = 'Article Merged';

        $this->_em->flush();
        $this->assertEquals(2, $articleMerged->version);
    }
}

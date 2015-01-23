<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC3346\DDC3346Article;
use Doctrine\Tests\Models\DDC3346\DDC3346Author;

/**
 * @group DDC-3346
 */
class DDC3346Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(
            array(
                DDC3346Author::CLASSNAME,
                DDC3346Article::CLASSNAME,
            )
        );
    }

    public function testFindOneWithEagerFetchWillNotHydrateLimitedCollection()
    {
        $user = new DDC3346Author();
        $user->username = "bwoogy";

        $article1 = new DDC3346Article();
        $article1->setAuthor($user);

        $article2 = new DDC3346Article();
        $article2->setAuthor($user);

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();
        $this->_em->clear();

        /** @var DDC3346Author $author */
        $author = $this->_em->getRepository('Doctrine\Tests\Models\DDC3346\DDC3346Author')->findOneBy(
            array('username' => "bwoogy")
        );

        $this->assertCount(2, $author->articles);
    }

    public function testFindLimitedWithEagerFetchWillNotHydrateLimitedCollection()
    {
        $user = new DDC3346Author();
        $user->username = "bwoogy";

        $article1 = new DDC3346Article();
        $article1->setAuthor($user);

        $article2 = new DDC3346Article();
        $article2->setAuthor($user);

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();
        $this->_em->clear();

        /** @var DDC3346Author[] $authors */
        $authors = $this->_em->getRepository('Doctrine\Tests\Models\DDC3346\DDC3346Author')->findBy(
            array('username' => "bwoogy"),
            null,
            1
        );

        $this->assertCount(1, $authors);
        $this->assertCount(2, $authors[0]->articles);
    }

    public function testFindWithEagerFetchAndOffsetWillNotHydrateLimitedCollection()
    {
        $user = new DDC3346Author();
        $user->username = "bwoogy";

        $article1 = new DDC3346Article();
        $article1->setAuthor($user);

        $article2 = new DDC3346Article();
        $article2->setAuthor($user);

        $this->_em->persist($user);
        $this->_em->persist($article1);
        $this->_em->persist($article2);
        $this->_em->flush();
        $this->_em->clear();

        /** @var DDC3346Author[] $authors */
        $authors = $this->_em->getRepository('Doctrine\Tests\Models\DDC3346\DDC3346Author')->findBy(
            array('username' => "bwoogy"),
            null,
            null,
            1
        );

        $this->assertCount(1, $authors);
        $this->assertCount(2, $authors[0]->articles);
    }
}

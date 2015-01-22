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

    public function testFindOneByWithEagerFetch()
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
        $this->_em->close();

        /** @var DDC3346Author[] $authors */
        $authors = $this->_em->getRepository('Doctrine\Tests\Models\DDC3346\DDC3346Author')->findBy(
            array('username' => "bwoogy")
        );

        $this->assertCount(1, $authors);

        $this->assertCount(2, $authors[0]->articles);

        $this->_em->close();

        /** @var DDC3346Author $author */
        $author = $this->_em->getRepository('Doctrine\Tests\Models\DDC3346\DDC3346Author')->findOneBy(
            array('username' => "bwoogy")
        );

        $this->assertCount(2, $author->articles);
    }
}

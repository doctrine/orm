<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class GH5699Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIterate()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";

        $article2 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article2->text = "bar";
        $article2->topic = "foo";

        $this->_em->persist($article);
        $this->_em->persist($article2);
        $this->_em->flush();
        $this->_em->clear();

        $it = $this->_em->createQueryBuilder()
        ->from('Doctrine\Tests\Models\CMS\CmsArticle', 'a')
        ->select('a.text')
        ->getQuery()
        ->iterate();

        foreach ($it as $row) {
            $this->assertArrayHasKey(0, $row);
        }
    }
}

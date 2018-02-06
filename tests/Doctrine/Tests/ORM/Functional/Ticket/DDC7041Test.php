<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-7041
 */
class DDC7041Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testContainsInFinalSql()
    {
        $user = new CmsUser();
        $user->name = "John Galt";
        $user->username = "jgalt";
        $user->status = "inactive";

        $article = new CmsArticle();
        $article->topic = "This is John Galt speaking!";
        $article->text = "Yadda Yadda!";
        $article->setAuthor($user);

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();
        
        $crit = \Doctrine\Common\Collections\Criteria::create();
        // get all articles where text contains '%Yadda%'
        $crit->andWhere(\Doctrine\Common\Collections\Criteria::expr()->contains('text', 'Yadda'));
        $result = $user->articles->matching($crit);
        
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
}

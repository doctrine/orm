<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsGroup;
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
        
        $user2 = new CmsUser();
        $user2->name = "Johnny Depp";
        $user2->username = "jdepp";
        $user2->status = "inactive";

        $group = new CmsGroup();
        $group->name = "Main group";
        $group->addUser($user);
        $group->addUser($user2);

        $this->em->persist($user);
        $this->em->persist($user2);
        $this->em->persist($group);
        $this->em->flush();
        
        $id = $group->id;
        
        $this->em->clear();
        
        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsGroup a WHERE a.id = :id";
        $g = $this->em->createQuery($dql)
                  ->setParameter('id', $id)
                  ->setMaxResults(1)
                  ->getQuery()
                  ->getOneOrNullResult();
        
        $crit = \Doctrine\Common\Collections\Criteria::create();
        // get all articles where text contains '%Yadda%'
        $crit->andWhere(\Doctrine\Common\Collections\Criteria::expr()->contains('username', 'test'));
        $result = $g->getUsers()->matching($crit);
        
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $result);
    }
}

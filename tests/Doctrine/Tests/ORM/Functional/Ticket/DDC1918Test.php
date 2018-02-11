<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use function iterator_to_array;
use function sprintf;

/**
 * @group DDC-1918
 */
class DDC1918Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testLastPageCorrect()
    {
        $groups = [];
        for ($i = 0; $i < 3; $i++) {
            $group       = new CmsGroup();
            $group->name = 'test';
            $this->em->persist($group);

            $groups[] = $group;
        }

        for ($i = 0; $i < 10; $i++) {
            $user           = new CmsUser();
            $user->username = sprintf('user%d', $i);
            $user->name     = sprintf('user%d', $i);
            $user->status   = 'active';
            $user->groups   = $groups;

            $this->em->persist($user);
        }

        $this->em->flush();

        $query = $this->em->createQuery('SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g');
        $query->setFirstResult(6);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        self::assertCount(3, iterator_to_array($paginator));

        $query->setFirstResult(8);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        self::assertCount(2, iterator_to_array($paginator));

        $query->setFirstResult(10);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        self::assertCount(0, iterator_to_array($paginator));
    }
}

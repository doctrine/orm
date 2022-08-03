<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function iterator_to_array;

/**
 * @group DDC-1918
 */
class DDC1918Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testLastPageCorrect(): void
    {
        $groups = [];
        for ($i = 0; $i < 3; $i++) {
            $group       = new CmsGroup();
            $group->name = 'test';
            $this->_em->persist($group);

            $groups[] = $group;
        }

        for ($i = 0; $i < 10; $i++) {
            $user           = new CmsUser();
            $user->username = 'user' . $i;
            $user->name     = 'user' . $i;
            $user->status   = 'active';
            $user->groups   = $groups;

            $this->_em->persist($user);
        }

        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g');
        $query->setFirstResult(6);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        $this->assertEquals(3, count(iterator_to_array($paginator)));

        $query->setFirstResult(8);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        $this->assertEquals(2, count(iterator_to_array($paginator)));

        $query->setFirstResult(10);
        $query->setMaxResults(3);

        $paginator = new Paginator($query, true);
        $this->assertEquals(0, count(iterator_to_array($paginator)));
    }
}

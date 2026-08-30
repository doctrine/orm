<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1918')]
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

        $paginator = new OffsetPaginator(true);

        self::assertCount(3, $paginator->paginate($query, new Window(6, 3)));
        self::assertCount(2, $paginator->paginate($query, new Window(8, 3)));
        self::assertCount(0, $paginator->paginate($query, new Window(10, 3)));
    }
}

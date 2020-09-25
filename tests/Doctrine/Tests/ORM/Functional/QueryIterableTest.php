<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\IterableTester;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

final class QueryIterableTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testAlias() : void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u AS user FROM Doctrine\Tests\Models\CMS\CmsUser u');

        $users = $query->getResult();
        self::assertCount(1, $users);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testAliasInnerJoin() : void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $address->user = $user;
        $user->address = $address;

        $this->_em->persist($user);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT u AS user, a AS address FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.address a');

        $users = $query->getResult();
        self::assertCount(1, $users);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }
}

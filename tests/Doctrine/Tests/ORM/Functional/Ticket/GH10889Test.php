<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10889Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testDuplicateDqlAliasinSelectClauseShouldNotFail(): void
    {
        $address          = new CmsAddress();
        $address->city    = 'bonn';
        $address->country = 'Germany';
        $address->street  = 'somestreet!';
        $address->zip     = 12345;

        $user           = new CmsUser();
        $user->username = 'joedoe';
        $user->name     = 'joe';
        $user->setAddress($address);

        $this->_em->persist($address);
        $this->_em->persist($user);
        $this->_em->flush();

        $qb    = $this->_em->createQueryBuilder();
        $query = $qb->select(['u', 'a'])
            ->from(CmsUser::class, 'u')
            ->join('u.address', 'a')
            ->addSelect('a')
            ->getQuery();

        self::assertCount(1, $query->getResult());
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1306
 */
class DDC1306Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testIssue(): void
    {
        $phone              = new CmsPhonenumber();
        $phone->phonenumber = '1234';

        // puts user and phone into commit order calculator
        $this->_em->persist($phone);
        $this->_em->flush();

        $address          = new CmsAddress();
        $address->city    = 'bonn';
        $address->country = 'Germany';
        $address->street  = 'somestreet!';
        $address->zip     = 12345;

        $this->_em->persist($address);

        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'benjamin';
        $user->status   = 'active';
        $user->setAddress($address);

        // puts user and address into commit order calculator, but does not calculate user dependencies new
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->remove($user->getAddress());
        $this->_em->remove($user);
        $this->_em->flush();

        self::assertEmpty($this->_em->getRepository(CmsAddress::class)->findAll());
        self::assertEmpty($this->_em->getRepository(CmsUser::class)->findAll());
    }
}

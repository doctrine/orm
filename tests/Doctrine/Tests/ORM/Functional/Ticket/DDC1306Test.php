<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

/**
 * @group DDC-1306
 */
class DDC1306Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testIssue()
    {
        $phone = new CmsPhonenumber();
        $phone->phonenumber = "1234";

        // puts user and phone into commit order calculator
        $this->em->persist($phone);
        $this->em->flush();

        $address = new CmsAddress();
        $address->city = "bonn";
        $address->country = "Germany";
        $address->street = "somestreet!";
        $address->zip = 12345;

        $this->em->persist($address);

        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "benjamin";
        $user->status = "active";
        $user->setAddress($address);

        // puts user and address into commit order calculator, but does not calculate user dependencies new
        $this->em->persist($user);
        $this->em->flush();

        $this->em->remove($user->getAddress());
        $this->em->remove($user);
        $this->em->flush();

        self::assertEmpty($this->em->getRepository(CmsAddress::class)->findAll());
        self::assertEmpty($this->em->getRepository(CmsUser::class)->findAll());
    }
}

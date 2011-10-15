<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../../TestInit.php';

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
        $this->_em->persist($phone);
        $this->_em->flush();

        $address = new \Doctrine\Tests\Models\CMS\CmsAddress();
        $address->city = "bonn";
        $address->country = "Germany";
        $address->street = "somestreet!";
        $address->zip = 12345;

        $this->_em->persist($address);

        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "benjamin";
        $user->status = "active";
        $user->setAddress($address);

        // puts user and address into commit order calculator, but does not calculate user dependencies new
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->remove($user->getAddress());
        $this->_em->remove($user);
        $this->_em->flush();
    }
}
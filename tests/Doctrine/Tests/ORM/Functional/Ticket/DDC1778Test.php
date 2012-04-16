<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

/**
 * @group DDC-1778
 */
class DDC1778Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $user;
    private $phone;

    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->user = new CmsUser();
        $this->user->username = "beberlei";
        $this->user->name = "Benjamin";
        $this->user->status = "active";

        $this->phone = new CmsPhoneNumber();
        $this->phone->phonenumber = '0123456789';
        $this->user->addPhoneNumber($this->phone);

        $this->_em->persist($this->user);
        $this->_em->persist($this->phone);
        $this->_em->flush();
        $this->_em->clear();

        $this->user = $this->_em->find('Doctrine\\Tests\\Models\\CMS\\CmsUser', $this->user->getId());
        $this->phone = $this->_em->find('Doctrine\\Tests\\Models\\CMS\\CmsPhonenumber', $this->phone->phonenumber);
    }

    public function testClear()
    {
        $clonedNumbers = clone $this->user->getPhonenumbers();
        $clonedNumbers->clear();
        $this->_em->flush();
        $this->_em->clear();

        $this->user = $this->_em->find('Doctrine\\Tests\\Models\\CMS\\CmsUser', $this->user->getId());

        $this->assertCount(1, $this->user->getPhonenumbers());
    }

    public function testRemove()
    {
        $clonedNumbers = clone $this->user->getPhonenumbers();
        $clonedNumbers->remove(0);
        $this->_em->flush();
        $this->_em->clear();

        $this->user = $this->_em->find('Doctrine\\Tests\\Models\\CMS\\CmsUser', $this->user->getId());

        $this->assertCount(1, $this->user->getPhonenumbers());
    }

    public function testRemoveElement()
    {
        $clonedNumbers = clone $this->user->getPhonenumbers();
        $clonedNumbers->removeElement($this->phone);
        $this->_em->flush();
        $this->_em->clear();

        $this->user = $this->_em->find('Doctrine\\Tests\\Models\\CMS\\CmsUser', $this->user->getId());

        $this->assertCount(1, $this->user->getPhonenumbers());
    }
}

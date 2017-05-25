<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group DDC-6170
 */
class DDC6170Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testCascadeFlushEntity()
    {
        $user = new CmsUser();
        $user->name = "name";
        $user->username = "username";
        $user->status = "status";

        $address = new CmsAddress();
        $address->country = 'sk';
        $address->zip = '06901';
        $address->city = 'Snina';

        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();

        $address->zip = '12345';

        $this->_em->flush($user);
        $this->_em->clear();

        $loadedUser = $this->_em->find(get_class($user), $user->getId());

        $this->assertTrue($loadedUser->getAddress()->getZipCode() == $address->getZipCode());

    }
}

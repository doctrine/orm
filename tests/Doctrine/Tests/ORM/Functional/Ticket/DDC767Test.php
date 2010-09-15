<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;

require_once __DIR__ . '/../../../TestInit.php';

class DDC767Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @group DDC-767
     */
    public function testCollectionChangesInsideTransaction()
    {
        $user = new CmsUser();
        $user->name = "beberlei";
        $user->status = "active";
        $user->username = "beberlei";

        $group1 = new CmsGroup();
        $group1->name = "foo";

        $group2 = new CmsGroup();
        $group2->name = "bar";

        $group3 = new CmsGroup();
        $group3->name = "baz";

        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->_em->persist($user);
        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->persist($group3);

        $this->_em->flush();
        $this->_em->clear();

        /* @var $pUser CmsUser */
        $pUser = $this->_em->find(get_class($user), $user->id);

        $this->assertNotNull($pUser, "User not retrieved from database.");

        $groups = array(2, 3);
        
        try {
            $this->_em->beginTransaction();

            $pUser->groups->clear();

            $this->_em->flush();

            // Add new
            foreach ($groups as $groupId) {
                $pUser->addGroup($this->_em->find(get_class($group1), $groupId));
            }

            $this->_em->flush();
            $this->_em->commit();
        } catch(\Exception $e) {
            $this->_em->rollback();
        }
    }
}

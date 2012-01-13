<?php
namespace Doctrine\Tests\ORM\Functional;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\ORM\Events;
require_once __DIR__ . '/../../TestInit.php';

/**
 * ManyToManyEventTest
 *
 * @author Francisco Facioni <fran6co@gmail.com>
 */
class ManyToManyEventTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var PostUpdateListener
     */
    private $listener;

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
        $this->listener = new PostUpdateListener();
        $evm = $this->_em->getEventManager();
        $evm->addEventListener(Events::postUpdate, $this->listener);
    }

    public function testListenerShouldBeNotifiedOnlyWhenUpdating()
    {
        $user = $this->createNewValidUser();
        $this->_em->persist($user);
        $this->_em->flush();
        $this->assertFalse($this->listener->wasNotified);

        $group = new CmsGroup();
        $group->name = "admins";
        $user->addGroup($group);
        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue($this->listener->wasNotified);
    }

    /**
     * @return CmsUser
     */
    private function createNewValidUser()
    {
        $user = new CmsUser();
        $user->username = 'fran6co';
        $user->name = 'Francisco Facioni';
        $group = new CmsGroup();
        $group->name = "users";
        $user->addGroup($group);
        return $user;
    }
}

class PostUpdateListener
{
    /**
     * @var bool
     */
    public $wasNotified = false;

    /**
     * @param $args
     */
    public function postUpdate($args)
    {
        $this->wasNotified = true;
    }
}



<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC2775\AdminRole;
use Doctrine\Tests\Models\DDC2775\Authorization;
use Doctrine\Tests\Models\DDC2775\User;

/**
 * Functional tests for cascade remove with class table inheritance.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DDC2775Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('ddc2775');
        parent::setUp();
    }

    /**
     * @group DDC-2775
     */
    public function testIssueCascadeRemove()
    {
        $user = new User();

        $role = new AdminRole();
        $user->addRole($role);

        $authorization = new Authorization();
        $user->addAuthorization($authorization);
        $role->addAuthorization($authorization);

        $this->_em->persist($user);
        $this->_em->flush();

        // Need to clear so that associations are lazy-loaded
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\Models\DDC2775\User', $user->getId());

        $this->_em->remove($user);
        $this->_em->flush();

        // With the bug, the second flush throws an error because the cascade remove didn't work correctly
        $this->_em->flush();
    }
}

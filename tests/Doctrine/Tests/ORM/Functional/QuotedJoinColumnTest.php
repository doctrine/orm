<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Quote\Phone;
use Doctrine\Tests\Models\Quote\User;

/**
 * Tests that association with quoted JoinColumn is loaded
 */
class QuotedJoinColumnTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        $this->useModelSet('quote');
        parent::setUp();
    }

    public function testLoadAssociationWithQuotedJoinColumn()
    {
        $user = new User();
        $user->name = 'John Doe';
        $phone = new Phone();
        $phone->number = '123456789';
        $phone->user = $user;
        $user->phones->add($phone);

        $this->_em->persist($user);
        $this->_em->persist($phone);
        $this->_em->flush();
        $this->_em->clear();
        $phoneNumber = $phone->number;
        unset($user, $phone);

        $persister = $this->_em->getUnitOfWork()->getEntityPersister('Doctrine\Tests\Models\Quote\Phone');
        $entity = $persister->load(array ('number' => $phoneNumber));

        $this->assertNotNull($entity->user);
    }

}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User;

class UnknownTicketTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testCompleteOriginalData()
    {
        $this->useModelSet('tweet');
        parent::setUp();

        $tweet1 = new Tweet();
        $tweet1->content = 'foobar';

        $user = new User();
        $user->name = 'Marco';
        $user->addTweet($tweet1);

        $this->_em->persist($user);
        $this->_em->flush();

        // This should contain all fields now
        $originalData1 = $this->_em->getUnitOfWork()->getOriginalEntityData($user);

        // Change only one of the non-associating fields
        $user->name = 'Polo';

        $this->_em->flush();

        // This should still contain all fields
        $originalData2 = $this->_em->getUnitOfWork()->getOriginalEntityData($user);

        $this->assertSame(array_keys($originalData1), array_keys($originalData2));
    }
}

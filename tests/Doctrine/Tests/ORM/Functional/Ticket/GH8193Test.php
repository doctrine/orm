<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\GH8193\User;
use Doctrine\Tests\Models\GH8193\Event;

class GH8193Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private const USER_EMAIL = 'nick@test.com';

    protected function setUp() : void
    {
        $this->useModelSet('GH8193');
        parent::setUp();

        $user = new User();
        $user->setEmail(self::USER_EMAIL);

        $event = new Event();
        $event
            ->setAmount(10)
            ->setUser($user);

        $this->_em->persist($user);
        $this->_em->persist($event);
        $this->_em->flush();

        $this->_em->clear();
    }

    public function testThatTheEventShouldntBeUpdated()
    {
        /** @var User $user */
        $user = $this->_em->getRepository(User::class)->findOneBy([
            'email' => self::USER_EMAIL
        ]);
        /** @var Event $event */
        $event = $user->getEvents()->first();

        $this->assertEquals(10, $event->getAmount());

        // we only want to change the email in the database
        $user->setEmail('updated@test.com');

        // but for some reason the event is updated too (maybe its due to https://github.com/doctrine/orm/issues/5594)
        $event->setAmount(20);

        // only persist the User as the event shouldn't be updated as cascade isnt enabled between the entities
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        // finally fetch the event again because the amount should still be 10
        $event = $this->_em->getRepository(Event::class)->findOneBy([]);

        $this->assertEquals(10, $event->getAmount());
    }
}

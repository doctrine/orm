<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\Legacy\LegacyUser;
use Doctrine\Tests\Models\Legacy\LegacyUserReference;

/**
 * @group DDC-2519
 */
class DDC2519Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $userId;

    public function setUp()
    {
        $this->useModelSet('legacy');
        parent::setUp();

        $this->loadFixture();
    }

    /**
     * @group DDC-2519
     */
    public function testIssue()
    {
        $dql    = 'SELECT PARTIAL l.{source, target} FROM Doctrine\Tests\Models\Legacy\LegacyUserReference l';
        $result = $this->em->createQuery($dql)->getResult();

        self::assertCount(2, $result);
        self::assertInstanceOf(LegacyUserReference::class, $result[0]);
        self::assertInstanceOf(LegacyUserReference::class, $result[1]);

        self::assertInstanceOf(LegacyUser::class, $result[0]->source());
        self::assertInstanceOf(LegacyUser::class, $result[0]->target());
        self::assertInstanceOf(LegacyUser::class, $result[1]->source());
        self::assertInstanceOf(LegacyUser::class, $result[1]->target());

        self::assertInstanceOf(Proxy::class, $result[0]->source());
        self::assertInstanceOf(Proxy::class, $result[0]->target());
        self::assertInstanceOf(Proxy::class, $result[1]->source());
        self::assertInstanceOf(Proxy::class, $result[1]->target());

        self::assertFalse($result[0]->target()->__isInitialized());
        self::assertFalse($result[0]->source()->__isInitialized());
        self::assertFalse($result[1]->target()->__isInitialized());
        self::assertFalse($result[1]->source()->__isInitialized());

        self::assertNotNull($result[0]->source()->getId());
        self::assertNotNull($result[0]->target()->getId());
        self::assertNotNull($result[1]->source()->getId());
        self::assertNotNull($result[1]->target()->getId());
    }

    public function loadFixture()
    {
        $user1              = new LegacyUser();
        $user1->username   = 'FabioBatSilva';
        $user1->name       = 'Fabio B. Silva';
        $user1->status     = 'active';

        $user2              = new LegacyUser();
        $user2->username   = 'doctrinebot';
        $user2->name       = 'Doctrine Bot';
        $user2->status     = 'active';

        $user3              = new LegacyUser();
        $user3->username   = 'test';
        $user3->name       = 'Tester';
        $user3->status     = 'active';

        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->persist($user3);

        $this->em->flush();

        $this->em->persist(new LegacyUserReference($user1, $user2, 'foo'));
        $this->em->persist(new LegacyUserReference($user1, $user3, 'bar'));

        $this->em->flush();
        $this->em->clear();
    }
}

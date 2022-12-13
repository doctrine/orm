<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Persistence\Proxy;
use Doctrine\Tests\Models\Legacy\LegacyUser;
use Doctrine\Tests\Models\Legacy\LegacyUserReference;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-2519 */
class DDC2519Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('legacy');

        parent::setUp();

        $this->loadFixture();
    }

    /** @group DDC-2519 */
    public function testIssue(): void
    {
        $dql    = 'SELECT PARTIAL l.{_source, _target} FROM Doctrine\Tests\Models\Legacy\LegacyUserReference l';
        $result = $this->_em->createQuery($dql)->getResult();

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

    public function loadFixture(): void
    {
        $user1           = new LegacyUser();
        $user1->username = 'FabioBatSilva';
        $user1->name     = 'Fabio B. Silva';

        $user2           = new LegacyUser();
        $user2->username = 'doctrinebot';
        $user2->name     = 'Doctrine Bot';

        $user3           = new LegacyUser();
        $user3->username = 'test';
        $user3->name     = 'Tester';

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);

        $this->_em->flush();

        $this->_em->persist(new LegacyUserReference($user1, $user2, 'foo'));
        $this->_em->persist(new LegacyUserReference($user1, $user3, 'bar'));

        $this->_em->flush();
        $this->_em->clear();
    }
}

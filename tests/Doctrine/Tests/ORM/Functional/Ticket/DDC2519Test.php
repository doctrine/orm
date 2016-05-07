<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

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
        $dql    = 'SELECT PARTIAL l.{_source, _target} FROM Doctrine\Tests\Models\Legacy\LegacyUserReference l';
        $result = $this->_em->createQuery($dql)->getResult();

        self::assertCount(2, $result);
        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUserReference', $result[0]);
        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUserReference', $result[1]);

        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUser', $result[0]->source());
        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUser', $result[0]->target());
        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUser', $result[1]->source());
        self::assertInstanceOf('Doctrine\Tests\Models\Legacy\LegacyUser', $result[1]->target());

        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $result[0]->source());
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $result[0]->target());
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $result[1]->source());
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $result[1]->target());

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
        $user1->_username   = 'FabioBatSilva';
        $user1->_name       = 'Fabio B. Silva';
        $user1->_status     = 'active';

        $user2              = new LegacyUser();
        $user2->_username   = 'doctrinebot';
        $user2->_name       = 'Doctrine Bot';
        $user2->_status     = 'active';

        $user3              = new LegacyUser();
        $user3->_username   = 'test';
        $user3->_name       = 'Tester';
        $user3->_status     = 'active';

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

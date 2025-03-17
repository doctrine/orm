<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\OrmFunctionalTestCase;

use const PHP_VERSION_ID;

class DDC11871Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('The ' . self::class . ' requires PHP 8.4.');
        }

        $this->setUpEntitySchema([
            DDC11871User::class,
            DDC11871Order::class,
        ]);
    }

    public function testEntityHydratation(): void
    {
        $user = new DDC11871User('Some company');
        $this->_em->persist($user);
        $order = new DDC11871Order($user);
        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->clear();

        $hydrated = $this->_em->getRepository(DDC11871Order::class)->findAll();
        self::assertCount(1, $hydrated);
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'DDC11871_User')]
class DDC11871User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    private(set) string $company; // phpcs:ignore

    public function __construct(string $company)
    {
        $this->company = $company;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'DDC11871_Order')]
class DDC11871Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column]
    private(set) string $company; // phpcs:ignore

    public function __construct(
        #[ORM\ManyToOne(targetEntity: DDC11871User::class, fetch: 'LAZY')]
        private(set) DDC11871User $user, // phpcs:ignore
    ) {
        $this->company = $user->company;
    }
}

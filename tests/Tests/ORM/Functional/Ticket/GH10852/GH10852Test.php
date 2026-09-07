<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10852;

use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 8.1
 */
class GH10852Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10852Card::class,
            GH10852Hand::class
        );

        $card = new GH10852Card(GH10852Suit::Clubs, 'clubs');

        $this->_em->persist($card);
        $this->_em->persist(new GH10852Hand(1, $card));
        $this->_em->flush();
        $this->_em->clear();
    }

    protected function tearDown(): void
    {
        $conn = $this->_em->getConnection();

        $conn->executeStatement('DELETE FROM GH10852Hand');
        $conn->executeStatement('DELETE FROM GH10852Card');

        parent::tearDown();
    }

    public function testRehydratingAManagedEntityKeepsItsEnumIdentifierFlattened(): void
    {
        $card = $this->_em->find(GH10852Card::class, GH10852Suit::Clubs);

        self::assertSame(['suit' => 'C'], $this->_em->getUnitOfWork()->getEntityIdentifier($card));

        $this->rehydrate($card);

        self::assertSame(['suit' => 'C'], $this->_em->getUnitOfWork()->getEntityIdentifier($card));
    }

    public function testRehydratedEntityWithEnumIdentifierCanBeBoundAsQueryParameter(): void
    {
        $card = $this->_em->find(GH10852Card::class, GH10852Suit::Clubs);

        $this->rehydrate($card);

        self::assertCount(1, $this->handsHolding($card));
    }

    public function testInitializingAProxyKeepsItsEnumIdentifierFlattened(): void
    {
        $card = $this->_em->find(GH10852Hand::class, 1)->card;

        self::assertSame(['suit' => 'C'], $this->_em->getUnitOfWork()->getEntityIdentifier($card));

        // Reading anything but the identifier initializes the proxy, which re-hydrates
        // the entity through the very same refresh hints.
        self::assertSame('clubs', $card->label);

        self::assertSame(['suit' => 'C'], $this->_em->getUnitOfWork()->getEntityIdentifier($card));
        self::assertCount(1, $this->handsHolding($card));
    }

    /**
     * Reloads an instance the UnitOfWork already manages, the way the ORM does it
     * internally when it initializes a lazy proxy.
     */
    private function rehydrate(GH10852Card $card): void
    {
        $this->_em->createQuery('SELECT c FROM ' . GH10852Card::class . ' c')
            ->setHint(Query::HINT_REFRESH, true)
            ->setHint(Query::HINT_REFRESH_ENTITY, $card)
            ->getSingleResult();
    }

    /** @return GH10852Hand[] */
    private function handsHolding(GH10852Card $card): array
    {
        return $this->_em->createQuery('SELECT h FROM ' . GH10852Hand::class . ' h WHERE h.card = :card')
            ->setParameter('card', $card)
            ->getResult();
    }
}

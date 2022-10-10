<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Enums\Card;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterResultMappingTest extends OrmTestCase
{
    /** @var BasicEntityPersister */
    protected $persister;

    /** @var EntityManagerMock */
    protected $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
        $this->persister     = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Card::class));
    }

    /**
     * @requires PHP 8.1
     */
    public function testEnumTypeIsAddedToResultMapping(): void
    {
        $statement = $this->persister->getSelectSQL([]);
        self::assertEquals('SELECT t0.id AS id_1, t0.suit AS suit_2 FROM Card t0', $statement);
        self::assertEquals(['suit_2' => Suit::class], $this->persister->getResultSetMapping()->enumMappings);
    }
}

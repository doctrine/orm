<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional Query tests.
 */
#[Group('DDC-692')]
class ReadOnlyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(ReadOnlyEntity::class);
    }

    public function testReadOnlyEntityNeverChangeTracked(): void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->_em->persist($readOnly);
        $this->_em->flush();

        $readOnly->name         = 'Test2';
        $readOnly->numericValue = 4321;

        $this->_em->flush();
        $this->_em->clear();

        $dbReadOnly = $this->getWithoutReadOnlyHint($readOnly->id);
        self::assertEquals('Test1', $dbReadOnly->name);
        self::assertEquals(1234, $dbReadOnly->numericValue);
    }

    #[Group('DDC-1659')]
    public function testClearReadOnly(): void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->_em->persist($readOnly);
        $this->_em->flush();
        $this->_em->getUnitOfWork()->markReadOnly($readOnly);

        $this->_em->clear();

        self::assertFalse($this->_em->getUnitOfWork()->isReadOnly($readOnly));
    }

    #[Group('DDC-1659')]
    public function testClearEntitiesReadOnly(): void
    {
        $readOnly = new ReadOnlyEntity('Test1', 1234);
        $this->_em->persist($readOnly);
        $this->_em->flush();
        $this->_em->getUnitOfWork()->markReadOnly($readOnly);

        $this->_em->clear();

        self::assertFalse($this->_em->getUnitOfWork()->isReadOnly($readOnly));
    }

    public function testReadOnlyQueryHint(): void
    {
        $user = new ReadOnlyEntity('beberlei', 1234);

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->getWithReadOnlyHint($user->id, readOnly: true);

        self::assertTrue($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    public function testNotReadOnlyQueryHint(): void
    {
        $user = new ReadOnlyEntity('beberlei', 1234);

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->getWithReadOnlyHint($user->id, readOnly: false);

        self::assertFalse($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    public function testNotReadOnlyIfObjectWasProxyBefore(): void
    {
        $user = new ReadOnlyEntity('beberlei', 1234);

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getReference(ReadOnlyEntity::class, $user->id);

        $user = $this->getWithReadOnlyHint($user->id, readOnly: true);

        self::assertFalse($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    public function testNotReadOnlyIfObjectWasKnownBefore(): void
    {
        $user = new ReadOnlyEntity('beberlei', 1234);

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $userIntoIdentityMap = $this->getWithoutReadOnlyHint($user->id);

        $user = $this->getWithReadOnlyHint($user->id, readOnly: true);

        self::assertFalse($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    public function testNotWriteableIfObjectWasKnownAsReadOnlyBefore(): void
    {
        $user = new ReadOnlyEntity('Théo', 1234);

        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $userIntoIdentityMap = $this->getWithReadOnlyHint($user->id, readOnly: true);
        $user                = $this->getWithoutReadOnlyHint($user->id);

        self::assertTrue($this->_em->getUnitOfWork()->isReadOnly($user));
    }

    private function getWithoutReadOnlyHint(int $id): ReadOnlyEntity
    {
        return $this->_em->find(ReadOnlyEntity::class, $id);
    }

    private function getWithReadOnlyHint(int $id, bool $readOnly): ReadOnlyEntity
    {
        $dql = 'SELECT u FROM ' . ReadOnlyEntity::class . ' u WHERE u.id = ?1';

        $query = $this->_em->createQuery($dql);
        $query->setParameter(1, $id);
        $query->setHint(Query::HINT_READ_ONLY, $readOnly);

        return $query->getSingleResult();
    }
}

#[Entity(readOnly: true)]
class ReadOnlyEntity
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        public string $name,
        #[Column(type: 'integer')]
        public int $numericValue,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 8.1
 * @group GH-10049
 */
class GH10049Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH10049Book::class),
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(GH10049Book::class),
            ]
        );

        parent::tearDown();
    }

    public function testIssue(): void
    {
        $id = 'b';

        $book = new GH10049Book(new GH10049BookId($id));

        $this->_em->persist($book);
        $this->_em->flush();

        $this->_em->clear();

        $repository = $this->_em->getRepository(GH10049Book::class);

        $persistedBook = $repository->find($id);

        // assert Book was persisted and could be hydrated
        self::assertInstanceOf(GH10049Book::class, $persistedBook);
        self::assertEquals($id, $persistedBook->id->value);
    }
}

abstract class GH10049AggregatedRootId
{
    /**
     * @Id
     * @Column(name="id", type="string")
     */
    public readonly string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? 'a';
    }

    public function __toString()
    {
        return $this->value;
    }
}

/**
 * @Embeddable
 */
final class GH10049BookId extends GH10049AggregatedRootId
{
}

/**
 * @Entity
 */
class GH10049Book
{
    /** @Embedded(columnPrefix=false) */
    public readonly GH10049BookId $id;

    public function __construct(?GH10049BookId $id = null)
    {
        $this->id = $id ?? new GH10049BookId();
    }
}

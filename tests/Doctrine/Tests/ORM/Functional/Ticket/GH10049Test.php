<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 8.1
 * @group GH-10049
 */
class GH10049Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/GH10049Mocks.php';

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
        self::assertInstanceOf(GH10049BookId::class, $persistedBook->id);
        self::assertEquals($id, $persistedBook->id->value);
    }
}

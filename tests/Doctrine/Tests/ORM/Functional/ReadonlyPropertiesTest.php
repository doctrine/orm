<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\ReadonlyProperties\Author;
use Doctrine\Tests\Models\ReadonlyProperties\Book;
use Doctrine\Tests\Models\ReadonlyProperties\SimpleBook;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\TestUtil;

use function dirname;
use function strtoupper;

/**
 * @requires PHP 8.1
 */
class ReadonlyPropertiesTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! isset(static::$sharedConn)) {
            static::$sharedConn = TestUtil::getConnection();
        }

        $this->_em         = $this->getEntityManager(null, new AttributeDriver(
            [dirname(__DIR__, 2) . '/Models/ReadonlyProperties']
        ));
        $this->_schemaTool = new SchemaTool($this->_em);

        parent::setUp();

        $this->setUpEntitySchema([Author::class, Book::class, SimpleBook::class]);
    }

    public function testSimpleEntity(): void
    {
        $connection = $this->_em->getConnection();

        $connection->insert('author', ['name' => 'Jane Austen']);
        $authorId = $connection->lastInsertId($this->getLastInsertName('author'));

        $author = $this->_em->find(Author::class, $authorId);

        self::assertSame('Jane Austen', $author->getName());
        self::assertEquals($authorId, $author->getId());
    }

    public function testEntityWithLazyManyToOne(): void
    {
        $connection = $this->_em->getConnection();

        $connection->insert('author', ['name' => 'Jane Austen']);
        $authorId = $connection->lastInsertId($this->getLastInsertName('author'));

        $connection->insert('simple_book', ['title' => 'Pride and Prejudice', 'author_id' => $authorId]);
        $bookId = $connection->lastInsertId($this->getLastInsertName('simple_book'));

        $book = $this->_em->find(SimpleBook::class, $bookId);

        self::assertSame('Pride and Prejudice', $book->getTitle());
        self::assertEquals($bookId, $book->getId());
        self::assertSame('Jane Austen', $book->getAuthor()->getName());
    }

    public function testEntityWithEagerManyToOne(): void
    {
        $connection = $this->_em->getConnection();

        $this->disableAutoCommit();
        $connection->insert('author', ['name' => 'Jane Austen']);
        $authorId = $connection->lastInsertId($this->getLastInsertName('author'));

        $connection->insert('simple_book', ['title' => 'Pride and Prejudice', 'author_id' => $authorId]);
        $bookId = $connection->lastInsertId($this->getLastInsertName('simple_book'));

        [$book] = $this->_em->createQueryBuilder()
            ->from(SimpleBook::class, 'b')
            ->join('b.author', 'a')
            ->select(['b', 'a'])
            ->where('b.id = :id')
            ->setParameter('id', $bookId)
            ->getQuery()
            ->execute();

        self::assertInstanceOf(SimpleBook::class, $book);
        self::assertSame('Pride and Prejudice', $book->getTitle());
        self::assertEquals($bookId, $book->getId());
        self::assertSame('Jane Austen', $book->getAuthor()->getName());
    }

    public function testEntityWithManyToMany(): void
    {
        $connection = $this->_em->getConnection();

        $this->disableAutoCommit();
        $connection->insert('author', ['name' => 'Jane Austen']);
        $authorId = $connection->lastInsertId($this->getLastInsertName('author'));

        $connection->insert('book', ['title' => 'Pride and Prejudice']);
        $bookId = $connection->lastInsertId($this->getLastInsertName('book'));

        $connection->insert('book_author', ['book_id' => $bookId, 'author_id' => $authorId]);

        $book = $this->_em->find(Book::class, $bookId);

        self::assertSame('Pride and Prejudice', $book->getTitle());
        self::assertEquals($bookId, $book->getId());
        self::assertSame('Jane Austen', $book->getAuthors()[0]->getName());
    }

    /**
     * Getting LastInsertName is needed, since in DBAL there is no other option to get the last added id
     */
    private function getLastInsertName($name): ?string
    {
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof OraclePlatform) {
            return strtoupper($name) . '_SEQ';
        }

        return null;
    }
}

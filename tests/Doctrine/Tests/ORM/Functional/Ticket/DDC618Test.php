<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-618
 */
class DDC618Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC618Author::class),
                $this->em->getClassMetadata(DDC618Book::class)
                ]
            );

            // Create author 10/Joe with two books 22/JoeA and 20/JoeB
            $author = new DDC618Author();
            $author->id = 10;
            $author->name = 'Joe';
            $this->em->persist($author);

            // Create author 11/Alice with two books 21/AliceA and 23/AliceB
            $author = new DDC618Author();
            $author->id = 11;
            $author->name = 'Alice';
            $author->addBook('In Wonderland');
            $author->addBook('Reloaded');
            $author->addBook('Test');

            $this->em->persist($author);

            $this->em->flush();
            $this->em->clear();
        } catch(\Exception $e) {

        }
    }

    public function testIndexByHydrateObject()
    {
        $dql = 'SELECT A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        $joe    = $this->em->find(DDC618Author::class, 10);
        $alice  = $this->em->find(DDC618Author::class, 11);

        self::assertArrayHasKey('Joe', $result, "INDEX BY A.name should return an index by the name of 'Joe'.");
        self::assertArrayHasKey('Alice', $result, "INDEX BY A.name should return an index by the name of 'Alice'.");
    }

    public function testIndexByHydrateArray()
    {
        $dql = 'SELECT A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $joe    = $this->em->find(DDC618Author::class, 10);
        $alice  = $this->em->find(DDC618Author::class, 11);

        self::assertArrayHasKey('Joe', $result, "INDEX BY A.name should return an index by the name of 'Joe'.");
        self::assertArrayHasKey('Alice', $result, "INDEX BY A.name should return an index by the name of 'Alice'.");
    }

    /**
     * @group DDC-1018
     */
    public function testIndexByJoin()
    {
        $dql = 'SELECT A, B FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A '.
               'INNER JOIN A.books B INDEX BY B.title ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        self::assertEquals(3, count($result[0]->books)); // Alice, Joe doesn't appear because he has no books.
        self::assertEquals('Alice', $result[0]->name);
        self::assertTrue( isset($result[0]->books["In Wonderland"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[0]->books["Reloaded"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[0]->books["Test"] ), "Indexing by title should have books by title.");

        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        self::assertEquals(3, count($result[0]['books'])); // Alice, Joe doesn't appear because he has no books.
        self::assertEquals('Alice', $result[0]['name']);
        self::assertTrue( isset($result[0]['books']["In Wonderland"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[0]['books']["Reloaded"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[0]['books']["Test"] ), "Indexing by title should have books by title.");
    }

    /**
     * @group DDC-1018
     */
    public function testIndexByToOneJoinSilentlyIgnored()
    {
        $dql = 'SELECT B, A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Book B '.
               'INNER JOIN B.author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        self::assertInstanceOf(DDC618Book::class, $result[0]);
        self::assertInstanceOf(DDC618Author::class, $result[0]->author);

        $dql = 'SELECT B, A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Book B '.
               'INNER JOIN B.author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        self::assertEquals("Alice", $result[0]['author']['name']);
    }

    /**
     * @group DDC-1018
     */
    public function testCombineIndexBy()
    {
        $dql = 'SELECT A, B FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A INDEX BY A.id '.
               'INNER JOIN A.books B INDEX BY B.title ORDER BY A.name ASC';
        $result = $this->em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        self::assertArrayHasKey(11, $result); // Alice

        self::assertEquals(3, count($result[11]->books)); // Alice, Joe doesn't appear because he has no books.
        self::assertEquals('Alice', $result[11]->name);
        self::assertTrue( isset($result[11]->books["In Wonderland"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[11]->books["Reloaded"] ), "Indexing by title should have books by title.");
        self::assertTrue( isset($result[11]->books["Test"] ), "Indexing by title should have books by title.");
    }
}

/**
 * @Entity
 */
class DDC618Author
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    /**
     * @OneToMany(targetEntity="DDC618Book", mappedBy="author", cascade={"persist"})
     */
    public $books;

    public function __construct()
    {
        $this->books = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function addBook($title)
    {
        $book = new DDC618Book($title, $this);
        $this->books[] = $book;
    }
}

/**
 * @Entity
 */
class DDC618Book
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /** @column(type="string") */
    public $title;

    /** @ManyToOne(targetEntity="DDC618Author", inversedBy="books") */
    public $author;

    function __construct($title, $author)
    {
        $this->title = $title;
        $this->author = $author;
    }
}

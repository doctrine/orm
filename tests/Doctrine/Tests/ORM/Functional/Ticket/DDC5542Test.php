<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Tools\ChangeDetector\DateTimeObjectByDate;
use Doctrine\Tests\OrmFunctionalTestCase;

use function print_r;
use function sprintf;

use const PHP_EOL;

class DDC5542Test extends OrmFunctionalTestCase
{
    private DDC5542DBALSQLLogger $sqlLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC5542Book::class);

        $this->sqlLogger = new DDC5542DBALSQLLogger();
        $cfg             = $this->_em->getConnection()->getConfiguration();
        $cfg->setSQLLogger($this->sqlLogger);
    }

    public function testDateIsSavedByValue(): void
    {
        //entity création
        $newBook              = new DDC5542Book();
        $newBook->title       = 'Foobar';
        $newBook->publishedAt = new DateTime('2020-01-01');

        $this->_em->persist($newBook);
        $this->_em->flush();
        $bookId = $newBook->id;
        $this->_em->clear();

        //retrieve the entity and change values
        $dbBook = $this->_em->find(DDC5542Book::class, $bookId);
        $this->assertInstanceOf(DateTime::class, $dbBook->publishedAt);
        $this->assertEquals('2020-01-01', $dbBook->publishedAt->format('Y-m-d'));

        $dbBook->title = 'Barfoo';
        // the date is changed by value, not by reference
        $dbBook->publishedAt->modify('+ 1 year');
        $this->assertEquals('2021-01-01', $dbBook->publishedAt->format('Y-m-d'));

        $this->_em->flush();
        $this->_em->clear();

        // chack that the date modification has been recorded
        $dbBook2 = $this->_em->find(DDC5542Book::class, $bookId);

        $this->assertEquals('Barfoo', $dbBook2->title);
        $this->assertEquals('2021-01-01', $dbBook2->publishedAt->format('Y-m-d'));
    }

    public function testDateCanBeUpdatedsTwiceOnALoadedEntity(): void
    {
        //entity création
        $newBook              = new DDC5542Book();
        $newBook->title       = 'Foobar';
        $newBook->publishedAt = new DateTime('2020-01-01');

        $this->_em->persist($newBook);
        $this->_em->flush();
        $bookId = $newBook->id;
        $this->_em->clear();

        //retrieve the entity
        $dbBook = $this->_em->find(DDC5542Book::class, $bookId);
        $this->assertInstanceOf(DateTime::class, $dbBook->publishedAt);
        $this->assertEquals('2020-01-01', $dbBook->publishedAt->format('Y-m-d'));

        // the date is changed by value, not by reference
        $dbBook->publishedAt->modify('+ 1 year');
        $this->assertEquals('2021-01-01', $dbBook->publishedAt->format('Y-m-d'));

        $this->_em->flush();
        $this->assertEquals('2021-01-01', $this->getValueInDatabase($bookId));

        // the date is changed by reference
        $dbBook->publishedAt = new DateTime('2022-01-01');

        $this->_em->flush();
        $this->assertEquals('2022-01-01', $this->getValueInDatabase($bookId));
        $this->assertEquals('2022-01-01', $dbBook->publishedAt->format('Y-m-d'));

        // the rechanged by value
        $dbBook->publishedAt->modify('+1year');
        $this->assertEquals('2023-01-01', $dbBook->publishedAt->format('Y-m-d'));
        $this->_em->flush();
        $this->assertEquals('2023-01-01', $this->getValueInDatabase($bookId));
    }

    public function testDateByValueDoesNotTriggerUnexpectedSQLQueries(): void
    {
        //entity création
        $newBook              = new DDC5542Book();
        $newBook->title       = 'Foobar';
        $newBook->publishedAt = new DateTime('2020-01-01');

        $this->_em->persist($newBook);
        $this->_em->flush();
        $this->assertCountQueries(3);// START TRANSACTION + INSERT + COMMIT
        $bookId = $newBook->id;
        $this->_em->clear();

        //retrieve the entity and change values
        $dbBook = $this->_em->find(DDC5542Book::class, $bookId);
        $this->assertCountQueries(4);
        $this->assertInstanceOf(DateTime::class, $dbBook->publishedAt);
        $this->assertEquals('2020-01-01', $dbBook->publishedAt->format('Y-m-d'));

        // the date is changed by reference
        $dbBook->publishedAt = new DateTime('2020-01-01');
        $this->assertEquals('2020-01-01', $dbBook->publishedAt->format('Y-m-d'));

        $this->_em->flush();
        $this->assertCountQueries(4);//as date did not change, no update queries expected

        $this->assertEquals('2020-01-01', $this->getValueInDatabase($bookId));
    }

    private function assertCountQueries(int $expectedCount): void
    {
        $this->assertCount($expectedCount, $this->sqlLogger->queries, 'Unexpected queries count ' . PHP_EOL . print_r($this->sqlLogger->queries, true));
    }

    /**
     * @return mixed
     */
    private function getValueInDatabase($id, $column = 'publishedAt', $tableName = 'ddc_5542_book')
    {
        $sql = sprintf('SELECT %s FROM %s WHERE id=?', $column, $tableName);

        return $this->_em->getConnection()->fetchOne($sql, [$id]);
    }
}

/**
 * @Entity
 * @Table(name="ddc_5542_book")
*/
#[ORM\Entity]
class DDC5542Book
{
    /**
    * @Column(type="integer")
    * @Id
    * @GeneratedValue
    */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    public ?int $id;

    /** @Column(length=50) */
    #[ORM\Column(type: 'string')]
    public ?string $title = null;

     /** @Column(type="date", nullable="true", changeDetector="Doctrine\ORM\Tools\ChangeDetector\DateTimeObjectByDate") */
    #[ORM\Column(type: 'date', nullable:true, changeDetector:DateTimeObjectByDate::class)]
    public ?DatetImeInterface $publishedAt = null;
}


class DDC5542DBALSQLLogger implements SQLLogger
{
    public array $queries = [];

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->queries[] = $sql;
    }

    public function stopQuery(): void
    {
    }
}

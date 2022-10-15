<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Tools\ChangeDetector\DateTimeObjectByDate;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC5542Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC5542Book::class);
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
}

/** @Entity */
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

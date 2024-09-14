<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7006Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH7006Book::class, GH7006PCT::class, GH7006PCTFee::class);
    }

    public function testIssue(): void
    {
        $book               = new GH7006Book();
        $book->exchangeCode = 'first';
        $this->_em->persist($book);

        $book->exchangeCode = 'second'; // change sth.

        $paymentCardTransaction       = new GH7006PCT();
        $paymentCardTransaction->book = $book;
        $paymentCardTransactionFee    = new GH7006PCTFee($paymentCardTransaction);

        $this->_em->persist($paymentCardTransaction);

        $this->_em->flush();

        self::assertIsInt($book->id);
        self::assertIsInt($paymentCardTransaction->id);
        self::assertIsInt($paymentCardTransactionFee->id);
    }
}

/**
 * @ORM\Entity
 */
class GH7006Book
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    public $exchangeCode;

    /**
     * @ORM\OneToOne(targetEntity="GH7006PCT", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="paymentCardTransactionId", referencedColumnName="id")
     *
     * @var GH7006PCT
     */
    public $paymentCardTransaction;
}

/**
 * @ORM\Entity
 */
class GH7006PCT
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH7006Book")
     * @ORM\JoinColumn(name="bookingId", referencedColumnName="id", nullable=false)
     *
     * @var GH7006Book
     */
    public $book;

    /**
     * @ORM\OneToMany(targetEntity="GH7006PCTFee", mappedBy="pct", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     *
     * @var Collection<int, GH7006PCTFee>
     */
    public $fees;

    public function __construct()
    {
        $this->fees = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class GH7006PCTFee
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH7006PCT", inversedBy="fees")
     * @ORM\JoinColumn(name="paymentCardTransactionId", referencedColumnName="id", nullable=false)
     *
     * @var GH7006PCT
     */
    public $pct;

    public function __construct(GH7006PCT $pct)
    {
        $this->pct = $pct;
        $pct->fees->add($this);
    }
}

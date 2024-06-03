<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11487Test extends OrmFunctionalTestCase
{
    public function testItThrowsASyntaxErrorOnUnfinishedQuery(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Syntax Error');
        $this->_em->createQuery('UPDATE Doctrine\Tests\ORM\Functional\Ticket\TaxType t SET t.default =')->execute();
    }
}

/** @Entity */
class TaxType
{
    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    public $default = false;
}

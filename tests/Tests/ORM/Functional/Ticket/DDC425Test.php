<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC425Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC425Entity::class);
    }

    #[Group('DDC-425')]
    public function testIssue(): void
    {
        $num = $this->_em->createQuery('DELETE ' . __NAMESPACE__ . '\DDC425Entity e WHERE e.someDatetimeField > ?1')
                ->setParameter(1, new DateTime(), Types::DATETIME_MUTABLE)
                ->getResult();
        self::assertEquals(0, $num);
    }
}

#[Entity]
class DDC425Entity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DateTime */
    #[Column(type: 'datetime')]
    public $someDatetimeField;
}

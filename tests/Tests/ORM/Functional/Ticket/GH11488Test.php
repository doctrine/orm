<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Tests\ORM\Functional\Ticket\GH11488\Somewhere\BaseEntity;
use Doctrine\Tests\ORM\Functional\Ticket\GH11488\SomewhereElse\Mother;
use Doctrine\Tests\ORM\Functional\Ticket\GH11488\YetSomewhereElse\Daughter;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11488Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // $this->_em = $this->getEntityManager(mappingDriver: new AttributeDriver([], reportFieldsWhereDeclared: true));

        $this->setUpEntitySchema([
            BaseEntity::class,
            Mother::class,
            Daughter::class,
        ]);
    }

    public function testSchema(): void
    {
        $cm = $this->_em->getClassMetadata(Daughter::class);

        self::assertSame([0 => 'id'], $cm->getIdentifier());
        self::assertSame(['id'], $cm->getFieldNames());
    }
}

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11488\Somewhere;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class BaseEntity
{
    #[ORM\Id, ORM\Column]
    protected ?int $id;
}

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11488\SomewhereElse;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\ORM\Functional\Ticket\GH11488\Somewhere\BaseEntity;

#[ORM\MappedSuperclass]
abstract class Mother extends BaseEntity
{
}

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11488\YetSomewhereElse;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\ORM\Functional\Ticket\GH11488\SomewhereElse\Mother;

#[ORM\Entity]
class Daughter extends Mother
{
}

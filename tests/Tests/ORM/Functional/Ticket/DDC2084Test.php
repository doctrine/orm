<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\ORM\Functional\Ticket\DDC2084\MyEntity1;
use Doctrine\Tests\ORM\Functional\Ticket\DDC2084\MyEntity2;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2084')]
class DDC2084Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            __NAMESPACE__ . '\DDC2084\MyEntity1',
            __NAMESPACE__ . '\DDC2084\MyEntity2',
        );
    }

    public function loadFixture(): MyEntity1
    {
        $e2 = new MyEntity2('Foo');
        $e1 = new MyEntity1($e2);

        $this->_em->persist($e2);
        $this->_em->flush();

        $this->_em->persist($e1);
        $this->_em->flush();

        $this->_em->clear();

        return $e1;
    }

    public function testIssue(): void
    {
        $e1 = $this->loadFixture();
        $e2 = $e1->getMyEntity2();
        $e  = $this->_em->find(__NAMESPACE__ . '\DDC2084\MyEntity1', $e2);

        self::assertInstanceOf(__NAMESPACE__ . '\DDC2084\MyEntity1', $e);
        self::assertInstanceOf(__NAMESPACE__ . '\DDC2084\MyEntity2', $e->getMyEntity2());
        self::assertEquals('Foo', $e->getMyEntity2()->getValue());
    }

    public function testInvalidIdentifierBindingEntityException(): void
    {
        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage(
            <<<'EXCEPTION'
Binding entities to query parameters only allowed for entities that have an identifier.
Class "Doctrine\Tests\ORM\Functional\Ticket\DDC2084\MyEntity2" does not have an identifier.
EXCEPTION,
        );
        $this->_em->find(__NAMESPACE__ . '\DDC2084\MyEntity1', new MyEntity2('Foo'));
    }
}

namespace Doctrine\Tests\ORM\Functional\Ticket\DDC2084;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'DDC2084_ENTITY1')]
#[Entity]
class MyEntity1
{
    public function __construct(
        #[Id]
        #[OneToOne(targetEntity: 'MyEntity2')]
        #[JoinColumn(name: 'entity2_id', referencedColumnName: 'id', nullable: false)]
        private MyEntity2 $entity2,
    ) {
    }

    public function setMyEntity2(MyEntity2 $myEntity2): void
    {
        $this->entity2 = $myEntity2;
    }

    public function getMyEntity2(): MyEntity2
    {
        return $this->entity2;
    }
}

#[Table(name: 'DDC2084_ENTITY2')]
#[Entity]
class MyEntity2
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    public function __construct(
        #[Column]
        private string $value,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}

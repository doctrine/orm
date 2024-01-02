<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC837Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC837Super::class,
            DDC837Class1::class,
            DDC837Class2::class,
            DDC837Class3::class,
            DDC837Aggregate::class,
        );
    }

    #[Group('DDC-837')]
    public function testIssue(): void
    {
        $c1              = new DDC837Class1();
        $c1->title       = 'Foo';
        $c1->description = 'Foo';
        $aggregate1      = new DDC837Aggregate('test1');
        $c1->aggregate   = $aggregate1;

        $c2              = new DDC837Class2();
        $c2->title       = 'Bar';
        $c2->description = 'Bar';
        $c2->text        = 'Bar';
        $aggregate2      = new DDC837Aggregate('test2');
        $c2->aggregate   = $aggregate2;

        $c3          = new DDC837Class3();
        $c3->apples  = 'Baz';
        $c3->bananas = 'Baz';

        $this->_em->persist($c1);
        $this->_em->persist($aggregate1);
        $this->_em->persist($c2);
        $this->_em->persist($aggregate2);
        $this->_em->persist($c3);
        $this->_em->flush();
        $this->_em->clear();

        // Test Class1
        $e1 = $this->_em->find(DDC837Super::class, $c1->id);

        self::assertInstanceOf(DDC837Class1::class, $e1);
        self::assertEquals('Foo', $e1->title);
        self::assertEquals('Foo', $e1->description);
        self::assertInstanceOf(DDC837Aggregate::class, $e1->aggregate);
        self::assertEquals('test1', $e1->aggregate->getSysname());

        // Test Class 2
        $e2 = $this->_em->find(DDC837Super::class, $c2->id);

        self::assertInstanceOf(DDC837Class2::class, $e2);
        self::assertEquals('Bar', $e2->title);
        self::assertEquals('Bar', $e2->description);
        self::assertEquals('Bar', $e2->text);
        self::assertInstanceOf(DDC837Aggregate::class, $e2->aggregate);
        self::assertEquals('test2', $e2->aggregate->getSysname());

        $all = $this->_em->getRepository(DDC837Super::class)->findAll();

        foreach ($all as $obj) {
            if ($obj instanceof DDC837Class1) {
                self::assertEquals('Foo', $obj->title);
                self::assertEquals('Foo', $obj->description);
            } elseif ($obj instanceof DDC837Class2) {
                self::assertSame($e2, $obj);
                self::assertEquals('Bar', $obj->title);
                self::assertEquals('Bar', $obj->description);
                self::assertEquals('Bar', $obj->text);
            } elseif ($obj instanceof DDC837Class3) {
                self::assertEquals('Baz', $obj->apples);
                self::assertEquals('Baz', $obj->bananas);
            } else {
                self::fail('Instance of DDC837Class1, DDC837Class2 or DDC837Class3 expected.');
            }
        }
    }
}

#[Table(name: 'DDC837Super')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'type', type: 'string')]
#[DiscriminatorMap(['class1' => 'DDC837Class1', 'class2' => 'DDC837Class2', 'class3' => 'DDC837Class3'])]
abstract class DDC837Super
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
}

#[Entity]
class DDC837Class1 extends DDC837Super
{
    /** @var string */
    #[Column(name: 'title', type: 'string', length: 150)]
    public $title;

    /** @var string */
    #[Column(name: 'content', type: 'string', length: 500)]
    public $description;

    /** @var DDC837Aggregate */
    #[OneToOne(targetEntity: 'DDC837Aggregate')]
    public $aggregate;
}

#[Entity]
class DDC837Class2 extends DDC837Super
{
    /** @var string */
    #[Column(name: 'title', type: 'string', length: 150)]
    public $title;

    /** @var string */
    #[Column(name: 'content', type: 'string', length: 500)]
    public $description;

    /** @var string */
    #[Column(name: 'text', type: 'text')]
    public $text;

    /** @var DDC837Aggregate */
    #[OneToOne(targetEntity: 'DDC837Aggregate')]
    public $aggregate;
}

/**
 * An extra class to demonstrate why title and description aren't in Super
 */
#[Entity]
class DDC837Class3 extends DDC837Super
{
    /** @var string */
    #[Column(name: 'title', type: 'string', length: 150)]
    public $apples;

    /** @var string */
    #[Column(name: 'content', type: 'string', length: 500)]
    public $bananas;
}

#[Entity]
class DDC837Aggregate
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue]
    public $id;

    public function __construct(
        #[Column(name: 'sysname', type: 'string', length: 255)]
        protected string $sysname,
    ) {
    }

    public function getSysname(): string
    {
        return $this->sysname;
    }
}

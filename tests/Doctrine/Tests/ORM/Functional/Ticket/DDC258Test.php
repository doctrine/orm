<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC258Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC258Super::class),
            $this->em->getClassMetadata(DDC258Class1::class),
            $this->em->getClassMetadata(DDC258Class2::class),
            $this->em->getClassMetadata(DDC258Class3::class),
            ]
        );
    }

    /**
     * @group DDC-258
     */
    public function testIssue()
    {
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $c1 = new DDC258Class1();
        $c1->title = "Foo";
        $c1->description = "Foo";

        $c2 = new DDC258Class2();
        $c2->title = "Bar";
        $c2->description = "Bar";
        $c2->text = "Bar";

        $c3 = new DDC258Class3();
        $c3->apples = "Baz";
        $c3->bananas = "Baz";

        $this->em->persist($c1);
        $this->em->persist($c2);
        $this->em->persist($c3);
        $this->em->flush();
        $this->em->clear();

        $e2 = $this->em->find(DDC258Super::class, $c2->id);

        self::assertInstanceOf(DDC258Class2::class, $e2);
        self::assertEquals('Bar', $e2->title);
        self::assertEquals('Bar', $e2->description);
        self::assertEquals('Bar', $e2->text);

        $all = $this->em->getRepository(DDC258Super::class)->findAll();

        foreach ($all as $obj) {
            if ($obj instanceof DDC258Class1) {
                self::assertEquals('Foo', $obj->title);
                self::assertEquals('Foo', $obj->description);
            } else if ($obj instanceof DDC258Class2) {
                self::assertTrue($e2 === $obj);
                self::assertEquals('Bar', $obj->title);
                self::assertEquals('Bar', $obj->description);
                self::assertEquals('Bar', $obj->text);
            } else if ($obj instanceof DDC258Class3) {
                self::assertEquals('Baz', $obj->apples);
                self::assertEquals('Baz', $obj->bananas);
            } else {
                $this->fail('Instance of DDC258Class1, DDC258Class2 or DDC258Class3 expected.');
            }
        }
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="DDC258Super")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"class1" = "DDC258Class1", "class2" = "DDC258Class2", "class3"="DDC258Class3"})
 */
abstract class DDC258Super
{
    /**
     * @ORM\Id @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
    */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC258Class1 extends DDC258Super
{
    /**
     * @ORM\Column(name="title", type="string", length=150)
     */
    public $title;

    /**
     * @ORM\Column(name="content", type="string", length=500)
     */
    public $description;
}

/**
 * @ORM\Entity
 */
class DDC258Class2 extends DDC258Super
{
    /**
     * @ORM\Column(name="title", type="string", length=150)
     */
    public $title;

    /**
     * @ORM\Column(name="content", type="string", length=500)
     */
    public $description;

    /**
     * @ORM\Column(name="text", type="text")
     */
    public $text;
}

/**
 * An extra class to demonstrate why title and description aren't in Super
 *
 * @ORM\Entity
 */
class DDC258Class3 extends DDC258Super
{
    /**
     * @ORM\Column(name="title", type="string", length=150)
     */
    public $apples;

    /**
     * @ORM\Column(name="content", type="string", length=500)
     */
    public $bananas;
}

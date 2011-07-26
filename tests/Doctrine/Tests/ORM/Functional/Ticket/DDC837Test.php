<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC837Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC837Super'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC837Class1'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC837Class2'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC837Class3'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC837Aggregate'),
        ));
    }

    /**
     * @group DDC-837
     */
    public function testIssue()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $c1 = new DDC837Class1();
        $c1->title = "Foo";
        $c1->description = "Foo";
        $aggregate1 = new DDC837Aggregate('test1');
        $c1->aggregate = $aggregate1;

        $c2 = new DDC837Class2();
        $c2->title = "Bar";
        $c2->description = "Bar";
        $c2->text = "Bar";
        $aggregate2 = new DDC837Aggregate('test2');
        $c2->aggregate = $aggregate2;

        $c3 = new DDC837Class3();
        $c3->apples = "Baz";
        $c3->bananas = "Baz";

        $this->_em->persist($c1);
        $this->_em->persist($aggregate1);
        $this->_em->persist($c2);
        $this->_em->persist($aggregate2);
        $this->_em->persist($c3);
        $this->_em->flush();
        $this->_em->clear();

        // Test Class1
        $e1 = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC837Super', $c1->id);

        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC837Class1', $e1);
        $this->assertEquals('Foo', $e1->title);
        $this->assertEquals('Foo', $e1->description);
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC837Aggregate', $e1->aggregate);
        $this->assertEquals('test1', $e1->aggregate->getSysname());

        // Test Class 2
        $e2 = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC837Super', $c2->id);

        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC837Class2', $e2);
        $this->assertEquals('Bar', $e2->title);
        $this->assertEquals('Bar', $e2->description);
        $this->assertEquals('Bar', $e2->text);
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC837Aggregate', $e2->aggregate);
        $this->assertEquals('test2', $e2->aggregate->getSysname());

        $all = $this->_em->getRepository(__NAMESPACE__.'\DDC837Super')->findAll();

        foreach ($all as $obj) {
            if ($obj instanceof DDC837Class1) {
                $this->assertEquals('Foo', $obj->title);
                $this->assertEquals('Foo', $obj->description);
            } else if ($obj instanceof DDC837Class2) {
                $this->assertTrue($e2 === $obj);
                $this->assertEquals('Bar', $obj->title);
                $this->assertEquals('Bar', $obj->description);
                $this->assertEquals('Bar', $obj->text);
            } else if ($obj instanceof DDC837Class3) {
                $this->assertEquals('Baz', $obj->apples);
                $this->assertEquals('Baz', $obj->bananas);
            } else {
                $this->fail('Instance of DDC837Class1, DDC837Class2 or DDC837Class3 expected.');
            }
        }
    }
}

/**
 * @Entity
 * @Table(name="DDC837Super")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"class1" = "DDC837Class1", "class2" = "DDC837Class2", "class3"="DDC837Class3"})
 */
abstract class DDC837Super
{
    /**
     * @Id @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
    */
    public $id;
}

/**
 * @Entity
 */
class DDC837Class1 extends DDC837Super
{
    /**
     * @Column(name="title", type="string", length="150")
     */
    public $title;

    /**
     * @Column(name="content", type="string", length="500")
     */
    public $description;

    /**
     * @OneToOne(targetEntity="DDC837Aggregate")
     */
    public $aggregate;
}

/**
 * @Entity
 */
class DDC837Class2 extends DDC837Super
{
    /**
     * @Column(name="title", type="string", length="150")
     */
    public $title;

    /**
     * @Column(name="content", type="string", length="500")
     */
    public $description;

    /**
     * @Column(name="text", type="text")
     */
    public $text;

    /**
     * @OneToOne(targetEntity="DDC837Aggregate")
     */
    public $aggregate;
}

/**
 * An extra class to demonstrate why title and description aren't in Super
 *
 * @Entity
 */
class DDC837Class3 extends DDC837Super
{
    /**
     * @Column(name="title", type="string", length="150")
     */
    public $apples;

    /**
     * @Column(name="content", type="string", length="500")
     */
    public $bananas;
}

/**
 * @Entity
 */
class DDC837Aggregate
{
    /**
     * @Id @Column(name="id", type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(name="sysname", type="string")
     */
    protected $sysname;

    public function __construct($sysname)
    {
        $this->sysname = $sysname;
    }

    public function getSysname()
    {
        return $this->sysname;
    }
}
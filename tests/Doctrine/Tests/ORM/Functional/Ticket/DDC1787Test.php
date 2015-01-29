<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1787
 */
class DDC1787Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1787Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1787Bar'),
        ));
    }

    public function testIssue()
    {
        $bar = new DDC1787Bar;
        $bar2 = new DDC1787Bar;

        $this->_em->persist($bar);
        $this->_em->persist($bar2);
        $this->_em->flush();

        $this->assertSame(1, $bar->getVersion());
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"bar" = "DDC1787Bar", "foo" = "DDC1787Foo"})
 */
class DDC1787Foo
{
    /**
     * @Id @Column(type="integer") @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Version @Column(type="integer")
     */
    private $version;

    public function getVersion()
    {
        return $this->version;
    }
}

/**
 * @Entity
 */
class DDC1787Bar extends DDC1787Foo
{
}

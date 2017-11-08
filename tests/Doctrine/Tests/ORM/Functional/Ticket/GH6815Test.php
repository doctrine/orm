<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-6815
 */
class GH6815Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GH6815Entity::class),
            $this->_em->getClassMetadata(GH6815SubClass1::class),
            $this->_em->getClassMetadata(GH6815SubClass2::class),
        ]);
    }

    /**
     * Verifies that the configured field name of the discriminator column
     * has been mapped to the class metadata object
     */
    public function testIssue() : void
    {
        $classMetadata = $this->_em->getClassMetadata(GH6815Entity::class);

        $this->assertArrayHasKey('fieldName', $classMetadata->discriminatorColumn);
        $this->assertEquals("discriminatorField", $classMetadata->discriminatorColumn['fieldName']);
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"ONE" = "GH6815SubClass1", "TWO" = "GH6815SubClass2"})
 * @DiscriminatorColumn(name = "dtype", fieldName = "discriminatorField")
 */
abstract class GH6815Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     **/
    public $id;
}

/** @Entity */
class GH6815SubClass1 extends GH6815Entity {}

/** @Entity */
class GH6815SubClass2 extends GH6815Entity {}

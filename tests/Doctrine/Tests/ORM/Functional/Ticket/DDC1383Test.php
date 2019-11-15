<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-1383
 */
class DDC1383Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC1383AbstractEntity::class),
                $this->_em->getClassMetadata(DDC1383Entity::class),
                ]
            );
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		$parent = new DDC1383Entity();
		$child = new DDC1383Entity();

		$child->setReference($parent);

		$this->_em->persist($parent);
		$this->_em->persist($child);

		$id = $child->getId();

		$this->_em->flush();
		$this->_em->clear();

		// Try merging the parent entity
		$child = $this->_em->merge($child);
		$parent = $child->getReference();

		// Parent is not instance of the abstract class
		self::assertTrue($parent instanceof DDC1383AbstractEntity,
				"Entity class is " . get_class($parent) . ', "DDC1383AbstractEntity" was expected');

		// Parent is NOT instance of entity
		self::assertTrue($parent instanceof DDC1383Entity,
				"Entity class is " . get_class($parent) . ', "DDC1383Entity" was expected');
        $this->assertHasDeprecationMessages();
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="integer")
 * @DiscriminatorMap({1 = "DDC1383Entity"})
 */
abstract class DDC1383AbstractEntity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id;

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}
}

/**
 * @Entity
 */
class DDC1383Entity extends DDC1383AbstractEntity
{
	/**
	 * @ManyToOne(targetEntity="DDC1383AbstractEntity")
	 */
	protected $reference;

	public function getReference()
	{
		return $this->reference;
	}

	public function setReference(DDC1383AbstractEntity $reference)
	{
		$this->reference = $reference;
	}
}

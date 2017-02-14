<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1383
 */
class DDC1383Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1383AbstractEntity::class),
                $this->em->getClassMetadata(DDC1383Entity::class),
                ]
            );
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		$parent = new DDC1383Entity();
		$child = new DDC1383Entity();

		$child->setReference($parent);

		$this->em->persist($parent);
		$this->em->persist($child);

		$id = $child->getId();

		$this->em->flush();
		$this->em->clear();

		// Try merging the parent entity
		$child = $this->em->merge($child);
		$parent = $child->getReference();

		// Parent is not instance of the abstract class
		self::assertTrue($parent instanceof DDC1383AbstractEntity,
				"Entity class is " . get_class($parent) . ', "DDC1383AbstractEntity" was expected');

		// Parent is NOT instance of entity
		self::assertTrue($parent instanceof DDC1383Entity,
				"Entity class is " . get_class($parent) . ', "DDC1383Entity" was expected');
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="integer")
 * @ORM\DiscriminatorMap({1 = "DDC1383Entity"})
 */
abstract class DDC1383AbstractEntity
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
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
 * @ORM\Entity
 */
class DDC1383Entity extends DDC1383AbstractEntity
{
	/**
	 * @ORM\ManyToOne(targetEntity="DDC1383AbstractEntity")
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

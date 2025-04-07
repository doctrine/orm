<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11861Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11861BaseEntity::class,
            GH11861ChildEntityA::class,
        ]);
    }

    /**
     * @throws ORMException
     */
    public function testEntityMapCollision(): void
    {
        $externalId = 'external-123'; // Predefined/external ID

        // Create and persist entity
        $entity = new GH11861ChildEntityA();
        $entity->setId($externalId);
        $entity->setFieldA('test');
        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        // Fetch via parent class
        $baseEntity = $this->_em->find(GH11861BaseEntity::class, $externalId); // Returns ChildEntityA

        // Fetch via child class
        $childEntity = $this->_em->find(GH11861ChildEntityA::class, $externalId); // New instance created

        // Check if they’re the same
        self::assertSame($baseEntity, $childEntity);

        // Trigger collision
        $this->_em->flush(); // Throws identity collision error
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"a" = "GH11861ChildEntityA"})
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['a' => GH11861ChildEntityA::class])]
class GH11861BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string") // Predefined/external ID
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string')] // Predefined/external ID
    protected $id;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}

// Child entity

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class GH11861ChildEntityA extends GH11861BaseEntity
{
    /**
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: 'string')]
    private $fieldA;

    public function setFieldA($value)
    {
        $this->fieldA = $value;
    }

    public function getFieldA()
    {
        return $this->fieldA;
    }
}

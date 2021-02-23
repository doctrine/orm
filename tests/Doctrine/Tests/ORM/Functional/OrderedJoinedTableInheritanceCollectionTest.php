<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 */
class OrderedJoinedTableInheritanceCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(OJTICPet::class),
                    $this->em->getClassMetadata(OJTICCat::class),
                    $this->em->getClassMetadata(OJTICDog::class),
                ]
            );
        } catch (Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }

        $dog       = new OJTICDog();
        $dog->name = 'Poofy';

        $dog1       = new OJTICDog();
        $dog1->name = 'Zampa';
        $dog2       = new OJTICDog();
        $dog2->name = 'Aari';

        $dog1->mother = $dog;
        $dog2->mother = $dog;

        $dog->children[] = $dog1;
        $dog->children[] = $dog2;

        $this->em->persist($dog);
        $this->em->persist($dog1);
        $this->em->persist($dog2);
        $this->em->flush();
        $this->em->clear();
    }

    public function testOrderdOneToManyCollection() : void
    {
        $poofy = $this->em->createQuery("SELECT p FROM Doctrine\Tests\ORM\Functional\OJTICPet p WHERE p.name = 'Poofy'")->getSingleResult();

        self::assertEquals('Aari', $poofy->children[0]->getName());
        self::assertEquals('Zampa', $poofy->children[1]->getName());

        $this->em->clear();

        $result = $this->em->createQuery(
            "SELECT p, c FROM Doctrine\Tests\ORM\Functional\OJTICPet p JOIN p.children c WHERE p.name = 'Poofy'"
        )
                ->getResult();

        self::assertCount(1, $result);
        $poofy = $result[0];

        self::assertEquals('Aari', $poofy->children[0]->getName());
        self::assertEquals('Zampa', $poofy->children[1]->getName());
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "cat" = OJTICCat::class,
 *      "dog" = OJTICDog::class
 * })
 */
abstract class OJTICPet
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @ORM\Column */
    public $name;

    /** @ORM\ManyToOne(targetEntity=OJTICPET::class) */
    public $mother;

    /**
     * @ORM\OneToMany(targetEntity=OJTICPet::class, mappedBy="mother")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    public $children;

    /**
     * @ORM\ManyToMany(targetEntity=OJTICPet::class)
     * @ORM\JoinTable(name="OTJICPet_Friends",
     *     joinColumns={@ORM\JoinColumn(name="pet_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="friend_id", referencedColumnName="id")})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    public $friends;

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @ORM\Entity
 */
class OJTICCat extends OJTICPet
{
}

/**
 * @ORM\Entity
 */
class OJTICDog extends OJTICPet
{
}

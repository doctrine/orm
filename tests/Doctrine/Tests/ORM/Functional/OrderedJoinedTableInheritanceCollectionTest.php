<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class OrderedJoinedTableInheritanceCollectionTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\OJTIC_Pet'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\OJTIC_Cat'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\OJTIC_Dog'),
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }

        $dog = new OJTIC_Dog();
        $dog->name = "Poofy";

        $dog1 = new OJTIC_Dog();
        $dog1->name = "Zampa";
        $dog2 = new OJTIC_Dog();
        $dog2->name = "Aari";

        $dog1->mother = $dog;
        $dog2->mother = $dog;

        $dog->children[] = $dog1;
        $dog->children[] = $dog2;

        $this->_em->persist($dog);
        $this->_em->persist($dog1);
        $this->_em->persist($dog2);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testOrderdOneToManyCollection()
    {
        $poofy = $this->_em->createQuery("SELECT p FROM Doctrine\Tests\ORM\Functional\OJTIC_Pet p WHERE p.name = 'Poofy'")->getSingleResult();

        $this->assertEquals('Aari', $poofy->children[0]->getName());
        $this->assertEquals('Zampa', $poofy->children[1]->getName());

        $this->_em->clear();

        $result = $this->_em->createQuery(
            "SELECT p, c FROM Doctrine\Tests\ORM\Functional\OJTIC_Pet p JOIN p.children c WHERE p.name = 'Poofy'")
                ->getResult();

        $this->assertEquals(1, count($result));
        $poofy = $result[0];

        $this->assertEquals('Aari', $poofy->children[0]->getName());
        $this->assertEquals('Zampa', $poofy->children[1]->getName());
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "cat" = "OJTIC_Cat",
 *      "dog" = "OJTIC_Dog"})
 */
abstract class OJTIC_Pet
{
    /**
     * @Id
     * @column(type="integer")
     * @generatedValue(strategy="AUTO")
     */
    public $id;

    /**
     *
     * @Column
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="OJTIC_PET")
     */
    public $mother;

    /**
     * @OneToMany(targetEntity="OJTIC_Pet", mappedBy="mother")
     * @OrderBy({"name" = "ASC"})
     */
    public $children;

    /**
     * @ManyToMany(targetEntity="OJTIC_Pet")
     * @JoinTable(name="OTJIC_Pet_Friends",
     *     joinColumns={@JoinColumn(name="pet_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="friend_id", referencedColumnName="id")})
     * @OrderBy({"name" = "ASC"})
     */
    public $friends;

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @Entity
 */
class OJTIC_Cat extends OJTIC_Pet
{

}

/**
 * @Entity
 */
class OJTIC_Dog extends OJTIC_Pet
{

}
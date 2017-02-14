<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a self referential one-to-one association mapping (without inheritance).
 * Relation is defined as the mentor that a customer choose. The mentor could
 * help only one other customer, while a customer can choose only one mentor
 * for receiving support.
 * Inverse side is not present.
 */
class OneToOneSelfReferentialAssociationTest extends OrmFunctionalTestCase
{
    private $customer;
    private $mentor;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->customer = new ECommerceCustomer();
        $this->customer->setName('Anakin Skywalker');
        $this->mentor = new ECommerceCustomer();
        $this->mentor->setName('Obi-wan Kenobi');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet() {
        $this->customer->setMentor($this->mentor);
        $this->em->persist($this->customer);
        $this->em->flush();

        self::assertForeignKeyIs($this->mentor->getId());
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->customer->setMentor($this->mentor);
        $this->em->persist($this->customer);
        $this->customer->removeMentor();

        $this->em->flush();

        self::assertForeignKeyIs(null);
    }

    public function testFind()
    {
        $id = $this->createFixture();

        $customer = $this->em->find(ECommerceCustomer::class, $id);
        self::assertNotInstanceOf(Proxy::class, $customer->getMentor());
    }

    public function testEagerLoadsAssociation()
    {
        $this->createFixture();

        $query = $this->em->createQuery('select c, m from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c left join c.mentor m order by c.id asc');
        $result = $query->getResult();
        $customer = $result[0];
        self::assertLoadingOfAssociation($customer);
    }

    /**
     * @group mine
     * @return unknown_type
     */
    public function testLazyLoadsAssociation()
    {
        $this->createFixture();

        $metadata = $this->em->getClassMetadata(ECommerceCustomer::class);
        $metadata->associationMappings['mentor']['fetch'] = FetchMode::LAZY;

        $query = $this->em->createQuery("select c from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c where c.name='Luke Skywalker'");
        $result = $query->getResult();
        $customer = $result[0];
        self::assertLoadingOfAssociation($customer);
    }

    public function testMultiSelfReference()
    {
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(MultiSelfReference::class)
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }

        $entity1 = new MultiSelfReference();
        $this->em->persist($entity1);
        $entity1->setOther1($entity2 = new MultiSelfReference);
        $entity1->setOther2($entity3 = new MultiSelfReference);
        $this->em->flush();

        $this->em->clear();

        $entity2 = $this->em->find(get_class($entity1), $entity1->getId());

        self::assertInstanceOf(MultiSelfReference::class, $entity2->getOther1());
        self::assertInstanceOf(MultiSelfReference::class, $entity2->getOther2());
        self::assertNull($entity2->getOther1()->getOther1());
        self::assertNull($entity2->getOther1()->getOther2());
        self::assertNull($entity2->getOther2()->getOther1());
        self::assertNull($entity2->getOther2()->getOther2());
    }

    public function assertLoadingOfAssociation($customer)
    {
        self::assertInstanceOf(ECommerceCustomer::class, $customer->getMentor());
        self::assertEquals('Obi-wan Kenobi', $customer->getMentor()->getName());
    }

    public function assertForeignKeyIs($value) {
        $foreignKey = $this->em->getConnection()->executeQuery('SELECT mentor_id FROM ecommerce_customers WHERE id=?', [$this->customer->getId()])->fetchColumn();
        self::assertEquals($value, $foreignKey);
    }

    private function createFixture()
    {
        $customer = new ECommerceCustomer;
        $customer->setName('Luke Skywalker');
        $mentor = new ECommerceCustomer;
        $mentor->setName('Obi-wan Kenobi');
        $customer->setMentor($mentor);

        $this->em->persist($customer);

        $this->em->flush();
        $this->em->clear();

        return $customer->getId();
    }
}

/**
 * @ORM\Entity
 */
class MultiSelfReference {
    /** @ORM\Id @ORM\GeneratedValue(strategy="AUTO") @ORM\Column(type="integer") */
    private $id;
    /**
     * @ORM\OneToOne(targetEntity="MultiSelfReference", cascade={"persist"})
     * @ORM\JoinColumn(name="other1", referencedColumnName="id")
     */
    private $other1;
    /**
     * @ORM\OneToOne(targetEntity="MultiSelfReference", cascade={"persist"})
     * @ORM\JoinColumn(name="other2", referencedColumnName="id")
     */
    private $other2;

    public function getId() {return $this->id;}
    public function setOther1($other1) {$this->other1 = $other1;}
    public function getOther1() {return $this->other1;}
    public function setOther2($other2) {$this->other2 = $other2;}
    public function getOther2() {return $this->other2;}
}

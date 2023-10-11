<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\Models\ECommerce\ECommerceCustomer;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests a self referential one-to-one association mapping (without inheritance).
 * Relation is defined as the mentor that a customer choose. The mentor could
 * help only one other customer, while a customer can choose only one mentor
 * for receiving support.
 * Inverse side is not present.
 */
class OneToOneSelfReferentialAssociationTest extends OrmFunctionalTestCase
{
    private ECommerceCustomer $customer;

    private ECommerceCustomer $mentor;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->customer = new ECommerceCustomer();
        $this->customer->setName('Anakin Skywalker');
        $this->mentor = new ECommerceCustomer();
        $this->mentor->setName('Obi-wan Kenobi');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet(): void
    {
        $this->customer->setMentor($this->mentor);
        $this->_em->persist($this->customer);
        $this->_em->flush();

        $this->assertForeignKeyIs($this->mentor->getId());
    }

    public function testRemovesOneToOneAssociation(): void
    {
        $this->customer->setMentor($this->mentor);
        $this->_em->persist($this->customer);
        $this->customer->removeMentor();

        $this->_em->flush();

        $this->assertForeignKeyIs(null);
    }

    public function testFind(): void
    {
        $id = $this->createFixture();

        $customer = $this->_em->find(ECommerceCustomer::class, $id);
        self::assertFalse($this->isUninitializedObject($customer->getMentor()));
    }

    public function testEagerLoadsAssociation(): void
    {
        $customerId = $this->createFixture();

        $query = $this->_em->createQuery('select c, m from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c left join c.mentor m where c.id = :id');
        $query->setParameter('id', $customerId);

        $result   = $query->getResult();
        $customer = $result[0];
        $this->assertLoadingOfAssociation($customer);
    }

    #[Group('mine')]
    public function testLazyLoadsAssociation(): void
    {
        $this->createFixture();

        $metadata                                       = $this->_em->getClassMetadata(ECommerceCustomer::class);
        $metadata->associationMappings['mentor']->fetch = ClassMetadata::FETCH_LAZY;

        $query    = $this->_em->createQuery("select c from Doctrine\Tests\Models\ECommerce\ECommerceCustomer c where c.name='Luke Skywalker'");
        $result   = $query->getResult();
        $customer = $result[0];
        $this->assertLoadingOfAssociation($customer);
    }

    public function testMultiSelfReference(): void
    {
        $this->createSchemaForModels(MultiSelfReference::class);

        $entity1 = new MultiSelfReference();
        $this->_em->persist($entity1);
        $entity1->setOther1($entity2 = new MultiSelfReference());
        $entity1->setOther2($entity3 = new MultiSelfReference());
        $this->_em->flush();

        $this->_em->clear();

        $entity2 = $this->_em->find($entity1::class, $entity1->getId());

        self::assertInstanceOf(MultiSelfReference::class, $entity2->getOther1());
        self::assertInstanceOf(MultiSelfReference::class, $entity2->getOther2());
        self::assertNull($entity2->getOther1()->getOther1());
        self::assertNull($entity2->getOther1()->getOther2());
        self::assertNull($entity2->getOther2()->getOther1());
        self::assertNull($entity2->getOther2()->getOther2());
    }

    public function assertLoadingOfAssociation($customer): void
    {
        self::assertInstanceOf(ECommerceCustomer::class, $customer->getMentor());
        self::assertEquals('Obi-wan Kenobi', $customer->getMentor()->getName());
    }

    public function assertForeignKeyIs($value): void
    {
        $foreignKey = $this->_em->getConnection()->executeQuery('SELECT mentor_id FROM ecommerce_customers WHERE id=?', [$this->customer->getId()])->fetchOne();
        self::assertEquals($value, $foreignKey);
    }

    private function createFixture(): int
    {
        $customer = new ECommerceCustomer();
        $customer->setName('Luke Skywalker');
        $mentor = new ECommerceCustomer();
        $mentor->setName('Obi-wan Kenobi');
        $customer->setMentor($mentor);

        $this->_em->persist($customer);

        $this->_em->flush();
        $this->_em->clear();

        return $customer->getId();
    }
}

#[Entity]
class MultiSelfReference
{
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Column(type: 'integer')]
    private int $id;

    #[OneToOne(targetEntity: 'MultiSelfReference', cascade: ['persist'])]
    #[JoinColumn(name: 'other1', referencedColumnName: 'id')]
    private MultiSelfReference|null $other1 = null;

    #[OneToOne(targetEntity: 'MultiSelfReference', cascade: ['persist'])]
    #[JoinColumn(name: 'other2', referencedColumnName: 'id')]
    private MultiSelfReference|null $other2 = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setOther1(MultiSelfReference $other1): void
    {
        $this->other1 = $other1;
    }

    public function getOther1(): MultiSelfReference|null
    {
        return $this->other1;
    }

    public function setOther2(MultiSelfReference $other2): void
    {
        $this->other2 = $other2;
    }

    public function getOther2(): MultiSelfReference|null
    {
        return $this->other2;
    }
}

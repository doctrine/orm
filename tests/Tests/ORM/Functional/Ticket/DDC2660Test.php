<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-2660')]
class DDC2660Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC2660Product::class),
                    $this->_em->getClassMetadata(DDC2660Customer::class),
                    $this->_em->getClassMetadata(DDC2660CustomerOrder::class),
                ],
            );
        } catch (Exception) {
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $product  = new DDC2660Product();
            $customer = new DDC2660Customer();
            $order    = new DDC2660CustomerOrder($product, $customer, 'name' . $i);

            $this->_em->persist($product);
            $this->_em->persist($customer);
            $this->_em->flush();

            $this->_em->persist($order);
            $this->_em->flush();
        }

        $this->_em->clear();
    }

    public function testIssueWithExtraColumn(): void
    {
        $sql = 'SELECT o.product_id, o.customer_id, o.name FROM ddc_2660_customer_order o';

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(DDC2660CustomerOrder::class, 'c');

        $query  = $this->_em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        self::assertCount(5, $result);

        foreach ($result as $order) {
            self::assertNotNull($order);
            self::assertInstanceOf(DDC2660CustomerOrder::class, $order);
        }
    }

    public function testIssueWithoutExtraColumn(): void
    {
        $sql = 'SELECT o.product_id, o.customer_id FROM ddc_2660_customer_order o';

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(DDC2660CustomerOrder::class, 'c');

        $query  = $this->_em->createNativeQuery($sql, $rsm);
        $result = $query->getResult();

        self::assertCount(5, $result);

        foreach ($result as $order) {
            self::assertNotNull($order);
            self::assertInstanceOf(DDC2660CustomerOrder::class, $order);
        }
    }
}
#[Table(name: 'ddc_2660_product')]
#[Entity]
class DDC2660Product
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Table(name: 'ddc_2660_customer')]
#[Entity]
class DDC2660Customer
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Table(name: 'ddc_2660_customer_order')]
#[Entity]
class DDC2660CustomerOrder
{
    public function __construct(
        #[Id]
        #[ManyToOne(targetEntity: 'DDC2660Product')]
        public DDC2660Product $product,
        #[Id]
        #[ManyToOne(targetEntity: 'DDC2660Customer')]
        public DDC2660Customer $customer,
        #[Column(type: 'string', length: 255)]
        public string $name,
    ) {
    }
}

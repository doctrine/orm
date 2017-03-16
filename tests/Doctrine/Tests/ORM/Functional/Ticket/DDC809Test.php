<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC809Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC809Variant::class),
            $this->_em->getClassMetadata(DDC809SpecificationValue::class)
            ]
        );

        $conn = $this->_em->getConnection();
        $conn->insert('specification_value_test', ['specification_value_id' => 94589]);
        $conn->insert('specification_value_test', ['specification_value_id' => 94593]);
        $conn->insert('specification_value_test', ['specification_value_id' => 94606]);
        $conn->insert('specification_value_test', ['specification_value_id' => 94607]);
        $conn->insert('specification_value_test', ['specification_value_id' => 94609]);
        $conn->insert('specification_value_test', ['specification_value_id' => 94711]);

        $conn->insert('variant_test', ['variant_id' => 545208]);
        $conn->insert('variant_test', ['variant_id' => 545209]);

        $conn->insert('var_spec_value_test', ['variant_id' => 545208, 'specification_value_id' => 94606]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545208, 'specification_value_id' => 94607]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545208, 'specification_value_id' => 94609]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545208, 'specification_value_id' => 94711]);

        $conn->insert('var_spec_value_test', ['variant_id' => 545209, 'specification_value_id' => 94589]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545209, 'specification_value_id' => 94593]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545209, 'specification_value_id' => 94606]);
        $conn->insert('var_spec_value_test', ['variant_id' => 545209, 'specification_value_id' => 94607]);
    }

    /**
     * @group DDC-809
     */
    public function testIssue()
    {
        $result = $this->_em->createQueryBuilder()
                        ->select('Variant, SpecificationValue')
                        ->from(DDC809Variant::class, 'Variant')
                        ->leftJoin('Variant.SpecificationValues', 'SpecificationValue')
                        ->getQuery()
                        ->getResult();

        $this->assertEquals(4, count($result[0]->getSpecificationValues()), "Works in test-setup.");
        $this->assertEquals(4, count($result[1]->getSpecificationValues()), "Only returns 2 in the case of the hydration bug.");
    }
}

/**
 * @Table(name="variant_test")
 * @Entity
 */
class DDC809Variant
{
    /**
     * @Column(name="variant_id", type="integer")
     * @Id
     */
    protected $variantId;

    /**
     * @ManyToMany(targetEntity="DDC809SpecificationValue", inversedBy="Variants")
     * @JoinTable(name="var_spec_value_test",
     *   joinColumns={
     *     @JoinColumn(name="variant_id", referencedColumnName="variant_id")
     *   },
     *   inverseJoinColumns={
     *     @JoinColumn(name="specification_value_id", referencedColumnName="specification_value_id")
     *   }
     * )
     */
    protected $SpecificationValues;

    public function getSpecificationValues()
    {
        return $this->SpecificationValues;
    }
}

/**
 * @Table(name="specification_value_test")
 * @Entity
 */
class DDC809SpecificationValue
{
    /**
     * @Column(name="specification_value_id", type="integer")
     * @Id
     */
    protected $specificationValueId;

    /**
     * @var Variant
     *
     * @ManyToMany(targetEntity="DDC809Variant", mappedBy="SpecificationValues")
     */
    protected $Variants;
}

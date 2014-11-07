<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\JoinColumn;

class MergeCompositeToOneKeyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\MergeCompositeToOneKeyCountry'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\MergeCompositeToOneKeyState'),
        ));
    }

    public function testIssue()
    {
        $country = new MergeCompositeToOneKeyCountry();
        $country->country = 'US';
        $state = new MergeCompositeToOneKeyState();
        $state->state = 'CA';
        $state->country = $country;

        $this->_em->merge($country);
        $this->_em->merge($state);
    }
}

/**
 * @Entity
 */
class MergeCompositeToOneKeyCountry
{
    /**
     * @Id
     * @Column(type="string", name="country")
     * @GeneratedValue(strategy="NONE")
     */
    public $country;
}

/**
 * @Entity
  */
class MergeCompositeToOneKeyState
{
    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="NONE")
     */
    public $state;

    /**
     * @Id
     * @ManyToOne(targetEntity="MergeCompositeToOneKeyCountry")
     * @JoinColumn(name="country", referencedColumnName="country")
     */
    public $country;
}

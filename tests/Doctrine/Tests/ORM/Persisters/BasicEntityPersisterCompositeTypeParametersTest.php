<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Country;

class BasicEntityPersisterCompositeTypeParametersTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var BasicEntityPersister
     */
    protected $_persister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_em = $this->_getTestEntityManager();

        $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Country');
        $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Admin1');
        $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Admin1AlternateName');

        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata('Doctrine\Tests\Models\GeoNames\Admin1AlternateName'));

    }

    public function testExpandParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);


        list ($values, $types) = $this->_persister->expandParameters(array(
            'admin1' => $admin1
        ));

        $this->assertEquals(array('integer', 'string'), $types);
        $this->assertEquals(array(10, 'IT'), $values);
    }

    public function testExpandCriteriaParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);

        $criteria = Criteria::create();

        $criteria->andWhere(Criteria::expr()->eq("admin1", $admin1));

        list ($values, $types) = $this->_persister->expandCriteriaParameters($criteria);

        $this->assertEquals(array('integer', 'string'), $types);
        $this->assertEquals(array(10, 'IT'), $values);
    }
}

<?php

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeParametersTest extends OrmTestCase
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

        $this->_em->getClassMetadata(Country::class);
        $this->_em->getClassMetadata(Admin1::class);
        $this->_em->getClassMetadata(Admin1AlternateName::class);

        $this->_persister = new BasicEntityPersister($this->_em, $this->_em->getClassMetadata(Admin1AlternateName::class));

    }

    public function testExpandParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);

        list ($values, $types) = $this->_persister->expandParameters(['admin1' => $admin1]);

        self::assertEquals([Type::getType('integer'), Type::getType('string')], $types);
        self::assertEquals([10, 'IT'], $values);
    }

    public function testExpandCriteriaParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);

        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq("admin1", $admin1));

        list ($values, $types) = $this->_persister->expandCriteriaParameters($criteria);

        self::assertEquals([Type::getType('integer'), Type::getType('string')], $types);
        self::assertEquals([10, 'IT'], $values);
    }
}

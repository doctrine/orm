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
    protected $persister;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->em = $this->getTestEntityManager();

        $this->em->getClassMetadata(Country::class);
        $this->em->getClassMetadata(Admin1::class);
        $this->em->getClassMetadata(Admin1AlternateName::class);

        $this->persister = new BasicEntityPersister($this->em, $this->em->getClassMetadata(Admin1AlternateName::class));

    }

    public function testExpandParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);

        list ($values, $types) = $this->persister->expandParameters(['admin1' => $admin1]);

        self::assertEquals([Type::getType('integer'), Type::getType('string')], $types);
        self::assertEquals([10, 'IT'], $values);
    }

    public function testExpandCriteriaParametersWillExpandCompositeEntityKeys()
    {
        $country = new Country("IT", "Italy");
        $admin1  = new Admin1(10, "Rome", $country);

        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq("admin1", $admin1));

        list ($values, $types) = $this->persister->expandCriteriaParameters($criteria);

        self::assertEquals([Type::getType('integer'), Type::getType('string')], $types);
        self::assertEquals([10, 'IT'], $values);
    }
}

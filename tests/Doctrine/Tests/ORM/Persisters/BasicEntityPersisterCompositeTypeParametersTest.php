<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use function array_map;

class BasicEntityPersisterCompositeTypeParametersTest extends OrmTestCase
{
    /** @var BasicEntityPersister */
    protected $persister;

    /** @var EntityManagerInterface */
    protected $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->em = $this->getTestEntityManager();

        $this->em->getClassMetadata(Country::class);
        $this->em->getClassMetadata(Admin1::class);
        $this->em->getClassMetadata(Admin1AlternateName::class);

        $this->persister = new BasicEntityPersister($this->em, $this->em->getClassMetadata(Admin1AlternateName::class));
    }

    public function testExpandParametersWillExpandCompositeEntityKeys() : void
    {
        $country = new Country('IT', 'Italy');
        $admin1  = new Admin1(10, 'Rome', $country);

        [$values, $types] = $this->persister->expandParameters(['admin1' => $admin1]);

        self::assertEquals([10, 'IT'], $values);
        self::assertEquals(
            ['integer', 'string'],
            array_map(
                static function (Type $type) : string {
                    return $type->getName();
                },
                $types
            )
        );
    }

    public function testExpandCriteriaParametersWillExpandCompositeEntityKeys() : void
    {
        $country = new Country('IT', 'Italy');
        $admin1  = new Admin1(10, 'Rome', $country);

        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('admin1', $admin1));

        [$values, $types] = $this->persister->expandCriteriaParameters($criteria);

        self::assertEquals([10, 'IT'], $values);
        self::assertEquals(
            ['integer', 'string'],
            array_map(
                static function (Type $type) : string {
                    return $type->getName();
                },
                $types
            )
        );
    }
}

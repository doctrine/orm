<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Exception\CantUseInOperatorOnCompositeKeys;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeSqlTest extends OrmTestCase
{
    protected BasicEntityPersister $persister;
    protected EntityManagerMock $entityManager;
    private AssociationMapping $associationMapping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager      = $this->getTestEntityManager();
        $this->persister          = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Admin1AlternateName::class));
        $this->associationMapping = new ManyToOneAssociationMapping(
            fieldName: 'admin1',
            sourceEntity: WhoCares::class,
            targetEntity: Admin1AlternateName::class,
        );
    }

    public function testSelectConditionStatementEq(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', 1, $this->associationMapping, Comparison::EQ);
        self::assertEquals('t0.admin1 = ? AND t0.country = ?', $statement);
    }

    public function testSelectConditionStatementEqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, $this->associationMapping, Comparison::IS);
        self::assertEquals('t0.admin1 IS NULL AND t0.country IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, $this->associationMapping, Comparison::NEQ);
        self::assertEquals('t0.admin1 IS NOT NULL AND t0.country IS NOT NULL', $statement);
    }

    public function testSelectConditionStatementIn(): void
    {
        $this->expectException(CantUseInOperatorOnCompositeKeys::class);
        $this->persister->getSelectConditionStatementSQL('admin1', [], $this->associationMapping, Comparison::IN);
    }
}

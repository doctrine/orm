<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeSqlTest extends OrmTestCase
{
    /** @var BasicEntityPersister */
    protected $persister;

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
        $this->persister     = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Admin1AlternateName::class));
    }

    public function testSelectConditionStatementEq(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', 1, [], Comparison::EQ);
        $this->assertEquals('t0.admin1 = ? AND t0.country = ?', $statement);
    }

    public function testSelectConditionStatementEqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::IS);
        $this->assertEquals('t0.admin1 IS NULL AND t0.country IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, [], Comparison::NEQ);
        $this->assertEquals('t0.admin1 IS NOT NULL AND t0.country IS NOT NULL', $statement);
    }

    public function testSelectConditionStatementIn(): void
    {
        $this->expectException('Doctrine\ORM\ORMException');
        $this->persister->getSelectConditionStatementSQL('admin1', [], [], Comparison::IN);
    }
}

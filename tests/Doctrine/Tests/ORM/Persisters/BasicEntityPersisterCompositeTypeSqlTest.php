<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\GeoNames\Admin1AlternateName;
use Doctrine\Tests\OrmTestCase;

class BasicEntityPersisterCompositeTypeSqlTest extends OrmTestCase
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

        $this->em        = $this->getTestEntityManager();
        $this->persister = new BasicEntityPersister($this->em, $this->em->getClassMetadata(Admin1AlternateName::class));
    }

    public function testSelectConditionStatementEq() : void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', 1, new OneToOneAssociationMetadata('admin1'), Comparison::EQ);
        self::assertEquals('t0."admin1" = ? AND t0."country" = ?', $statement);
    }

    public function testSelectConditionStatementEqNull() : void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, new OneToOneAssociationMetadata('admin1'), Comparison::IS);
        self::assertEquals('t0."admin1" IS NULL AND t0."country" IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull() : void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('admin1', null, new OneToOneAssociationMetadata('admin1'), Comparison::NEQ);
        self::assertEquals('t0."admin1" IS NOT NULL AND t0."country" IS NOT NULL', $statement);
    }

    /**
     * @expectedException Doctrine\ORM\ORMException
     */
    public function testSelectConditionStatementIn() : void
    {
        $this->persister->getSelectConditionStatementSQL('admin1', [], new OneToOneAssociationMetadata('admin1'), Comparison::IN);
    }
}

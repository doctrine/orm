<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Internal\TopologicalSort\CycleDetectedException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Mocks\AttributeDriverFactory;
use Doctrine\Tests\Models\InferredNullability\OptionalSelfReference;
use Doctrine\Tests\Models\InferredNullability\RequiredSelfReference;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\TestUtil;

class InferredNullabilityTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! isset(static::$sharedConn)) {
            static::$sharedConn = TestUtil::getConnection();
        }

        $this->_em = $this->getEntityManager(null, AttributeDriverFactory::createAttributeDriver(
            [__DIR__ . '/../../Models/InferredNullability'],
            true,
        ));

        $this->_schemaTool = new SchemaTool($this->_em);

        parent::setUp();

        $this->setUpEntitySchema([RequiredSelfReference::class, OptionalSelfReference::class]);
    }

    public function testNonNullablePHPTypeProducesANotNullForeignKeyColumn(): void
    {
        $class  = $this->_em->getClassMetadata(RequiredSelfReference::class);
        $schema = $this->_schemaTool->getSchemaFromMetadata([$class]);
        $column = $schema->getTable($class->getTableName())
            ->getColumn($class->associationMappings['ref']->joinColumns[0]->name);

        self::assertTrue($column->getNotnull());
    }

    /** A cycle can only be broken at a nullable join column, so this one never reaches the database. */
    public function testCycleOfNonNullableAssociationsIsRejectedBeforeAnySqlIsExecuted(): void
    {
        $a      = new RequiredSelfReference();
        $b      = new RequiredSelfReference();
        $a->ref = $b;
        $b->ref = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);

        $this->getQueryLog()->reset()->enable();

        try {
            $this->_em->flush();
            self::fail('A cycle of NOT NULL join columns cannot be inserted.');
        } catch (CycleDetectedException $exception) {
            self::assertContains($a, $exception->getCycle());
            self::assertContains($b, $exception->getCycle());
        }

        self::assertQueryCount(0);
    }

    /** A nullable PHP type keeps the join column nullable, so the cycle can still be broken. */
    public function testCycleOfNullableAssociationsIsResolvedWithAnExtraUpdate(): void
    {
        $a      = new OptionalSelfReference();
        $b      = new OptionalSelfReference();
        $a->ref = $b;
        $b->ref = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->flush();
        $this->_em->clear();

        $reloaded = $this->_em->find(OptionalSelfReference::class, $a->id);

        self::assertNotNull($reloaded);
        self::assertSame($b->id, $reloaded->ref?->id);
    }
}

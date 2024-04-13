<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\DbalTypes\NegativeToPositiveType;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\CustomType\CustomTypeChild;
use Doctrine\Tests\Models\CustomType\CustomTypeParent;
use Doctrine\Tests\Models\Generic\NonAlphaColumnsEntity;
use Doctrine\Tests\OrmTestCase;
use ReflectionMethod;

use function array_shift;

class BasicEntityPersisterTypeValueSqlTest extends OrmTestCase
{
    /** @var BasicEntityPersister */
    protected $persister;

    /** @var EntityManagerMock */
    protected $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        if (DBALType::hasType('negative_to_positive')) {
            DBALType::overrideType('negative_to_positive', NegativeToPositiveType::class);
        } else {
            DBALType::addType('negative_to_positive', NegativeToPositiveType::class);
        }

        if (DBALType::hasType('upper_case_string')) {
            DBALType::overrideType('upper_case_string', UpperCaseStringType::class);
        } else {
            DBALType::addType('upper_case_string', UpperCaseStringType::class);
        }

        $this->entityManager = $this->getTestEntityManager();

        $this->persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(CustomTypeParent::class));
    }

    public function testGetInsertSQLUsesTypeValuesSQL(): void
    {
        $method = new ReflectionMethod($this->persister, 'getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->persister);

        self::assertEquals('INSERT INTO customtype_parents (customInteger, child_id) VALUES (ABS(?), ?)', $sql);
    }

    public function testUpdateUsesTypeValuesSQL(): void
    {
        $child = new CustomTypeChild();

        $parent                = new CustomTypeParent();
        $parent->customInteger = 1;
        $parent->child         = $child;

        $this->entityManager->getUnitOfWork()->registerManaged($parent, ['id' => 1], ['customInteger' => 0, 'child' => null]);
        $this->entityManager->getUnitOfWork()->registerManaged($child, ['id' => 1], []);

        $this->entityManager->getUnitOfWork()->propertyChanged($parent, 'customInteger', 0, 1);
        $this->entityManager->getUnitOfWork()->propertyChanged($parent, 'child', null, $child);

        $this->persister->update($parent);

        $executeStatements = $this->entityManager->getConnection()->getExecuteStatements();

        self::assertEquals('UPDATE customtype_parents SET customInteger = ABS(?), child_id = ? WHERE id = ?', $executeStatements[0]['sql']);
    }

    public function testGetSelectConditionSQLUsesTypeValuesSQL(): void
    {
        $method = new ReflectionMethod($this->persister, 'getSelectConditionSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($this->persister, ['customInteger' => 1, 'child' => 1]);

        self::assertEquals('t0.customInteger = ABS(?) AND t0.child_id = ?', $sql);
    }

    /** @group DDC-1719 */
    public function testStripNonAlphanumericCharactersFromSelectColumnListSQL(): void
    {
        $persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(NonAlphaColumnsEntity::class));
        $method    = new ReflectionMethod($persister, 'getSelectColumnsSQL');
        $method->setAccessible(true);

        self::assertEquals('t0."simple-entity-id" AS simpleentityid_1, t0."simple-entity-value" AS simpleentityvalue_2', $method->invoke($persister));
    }

    /** @group DDC-2073 */
    public function testSelectConditionStatementIsNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, [], Comparison::IS);
        self::assertEquals('test IS NULL', $statement);
    }

    public function testSelectConditionStatementEqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, [], Comparison::EQ);
        self::assertEquals('test IS NULL', $statement);
    }

    public function testSelectConditionStatementNeqNull(): void
    {
        $statement = $this->persister->getSelectConditionStatementSQL('test', null, [], Comparison::NEQ);
        self::assertEquals('test IS NOT NULL', $statement);
    }

    /** @group DDC-3056 */
    public function testSelectConditionStatementWithMultipleValuesContainingNull(): void
    {
        self::assertEquals(
            '(t0.id IN (?) OR t0.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', [null])
        );

        self::assertEquals(
            '(t0.id IN (?) OR t0.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', [null, 123])
        );

        self::assertEquals(
            '(t0.id IN (?) OR t0.id IS NULL)',
            $this->persister->getSelectConditionStatementSQL('id', [123, null])
        );
    }

    public function testCountCondition(): void
    {
        $persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(NonAlphaColumnsEntity::class));

        // Using a criteria as array
        $statement = $persister->getCountSQL(['value' => 'bar']);
        self::assertEquals('SELECT COUNT(*) FROM "not-a-simple-entity" t0 WHERE t0."simple-entity-value" = ?', $statement);

        // Using a criteria object
        $criteria  = new Criteria(Criteria::expr()->eq('value', 'bar'));
        $statement = $persister->getCountSQL($criteria);
        self::assertEquals('SELECT COUNT(*) FROM "not-a-simple-entity" t0 WHERE t0."simple-entity-value" = ?', $statement);
    }

    public function testCountEntities(): void
    {
        self::assertEquals(0, $this->persister->count());
    }

    public function testDeleteManyToManyUsesTypeValuesSQL(): void
    {
        $friend = new CustomTypeParent();
        $parent = new CustomTypeParent();
        $parent->addMyFriend($friend);

        $this->entityManager->getUnitOfWork()->registerManaged($parent, ['id' => 1], []);
        $this->entityManager->getUnitOfWork()->registerManaged($friend, ['id' => 2], []);

        $this->persister->delete($parent);

        $deletes = $this->entityManager->getConnection()->getDeletes();

        self::assertEquals([
            'table' => 'customtype_parent_friends',
            'criteria' => ['friend_customtypeparent_id' => 1],
            'types' => ['integer'],
        ], array_shift($deletes));
        self::assertEquals([
            'table' => 'customtype_parent_friends',
            'criteria' => ['customtypeparent_id' => 1],
            'types' => ['integer'],
        ], array_shift($deletes));
    }
}

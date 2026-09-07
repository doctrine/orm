<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Query;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\Models\CustomType\CustomTypeInferredParameter;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Tests the type of the bind parameters of a DQL query being inferred from the fields they are
 * compared to, rather than guessed from the bound value.
 */
class ParameterTypeInferenceTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (DBALType::hasType(UpperCaseStringType::NAME)) {
            DBALType::overrideType(UpperCaseStringType::NAME, UpperCaseStringType::class);
        } else {
            DBALType::addType(UpperCaseStringType::NAME, UpperCaseStringType::class);
        }

        $this->useModelSet('custom_type_inferred_parameter');

        parent::setUp();

        $entity          = new CustomTypeInferredParameter();
        $entity->encoded = 'abc';

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testItIsDisabledByDefault(): void
    {
        self::assertFalse($this->_em->getConfiguration()->isInferParameterTypesEnabled());
        self::assertNull($this->createQuery('WHERE e.encoded = :value')->setParameter('value', 'abc')->getOneOrNullResult());
    }

    public function testItConvertsTheValueThroughTheTypeOfTheComparedField(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        self::assertNotNull($this->createQuery('WHERE e.encoded = :value')->setParameter('value', 'abc')->getOneOrNullResult());
    }

    public function testAnExplicitlyGivenTypeStillWins(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        // 'nop' is what 'abc' is stored as, so binding it as a plain string matches.
        $entity = $this->createQuery('WHERE e.encoded = :value')
            ->setParameter('value', 'nop', 'string')
            ->getOneOrNullResult();

        self::assertNotNull($entity);
    }

    public function testItCanBeTurnedOffForASingleQuery(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        $query = $this->createQuery('WHERE e.encoded = :value')->setParameter('value', 'abc');

        self::assertNull($query->setInferParameterTypes(false)->getOneOrNullResult());
        self::assertNotNull($query->setInferParameterTypes(null)->getOneOrNullResult());
    }

    public function testItCanBeTurnedOnForASingleQuery(): void
    {
        $query = $this->createQuery('WHERE e.encoded = :value')->setParameter('value', 'abc');

        self::assertNull($query->getOneOrNullResult());
        self::assertNotNull($query->setInferParameterTypes(true)->getOneOrNullResult());
    }

    public function testItConvertsEveryValueOfAnInList(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        $entities = $this->createQuery('WHERE e.encoded IN (:values)')
            ->setParameter('values', ['abc', 'xyz'])
            ->getResult();

        self::assertCount(1, $entities);
    }

    public function testAnEmptyInListStillMatchesNothing(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        self::assertSame([], $this->createQuery('WHERE e.encoded IN (:values)')->setParameter('values', [])->getResult());
    }

    public function testItAppliesToAQueryWhoseSqlWasCachedWithTheFeatureDisabled(): void
    {
        $queryCache = new ArrayAdapter();

        $this->createQuery('WHERE e.encoded = :value')
            ->setQueryCache($queryCache)
            ->setParameter('value', 'abc')
            ->getOneOrNullResult();

        $this->_em->getConfiguration()->setInferParameterTypes(true);

        $entity = $this->createQuery('WHERE e.encoded = :value')
            ->setQueryCache($queryCache)
            ->setParameter('value', 'abc')
            ->getOneOrNullResult();

        self::assertNotNull($entity);
    }

    /**
     * A type wrapping the placeholder in SQL is written through that wrapper by the persisters,
     * so the comparison has to keep using a bare placeholder.
     */
    public function testATypeConvertingTheValueInSqlIsLeftAlone(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        $sql = $this->createQuery('WHERE e.upperCased = :value')->getSQL();

        self::assertStringEndsWith('.upperCased = ?', $sql);
    }

    /**
     * Converting a loosely bound value can fail where sending it as it is did not. This is why
     * the behaviour has to be opted into.
     */
    public function testConvertingALooselyBoundValueCanFail(): void
    {
        $this->_em->getConfiguration()->setInferParameterTypes(true);

        $this->expectException(ConversionException::class);

        $this->createQuery('WHERE e.happenedAt > :value')->setParameter('value', '2026-01-01')->getResult();
    }

    private function createQuery(string $where): Query
    {
        return $this->_em->createQuery(
            'SELECT e FROM ' . CustomTypeInferredParameter::class . ' e ' . $where,
        );
    }
}

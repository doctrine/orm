<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\Enums\UserStatus;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function is_int;

class ArrayParameterProcessingTest extends OrmTestCase
{
    /** @return iterable<string, array{bool}> */
    public static function provideQueryKinds(): iterable
    {
        yield 'DQL' => [false];
        yield 'native SQL' => [true];
    }

    private function createQuery(bool $native): AbstractQuery
    {
        $em = $this->getTestEntityManager();

        return $native ? $em->createNativeQuery('', new ResultSetMapping()) : $em->createQuery('');
    }

    #[DataProvider('provideQueryKinds')]
    public function testScalarValuesKeysAndOriginalInputArePreserved(bool $native): void
    {
        $query  = $this->createQuery($native);
        $values = [7 => 12, 'name' => 'value', 91 => null, 'bool' => false, 100 => 1.5];

        self::assertSame($values, $query->processParameterValue($values));
        self::assertSame([7 => 12, 'name' => 'value', 91 => null, 'bool' => false, 100 => 1.5], $values);
        self::assertSame([], $query->processParameterValue([]));
    }

    #[DataProvider('provideQueryKinds')]
    public function testMixedEnumsEntitiesAndNestedArrays(bool $native): void
    {
        $query     = $this->createQuery($native);
        $group     = new CmsGroup();
        $group->id = 42;
        $values    = ['scalar' => 1, 'enum' => UserStatus::Active, 'entity' => $group, 'nested' => [9, 10], 'empty' => []];

        self::assertSame(['scalar' => 1, 'enum' => 'active', 'entity' => 42, 'nested' => 9, 'empty' => false], $query->processParameterValue($values));
        self::assertSame(UserStatus::Active, $values['enum']);
        self::assertSame($group, $values['entity']);
        self::assertSame([9, 10], $values['nested']);
        self::assertSame([1, 'active', null], $query->processParameterValue(new ArrayCollection([1, UserStatus::Active, null])));
    }

    #[DataProvider('provideQueryKinds')]
    public function testReferencesRetainTheirExistingConversionBehavior(bool $native): void
    {
        $query  = $this->createQuery($native);
        $enum   = UserStatus::Active;
        $scalar = 12;
        $values = ['first' => &$enum, 'second' => &$enum, 'scalar' => &$scalar];

        self::assertSame(['first' => 'active', 'second' => 'active', 'scalar' => 12], $query->processParameterValue($values));
        self::assertSame('active', $enum);
        self::assertSame(12, $scalar);
    }

    #[DataProvider('provideQueryKinds')]
    public function testScalarProcessingOverridesAreCalledInTheirOriginalOrder(bool $native): void
    {
        $em    = $this->getTestEntityManager();
        $query = $native ? new class ($em) extends NativeQuery {
            /** @var list<mixed> */
            public array $visited = [];

            public function processParameterValue(mixed $value): mixed
            {
                $this->visited[] = $value;

                return is_int($value) ? $value + 100 : parent::processParameterValue($value);
            }
        } : new class ($em) extends Query {
            /** @var list<mixed> */
            public array $visited = [];

            public function processParameterValue(mixed $value): mixed
            {
                $this->visited[] = $value;

                return is_int($value) ? $value + 100 : parent::processParameterValue($value);
            }
        };
        $input = [1, [2, 3], null];

        self::assertSame([101, 102, null], $query->processParameterValue($input));
        self::assertSame([$input, 1, [2, 3], 2, 3, null], $query->visited);
    }
}

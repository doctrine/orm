<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\DbalTypes\NegativeToPositiveType;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\ORM\Functional\Ticket\GH11608\GH11608Test;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function array_keys;
use function constant;
use function implode;

/**
 * Test the validity of all modelsets
 */
#[Group('DDC-1601')]
class SchemaValidatorTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->registerType(CustomIdObjectType::class);
        $this->registerType(UpperCaseStringType::class);
        $this->registerType(NegativeToPositiveType::class);

        parent::setUp();
    }

    /** @throws DBALException */
    private function registerType(string $className): void
    {
        $type = constant($className . '::NAME');

        if (DBALType::hasType($type)) {
            DBALType::overrideType($type, $className);

            return;
        }

        DBALType::addType($type, $className);
    }

    public static function dataValidateModelSets(): array
    {
        $modelSets = [];

        foreach (array_keys(self::$modelSets) as $modelSet) {

            /**
             * GH11608 Tests whether the Schema validator picks up on invalid mapping. The entities are intentionally
             * invalid, and so for the purpose of this test case, those entities should be ignored.
             *
             * @see GH11608Test
             * @see https://github.com/doctrine/orm/issues/11608
             */
            if ($modelSet === GH11608Test::class) {
                continue;
            }

            $modelSets[$modelSet] = [$modelSet];
        }

        return $modelSets;
    }

    #[DataProvider('dataValidateModelSets')]
    public function testValidateModelSets(string $modelSet): void
    {
        $validator = new SchemaValidator($this->_em);
        $classes   = [];

        foreach (self::$modelSets[$modelSet] as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }

        foreach ($classes as $class) {
            $ce = $validator->validateClass($class);

            self::assertEmpty($ce, 'Invalid Modelset: ' . $modelSet . ' class ' . $class->name . ': ' . implode("\n", $ce));
        }
    }
}

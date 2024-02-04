<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\DbalTypes\NegativeToPositiveType;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_keys;
use function constant;
use function implode;

/**
 * Test the validity of all modelsets
 *
 * @group DDC-1601
 */
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
            $modelSets[$modelSet] = [$modelSet];
        }

        return $modelSets;
    }

    /** @dataProvider dataValidateModelSets */
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

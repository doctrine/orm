<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\DbalTypes\NegativeToPositiveType;
use Doctrine\Tests\DbalTypes\UpperCaseStringType;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\DBAL\Types\Type as DBALType;

/**
 * Test the validity of all modelsets
 *
 * @group DDC-1601
 */
class SchemaValidatorTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->registerType(CustomIdObjectType::class);
        $this->registerType(UpperCaseStringType::class);
        $this->registerType(NegativeToPositiveType::class);

        parent::setUp();
    }

    /**
     * @param string $className
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return void
     */
    private function registerType(string $className)
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

        foreach (array_keys(self::$_modelSets) as $modelSet) {
            $modelSets[$modelSet] = [$modelSet];
        }

        return $modelSets;
    }

    /**
     * @dataProvider dataValidateModelSets
     */
    public function testValidateModelSets(string $modelSet)
    {
        $validator = new SchemaValidator($this->_em);
        $classes   = [];

        foreach (self::$_modelSets[$modelSet] as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }

        foreach ($classes as $class) {
            $ce = $validator->validateClass($class);

            $this->assertEmpty($ce, "Invalid Modelset: " . $modelSet . " class " . $class->name . ": ". implode("\n", $ce));
        }
    }
}

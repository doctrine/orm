<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Test the validity of all modelsets
 *
 * @group DDC-1601
 */
class SchemaValidatorTest extends OrmFunctionalTestCase
{
    static public function dataValidateModelSets()
    {
        $modelSets = [];
        foreach (self::$_modelSets as $modelSet => $classes) {
            if ($modelSet == "customtype") {
                continue;
            }
            $modelSets[] = [$modelSet];
        }
        return $modelSets;
    }

    /**
     * @dataProvider dataValidateModelSets
     */
    public function testValidateModelSets($modelSet)
    {
        $validator = new SchemaValidator($this->_em);

        $classes = [];
        foreach (self::$_modelSets[$modelSet] as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }

        foreach ($classes as $class) {
            $ce = $validator->validateClass($class);

            self::assertEquals(0, count($ce), "Invalid Modelset: " . $modelSet . " class " . $class->name . ": ". implode("\n", $ce));
        }
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaValidator;

/**
 * Test the validity of all modelsets
 *
 * @group DDC-1601
 */
class SchemaValidatorTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    static public function dataValidateModelSets()
    {
        $modelSets = array();
        foreach (self::$_modelSets as $modelSet => $classes) {
            if ($modelSet == "customtype") {
                continue;
            }
            $modelSets[] = array($modelSet);
        }
        return $modelSets;
    }

    /**
     * @dataProvider dataValidateModelSets
     */
    public function testValidateModelSets($modelSet)
    {
        $validator = new SchemaValidator($this->_em);

        $classes = array();
        foreach (self::$_modelSets[$modelSet] as $className) {
            $classes[] = $this->_em->getClassMetadata($className);
        }

        foreach ($classes as $class) {
            $ce = $validator->validateClass($class);

            foreach ($ce as $key => $error) {
                if (strpos($error, "must be private or protected. Public fields may break lazy-loading.") !== false) {
                    unset($ce[$key]);
                }
            }

            $this->assertEquals(0, count($ce), "Invalid Modelset: " . $modelSet . " class " . $class->name . ": ". implode("\n", $ce));
        }
    }
}

<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Tests\Mocks\HydratorMockStatement;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of SingleScalarHydratorTest
 *
 * @author robo
 */
class SingleScalarHydratorTest extends HydrationTest
{
    /** Result set provider for the HYDRATE_SINGLE_SCALAR tests */
    public static function singleScalarResultSetProvider() {
        return array(
          // valid
          array('name' => 'result1',
                'resultSet' => array(
                  array(
                      'u__name' => 'romanb'
                  )
               )),
          // valid
          array('name' => 'result2',
                'resultSet' => array(
                  array(
                      'u__id' => '1'
                  )
             )),
           // invalid
           array('name' => 'result3',
                'resultSet' => array(
                  array(
                      'u__id' => '1',
                      'u__name' => 'romanb'
                  )
             )),
           // invalid
           array('name' => 'result4',
                'resultSet' => array(
                  array(
                      'u__id' => '1'
                  ),
                  array(
                      'u__id' => '2'
                  )
             )),
        );
    }

    /**
     * select u.name from CmsUser u where u.id = 1
     *
     * @dataProvider singleScalarResultSetProvider
     */
    public function testHydrateSingleScalar($name, $resultSet)
    {
        // Faked query components
        $queryComponents = array(
            'u' => array(
                'metadata' => $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'),
                'parent' => null,
                'relation' => null,
                'map' => null
                )
            );

        // Faked table alias map
        $tableAliasMap = array(
            'u' => 'u'
            );

        $stmt = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\SingleScalarHydrator($this->_em);

        if ($name == 'result1') {
            $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                    $queryComponents, $tableAliasMap));
            $this->assertEquals('romanb', $result);
        } else if ($name == 'result2') {
            $result = $hydrator->hydrateAll($stmt, $this->_createParserResult(
                    $queryComponents, $tableAliasMap));
            $this->assertEquals(1, $result);
        } else if ($name == 'result3' || $name == 'result4') {
            try {
                $result = $hydrator->hydrateall($stmt, $this->_createParserResult(
                        $queryComponents, $tableAliasMap));
                $this->fail();
            } catch (\Doctrine\ORM\Exceptions\HydrationException $ex) {}
        }

    }
}


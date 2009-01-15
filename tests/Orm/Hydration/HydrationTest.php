<?php

require_once 'lib/DoctrineTestInit.php';
require_once 'lib/mocks/Doctrine_HydratorMockStatement.php';

/**
 * Description of HydrationTest
 *
 * @author robo
 */
class Orm_Hydration_HydrationTest extends Doctrine_OrmTestCase
{
    protected $_em;

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    /** Helper method */
    protected function _createParserResult($queryComponents, $tableToClassAliasMap, $isMixedQuery = false)
    {
        $parserResult = new Doctrine_ORM_Query_ParserResultDummy();
        $parserResult->setQueryComponents($queryComponents);
        $parserResult->setTableToClassAliasMap($tableToClassAliasMap);
        $parserResult->setMixedQuery($isMixedQuery);
        return $parserResult;
    }
}


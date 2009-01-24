<?php

namespace Doctrine\Tests\ORM\Hydration;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of HydrationTest
 *
 * @author robo
 */
class HydrationTest extends \Doctrine\Tests\OrmTestCase
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
        $parserResult = new \Doctrine\ORM\Query\ParserResultDummy();
        $parserResult->setQueryComponents($queryComponents);
        $parserResult->setTableToClassAliasMap($tableToClassAliasMap);
        $parserResult->setMixedQuery($isMixedQuery);
        return $parserResult;
    }
}


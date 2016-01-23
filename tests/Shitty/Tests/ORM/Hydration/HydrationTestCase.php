<?php

namespace Shitty\Tests\ORM\Hydration;

use Shitty\ORM\Query\ParserResult;
use Shitty\ORM\Query\Parser;

class HydrationTestCase extends \Shitty\Tests\OrmTestCase
{
    protected $_em;

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->_getTestEntityManager();
    }

    /** Helper method */
    protected function _createParserResult($resultSetMapping, $isMixedQuery = false)
    {
        $parserResult = new ParserResult;
        $parserResult->setResultSetMapping($resultSetMapping);
        //$parserResult->setDefaultQueryComponentAlias(key($queryComponents));
        //$parserResult->setTableAliasMap($tableToClassAliasMap);
        $parserResult->setMixedQuery($isMixedQuery);
        return $parserResult;
    }
}

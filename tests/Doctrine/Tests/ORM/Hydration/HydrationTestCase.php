<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Query\ParserResult;

class HydrationTestCase extends \Doctrine\Tests\OrmTestCase
{
    protected $em;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getTestEntityManager();
    }

    /** Helper method */
    protected function createParserResult($resultSetMapping, $isMixedQuery = false)
    {
        $parserResult = new ParserResult;
        $parserResult->setResultSetMapping($resultSetMapping);
        //$parserResult->setDefaultQueryComponentAlias(key($queryComponents));
        //$parserResult->setTableAliasMap($tableToClassAliasMap);
        $parserResult->setMixedQuery($isMixedQuery);
        return $parserResult;
    }
}

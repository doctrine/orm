<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Query\ParserResult;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;

class HydrationTestCase extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp() : void
    {
        parent::setUp();
        $this->em = $this->getTestEntityManager();
    }

    /** Helper method */
    protected function createParserResult($resultSetMapping, $isMixedQuery = false)
    {
        $parserResult = new ParserResult();
        $parserResult->setResultSetMapping($resultSetMapping);
        //$parserResult->setDefaultQueryComponentAlias(key($queryComponents));
        //$parserResult->setTableAliasMap($tableToClassAliasMap);
        $parserResult->setMixedQuery($isMixedQuery);

        return $parserResult;
    }
}

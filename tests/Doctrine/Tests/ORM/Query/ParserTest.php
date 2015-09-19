<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;

class ParserTest extends \Doctrine\Tests\OrmTestCase
{
    public function testAbstractSchemaName()
    {
        $parser = $this->createParser('Doctrine\Tests\Models\CMS\CmsUser');

        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $parser->AbstractSchemaName());
    }

    public function testAbstractSchemaNameTrimsLeadingBackslash()
    {
        $parser = $this->createParser('\Doctrine\Tests\Models\CMS\CmsUser');
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $parser->AbstractSchemaName());
    }

    private function createParser($dql)
    {
        $query = new Query($this->_getTestEntityManager());
        $query->setDQL($dql);

        $parser = new Parser($query);
        $parser->getLexer()->moveNext();

        return $parser;
    }
}

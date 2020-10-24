<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmTestCase;

class ParserTest extends OrmTestCase
{

    /**
     * @covers \Doctrine\ORM\Query\Parser::AbstractSchemaName
     * @group DDC-3715
     */
    public function testAbstractSchemaNameSupportsFQCN()
    {
        $parser = $this->createParser(CmsUser::class);

        $this->assertEquals(CmsUser::class, $parser->AbstractSchemaName());
    }

    /**
     * @covers Doctrine\ORM\Query\Parser::AbstractSchemaName
     * @group DDC-3715
     */
    public function testAbstractSchemaNameSupportsClassnamesWithLeadingBackslash()
    {
        $parser = $this->createParser('\\' . CmsUser::class);

        $this->assertEquals('\\' . CmsUser::class, $parser->AbstractSchemaName());
    }

    /**
     * @covers \Doctrine\ORM\Query\Parser::AbstractSchemaName
     * @group DDC-3715
     */
    public function testAbstractSchemaNameSupportsIdentifier()
    {
        $parser = $this->createParser(\stdClass::class);

        $this->assertEquals(\stdClass::class, $parser->AbstractSchemaName());
    }

    /**
     * @covers \Doctrine\ORM\Query\Parser::AbstractSchemaName
     * @group DDC-3715
     */
    public function testAbstractSchemaNameSupportsNamespaceAlias()
    {
        $parser = $this->createParser('CMS:CmsUser');

        $parser->getEntityManager()->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        $this->assertEquals(CmsUser::class, $parser->AbstractSchemaName());
    }

    /**
     * @covers \Doctrine\ORM\Query\Parser::AbstractSchemaName
     * @group DDC-3715
     */
    public function testAbstractSchemaNameSupportsNamespaceAliasWithRelativeClassname()
    {
        $parser = $this->createParser('Model:CMS\CmsUser');

        $parser->getEntityManager()->getConfiguration()->addEntityNamespace('Model', 'Doctrine\Tests\Models');

        $this->assertEquals(CmsUser::class, $parser->AbstractSchemaName());
    }

    /**
     * @dataProvider validMatches
     * @covers Doctrine\ORM\Query\Parser::match
     * @group DDC-3701
     */
    public function testMatch($expectedToken, $inputString)
    {
        $parser = $this->createParser($inputString);

        $parser->match($expectedToken); // throws exception if not matched

        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider invalidMatches
     * @covers Doctrine\ORM\Query\Parser::match
     * @group DDC-3701
     */
    public function testMatchFailure($expectedToken, $inputString)
    {
        $this->expectException(QueryException::class);

        $parser = $this->createParser($inputString);

        $parser->match($expectedToken);
    }

    public function validMatches()
    {
        /*
         * This only covers the special case handling in the Parser that some
         * tokens that are *not* T_IDENTIFIER are accepted as well when matching
         * identifiers.
         *
         * The basic checks that tokens are classified correctly do not belong here
         * but in LexerTest.
         */
        return [
            [Lexer::T_WHERE, 'where'], // keyword
            [Lexer::T_DOT, '.'], // token that cannot be an identifier
            [Lexer::T_IDENTIFIER, 'someIdentifier'],
            [Lexer::T_IDENTIFIER, 'from'], // also a terminal string (the "FROM" keyword) as in DDC-505
            [Lexer::T_IDENTIFIER, 'comma']
            // not even a terminal string, but the name of a constant in the Lexer (whitebox test)
        ];
    }

    public function invalidMatches()
    {
        return [
            [Lexer::T_DOT, 'ALL'], // ALL is a terminal string (reserved keyword) and also possibly an identifier
            [Lexer::T_DOT, ','], // "," is a token on its own, but cannot be used as identifier
            [Lexer::T_WHERE, 'WITH'], // as in DDC-3697
            [Lexer::T_WHERE, '.'],

            // The following are qualified or aliased names and must not be accepted where only an Identifier is expected
            [Lexer::T_IDENTIFIER, '\\Some\\Class'],
            [Lexer::T_IDENTIFIER, 'Some\\Class'],
            [Lexer::T_IDENTIFIER, 'Some:Name'],
        ];
    }

    /**
     * PHP 7.4 would fail with Notice: Trying to access array offset on value of type null.
     *
     * @see https://github.com/doctrine/orm/pull/7934
     *
     * @group GH7934
     */
    public function testNullLookahead() : void
    {
        $query = new Query($this->_getTestEntityManager());
        $query->setDQL('SELECT CURRENT_TIMESTAMP()');

        $parser = new Parser($query);

        $this->expectException(QueryException::class);
        $parser->match(Lexer::T_SELECT);
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

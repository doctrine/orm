<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

require_once __DIR__ . '/../../TestInit.php';

class CustomFunctionsTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testCustomFunctionDefinedWithCallback()
    {
        $user = new CmsUser();
        $user->name = 'Bob';
        $user->username = 'Dylan';
        $this->_em->persist($user);
        $this->_em->flush();

        // Instead of defining the function with the class name, we use a callback
        $this->_em->getConfiguration()->addCustomStringFunction('FOO', function($funcName) {
            return new NoOp($funcName);
        });
        $this->_em->getConfiguration()->addCustomNumericFunction('BAR', function($funcName) {
            return new NoOp($funcName);
        });

        $query = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u'
            . ' WHERE FOO(u.name) = \'Bob\''
            . ' AND BAR(1) = 1');

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertSame($user, $users[0]);
    }
}

class NoOp extends FunctionNode
{
    /**
     * @var PathExpression
     */
    private $field;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        return $this->field->dispatch($sqlWalker);
    }
}


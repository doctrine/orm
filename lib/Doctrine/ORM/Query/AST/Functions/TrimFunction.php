<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")"
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class TrimFunction extends FunctionNode
{
    /**
     * @var boolean
     */
    public $leading;

    /**
     * @var boolean
     */
    public $trailing;

    /**
     * @var boolean
     */
    public $both;

    /**
     * @var boolean
     */
    public $trimChar = false;

    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $stringPrimary;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $stringPrimary  = $sqlWalker->walkStringPrimary($this->stringPrimary);
        $platform       = $sqlWalker->getConnection()->getDatabasePlatform();
        $trimMode       = $this->getTrimMode();
        $trimChar       = ($this->trimChar !== false)
            ? $sqlWalker->getConnection()->quote($this->trimChar)
            : false;

        return $platform->getTrimExpression($stringPrimary, $trimMode, $trimChar);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->parseTrimMode($parser);

        if ($lexer->isNextToken(Lexer::T_STRING)) {
            $parser->match(Lexer::T_STRING);

            $this->trimChar = $lexer->token['value'];
        }

        if ($this->leading || $this->trailing || $this->both || $this->trimChar) {
            $parser->match(Lexer::T_FROM);
        }

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @return integer
     */
    private function getTrimMode()
    {
        if ($this->leading) {
            return AbstractPlatform::TRIM_LEADING;
        }

        if ($this->trailing) {
            return AbstractPlatform::TRIM_TRAILING;
        }

        if ($this->both) {
            return AbstractPlatform::TRIM_BOTH;
        }

        return AbstractPlatform::TRIM_UNSPECIFIED;
    }

    /**
     * @param \Doctrine\ORM\Query\Parser $parser
     *
     * @return void
     */
    private function parseTrimMode(Parser $parser)
    {
        $lexer = $parser->getLexer();
        $value = $lexer->lookahead['value'];

        if (strcasecmp('leading', $value) === 0) {
            $parser->match(Lexer::T_LEADING);

            $this->leading = true;

            return;
        }

        if (strcasecmp('trailing', $value) === 0) {
            $parser->match(Lexer::T_TRAILING);

            $this->trailing = true;

            return;
        }

        if (strcasecmp('both', $value) === 0) {
            $parser->match(Lexer::T_BOTH);

            $this->both = true;

            return;
        }
    }
}

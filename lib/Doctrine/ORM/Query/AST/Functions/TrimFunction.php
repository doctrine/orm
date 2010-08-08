<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")"
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class TrimFunction extends FunctionNode
{
    public $leading;
    public $trailing;
    public $both;
    public $trimChar = false;
    public $stringPrimary;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $pos = AbstractPlatform::TRIM_UNSPECIFIED;
        if ($this->leading) {
            $pos = AbstractPlatform::TRIM_LEADING;
        } else if ($this->trailing) {
            $pos = AbstractPlatform::TRIM_TRAILING;
        } else if ($this->both) {
            $pos = AbstractPlatform::TRIM_BOTH;
        }

        return $sqlWalker->getConnection()->getDatabasePlatform()->getTrimExpression(
            $sqlWalker->walkStringPrimary($this->stringPrimary),
            $pos,
            ($this->trimChar != false) ? $sqlWalker->getConnection()->quote($this->trimChar) : false
        );
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        if (strcasecmp('leading', $lexer->lookahead['value']) === 0) {
            $parser->match(Lexer::T_LEADING);
            $this->leading = true;
        } else if (strcasecmp('trailing', $lexer->lookahead['value']) === 0) {
            $parser->match(Lexer::T_TRAILING);
            $this->trailing = true;
        } else if (strcasecmp('both', $lexer->lookahead['value']) === 0) {
            $parser->match(Lexer::T_BOTH);
            $this->both = true;
        }

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

}

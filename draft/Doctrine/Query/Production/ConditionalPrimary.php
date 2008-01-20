<?php
/*
 *  $Id$
 *
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * ConditionalPrimary = SimpleConditionalExpression | "(" ConditionalExpression ")"
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_ConditionalPrimary extends Doctrine_Query_Production
{
    private function _isConditionalExpression()
    {
        $token = $this->_parser->lookahead;
        $parenthesis = 0;

        if ($token['value'] === '(') {
            $parenthesis++;
        }

        while ($parenthesis > 0) {
            $token = $this->_parser->getScanner()->peek();

            if ($token['value'] === '(') {
                $parenthesis++;
            } elseif ($token['value'] === ')') {
                $parenthesis--;
            } else {
                switch ($token['type']) {
                    case Doctrine_Query_Token::T_NOT:
                    case Doctrine_Query_Token::T_AND:
                    case Doctrine_Query_Token::T_OR:
                    case Doctrine_Query_Token::T_BETWEEN:
                    case Doctrine_Query_Token::T_LIKE:
                    case Doctrine_Query_Token::T_IN:
                    case Doctrine_Query_Token::T_IS:
                    case Doctrine_Query_Token::T_EXISTS:
                        return true;

                    case Doctrine_Query_Token::T_NONE:
                        switch ($token['value']) {
                            case '=':
                            case '<':
                            case '>':
                                return true;
                        }
                    break;
                }
            }
        }

        return false;
    }

    public function execute(array $params = array())
    {
        if ($this->_isConditionalExpression()) {
            $this->_parser->match('(');
            $this->ConditionalExpression();
            $this->_parser->match(')');
        } else {
            $this->SimpleConditionalExpression();
        }
    }
}

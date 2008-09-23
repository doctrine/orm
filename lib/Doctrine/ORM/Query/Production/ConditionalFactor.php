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
 * ConditionalFactor = ["NOT"] ConditionalPrimary
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_ConditionalFactor extends Doctrine_Query_Production
{
    protected $_conditionalPrimary;


    public function syntax($paramHolder)
    {
        // ConditionalFactor = ["NOT"] ConditionalPrimary
        $notFactor = false;

        if ($this->_isNextToken(Doctrine_Query_Token::T_NOT)) {
            $this->_parser->match(Doctrine_Query_Token::T_NOT);
            $notFactor = true;
        }

        $this->_conditionalPrimary = $this->AST('ConditionalPrimary', $paramHolder);

        // Optimize depth instances in AST
        if ( ! $notFactor) {
            return $this->_conditionalPrimary;
        }
    }


    public function buildSql()
    {
        // Do not need to check $notFactor. It'll be always present if we have this instance.
        return 'NOT ' . $this->_conditionalPrimary->buildSql();
    }


    /* Getters */
    public function getConditionalPrimary()
    {
        return $this->_conditionalPrimary;
    }
}

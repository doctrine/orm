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
 * This class represents the AbstractSchemaName production in DQL grammar.
 *
 * <code>
 * AbstractSchemaName = identifier
 * </code>
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_AbstractSchemaName extends Doctrine_Query_Production
{
    /**
     * Executes the AbstractSchemaName production.
     *
     * <code>
     * AbstractSchemaName = identifier
     * </code>
     *
     * @param array $params This production does not take any parameters.
     * @return Doctrine_Table|null the table object corresponding the identifier
     * name
     */
    public function execute(array $params = array())
    {
        $table = null;
        $token = $this->_parser->lookahead;

        if ($token['type'] === Doctrine_Query_Token::T_IDENTIFIER) {

            $table = $this->_parser->getConnection()->getTable($token['value']);

            if ($table === null) {
                $this->_parser->logError('Table named "' . $name . '" does not exist.');
            }

            $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        } else {
            $this->_parser->logError('Identifier expected');
        }

        return $table;
    }
}

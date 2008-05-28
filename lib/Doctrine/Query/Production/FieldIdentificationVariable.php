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
 * FieldIdentificationVariable = identifier
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_FieldIdentificationVariable extends Doctrine_Query_Production
{
    protected $_fieldAlias;

    protected $_columnAlias;


    public function syntax($paramHolder)
    {
        // FieldIdentificationVariable = identifier
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        $this->_fieldAlias = $this->_parser->token['value'];
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();

        if ($parserResult->hasQueryField($this->_fieldAlias)) {
            // We should throw semantical error if there's already a component for this alias
            $fieldName = $parserResult->getQueryField($this->_fieldAlias);

            $message  = "Cannot re-declare field alias '{$this->_fieldAlias}'"
                      . "for '".$paramHolder->get('fieldName')."'.";

            $this->_parser->semanticalError($message);
        }

        // Now we map it in queryComponent
        $componentAlias = Doctrine_Query_Production::DEFAULT_QUERYCOMPONENT;
        $queryComponent = $parserResult->getQueryComponent($componentAlias);

        $idx = count($queryComponent['scalar']);
        $queryComponent['scalar'][$idx] = $this->_fieldAlias;
        $parserResult->setQueryComponent($componentAlias, $queryComponent);

        // And also in field aliases
        $parserResult->setQueryField($queryComponent['scalar'][$idx], $idx);

        // Build the column alias
        $this->_columnAlias = $parserResult->getTableAliasFromComponentAlias($componentAlias)
                            . Doctrine_Query_Production::SQLALIAS_SEPARATOR . $idx;
    }
}

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
 * IndexBy = "INDEX" "BY" identifier
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Production_IndexBy extends Doctrine_Query_Production
{
    protected $_componentAlias;

    protected $_fieldName;


    public function syntax($paramHolder)
    {
        $this->_componentAlias = $paramHolder->get('componentAlias');

        $this->_parser->match(Doctrine_Query_Token::T_INDEX);
        $this->_parser->match(Doctrine_Query_Token::T_BY);
        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);

        $this->_fieldName = $this->_parser->token['value'];
    }


    public function semantical($paramHolder)
    {
        $parserResult = $this->_parser->getParserResult();

        //echo "Component alias: " . $this->_componentAlias . "\n";
        //echo "Has query component: " . ($parserResult->hasQueryComponent($this->_componentAlias) ? "TRUE" : "FALSE") . "\n";
        //$qc = $parserResult->getQueryComponents();
        //$qc = array_keys($qc);
        //echo "Query Components: " . var_export($qc, true) . "\n";

        try {
            $queryComponent = $parserResult->getQueryComponent($this->_componentAlias);
            $classMetadata = $queryComponent['metadata'];
        } catch (Doctrine_Exception $e) {
            $this->_parser->semanticalError($e->getMessage());

            return;
        }

        if ($classMetadata instanceof Doctrine_ClassMetadata && ! $classMetadata->hasField($this->_fieldName)) {
            $this->_parser->semanticalError(
                "Cannot use key mapping. Field '" . $this->_fieldName . "' " . 
                "does not exist in component '" . $classMetadata->getClassName() . "'.",
                $this->_parser->token
            );
        }

        // The INDEXBY field must be either the (primary && not part of composite pk) || (unique && notnull)
        $columnMapping = $classMetadata->getFieldMapping($this->_fieldName);

        if ( ! $classMetadata->isIdentifier($this->_fieldName) && ! $classMetadata->isUniqueField($this->_fieldName) && ! $classMetadata->isNotNull($this->_fieldName)) {
            $this->_parser->semanticalError(
                "Field '" . $this->_fieldName . "' of component  '" . $classMetadata->getClassName() .
                "' must be unique and notnull to be used as index.",
                $this->_parser->token
            );
        }

        if ($classMetadata->isIdentifier($this->_fieldName) && $classMetadata->isIdentifierComposite()) {
            $this->_parser->semanticalError(
                "Field '" . $this->_fieldName . "' of component  '" . $classMetadata->getClassName() .
                "' must be primary and not part of a composite primary key to be used as index.",
                $this->_parser->token
            );
        }


        $queryComponent['map'] = $this->_fieldName;
        $parserResult->setQueryComponent($this->_componentAlias, $queryComponent);
    }


    public function buildSql()
    {
        return '';
    }
    
    /**
     * Visitor support
     *
     * @param object $visitor
     */
    public function accept($visitor)
    {
        $visitor->visitIndexBy($this);
    }
    
    /* Getters */
    
    public function getComponentAlias()
    {
        return $this->_componentAlias;
    }
    
    public function getFieldName()
    {
        return $this->_fieldName;
    }
}

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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\AST\Functions;

/**
 * "SIZE" "(" CollectionValuedPathExpression ")"
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class SizeFunction extends FunctionNode
{
    public $collectionPathExpression;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $dqlAlias = $this->collectionPathExpression->identificationVariable;
        $qComp = $sqlWalker->getQueryComponent($dqlAlias);
        $parts = $this->collectionPathExpression->parts;
        
        $assoc = $qComp['metadata']->associationMappings[$parts[0]];
        
        if ($assoc->isOneToMany()) {
            $targetClass = $sqlWalker->getEntityManager()->getClassMetadata($assoc->targetEntityName);
            $targetAssoc = $targetClass->associationMappings[$assoc->mappedByFieldName];
            
            $targetTableAlias = $sqlWalker->getSqlTableAlias($targetClass->primaryTable['name']);
            $sourceTableAlias = $sqlWalker->getSqlTableAlias($qComp['metadata']->primaryTable['name'], $dqlAlias);
            
            $sql = "(SELECT COUNT($targetTableAlias."
                    . implode(", $targetTableAlias.", $targetAssoc->targetToSourceKeyColumns)
                    . ') FROM ' . $targetClass->primaryTable['name'] . ' ' . $targetTableAlias;
             
            $whereSql = '';
            foreach ($targetAssoc->targetToSourceKeyColumns as $targetKeyColumn => $sourceKeyColumn) {
                if ($whereSql == '') $whereSql = ' WHERE '; else $whereSql .= ' AND ';
                $whereSql .= $targetTableAlias . '.' . $sourceKeyColumn . ' = ' . $sourceTableAlias . '.' . $targetKeyColumn;
            }
            
            $sql .= $whereSql . ')';
        }
        
        return $sql;
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        
        $parser->match($lexer->lookahead['value']);
        $parser->match('(');
        
        $this->collectionPathExpression = $parser->CollectionValuedPathExpression();
        
        $parser->match(')');
    }
}


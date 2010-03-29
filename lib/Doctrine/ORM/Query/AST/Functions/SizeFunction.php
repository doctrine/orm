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

use Doctrine\ORM\Query\Lexer;

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
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SizeFunction extends FunctionNode
{
    public $collectionPathExpression;

    /**
     * @override
     * @todo If the collection being counted is already joined, the SQL can be simpler (more efficient).
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $dqlAlias = $this->collectionPathExpression->identificationVariable;
        $parts = $this->collectionPathExpression->parts;
        $assocField = array_pop($parts);

        $qComp = $sqlWalker->getQueryComponent(implode('.', array_merge((array) $dqlAlias, $parts)));
        $assoc = $qComp['metadata']->associationMappings[$assocField];
        $sql = '';
        
        if ($assoc->isOneToMany()) {
            $targetClass = $sqlWalker->getEntityManager()->getClassMetadata($assoc->targetEntityName);
            $targetAssoc = $targetClass->associationMappings[$assoc->mappedBy];
            
            $targetTableAlias = $sqlWalker->getSqlTableAlias($targetClass->table['name']);
            $sourceTableAlias = $sqlWalker->getSqlTableAlias($qComp['metadata']->table['name'], $dqlAlias);
            
            $whereSql = '';

            foreach ($targetAssoc->targetToSourceKeyColumns as $targetKeyColumn => $sourceKeyColumn) {
                $whereSql .= (($whereSql == '') ? ' WHERE ' : ' AND ')
                           . $targetTableAlias . '.' . $sourceKeyColumn . ' = ' 
                           . $sourceTableAlias . '.' . $targetKeyColumn;
            }

            $tableName = $targetClass->table['name'];
        } else if ($assoc->isManyToMany()) {
            $targetTableAlias = $sqlWalker->getSqlTableAlias($assoc->joinTable['name']);
            $sourceTableAlias = $sqlWalker->getSqlTableAlias($qComp['metadata']->table['name'], $dqlAlias);
            
            $whereSql = '';

            foreach ($assoc->relationToSourceKeyColumns as $targetKeyColumn => $sourceKeyColumn) {
                $whereSql .= (($whereSql == '') ? ' WHERE ' : ' AND ')
                           . $targetTableAlias . '.' . $targetKeyColumn . ' = ' 
                           . $sourceTableAlias . '.' . $sourceKeyColumn;
            }

            $tableName = $assoc->joinTable['name'];
        }
        
        return '(SELECT COUNT(*) FROM ' . $tableName . ' ' . $targetTableAlias . $whereSql . ')';
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $lexer = $parser->getLexer();
        
        $parser->match(Lexer::T_SIZE);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        
        $this->collectionPathExpression = $parser->CollectionValuedPathExpression();
        
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}


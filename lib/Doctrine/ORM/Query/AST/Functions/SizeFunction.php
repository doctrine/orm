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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Query\Lexer;

/**
 * "SIZE" "(" CollectionValuedPathExpression ")"
 *
 * 
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SizeFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    public $collectionPathExpression;

    /**
     * @override
     * @inheritdoc
     * @todo If the collection being counted is already joined, the SQL can be simpler (more efficient).
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        $platform          = $sqlWalker->getEntityManager()->getConnection()->getDatabasePlatform();
        $dqlAlias          = $this->collectionPathExpression->identificationVariable;
        $assocField        = $this->collectionPathExpression->field;
        $sql               = 'SELECT COUNT(*) FROM ';
        $qComp             = $sqlWalker->getQueryComponent($dqlAlias);
        $class             = $qComp['metadata'];
        $association       = $class->getProperty($assocField);
        $targetClass       = $sqlWalker->getEntityManager()->getClassMetadata($association->getTargetEntity());
        $owningAssociation = $association->isOwningSide()
            ? $association
            : $targetClass->getProperty($association->getMappedBy())
        ;

        if ($association instanceof OneToManyAssociationMetadata) {
            $targetTableName    = $targetClass->table->getQuotedQualifiedName($platform);
            $targetTableAlias   = $sqlWalker->getSQLTableAlias($targetClass->getTableName());
            $sourceTableAlias   = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

            $sql .= $targetTableName . ' ' . $targetTableAlias . ' WHERE ';

            $owningAssociation = $targetClass->getProperty($association->getMappedBy());
            $first             = true;

            foreach ($owningAssociation->getJoinColumns() as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';

                $sql .= sprintf('%s.%s = %s.%s',
                    $targetTableAlias,
                    $platform->quoteIdentifier($joinColumn->getColumnName()),
                    $sourceTableAlias,
                    $platform->quoteIdentifier($joinColumn->getReferencedColumnName())
                );
            }
        } else { // many-to-many
            $joinTable     = $owningAssociation->getJoinTable();
            $joinTableName = $joinTable->getQuotedQualifiedName($platform);

            // SQL table aliases
            $joinTableAlias   = $sqlWalker->getSQLTableAlias($joinTable->getName());
            $sourceTableAlias = $sqlWalker->getSQLTableAlias($class->getTableName(), $dqlAlias);

            // Quote in case source table alias matches class table name (happens in an UPDATE statement)
            if ($sourceTableAlias === $class->getTableName()) {
                $sourceTableAlias = $platform->quoteIdentifier($sourceTableAlias);
            }

            // join to target table
            $sql .= $joinTableName . ' ' . $joinTableAlias . ' WHERE ';

            $joinColumns = $association->isOwningSide()
                ? $joinTable->getJoinColumns()
                : $joinTable->getInverseJoinColumns()
            ;

            $first = true;

            foreach ($joinColumns as $joinColumn) {
                if ($first) $first = false; else $sql .= ' AND ';

                $sql .= sprintf('%s.%s = %s.%s',
                    $joinTableAlias,
                    $platform->quoteIdentifier($joinColumn->getColumnName()),
                    $sourceTableAlias,
                    $platform->quoteIdentifier($joinColumn->getReferencedColumnName())
                );
            }
        }

        return '(' . $sql . ')';
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->collectionPathExpression = $parser->CollectionValuedPathExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}

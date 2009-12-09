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

namespace Doctrine\ORM\Persisters;

use Doctrine\DBAL\Types\Type;

/**
 * Persister for entities that participate in a hierarchy mapped with the
 * SINGLE_TABLE strategy.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.doctrine-project.org
 * @since       2.0
 */
class SingleTablePersister extends StandardEntityPersister
{
    /** @override */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        parent::_prepareData($entity, $result, $isInsert);
        // Populate the discriminator column
        if ($isInsert) {
            $discColumn = $this->_class->getQuotedDiscriminatorColumnName($this->_platform);
            $result[$this->_class->getQuotedTableName($this->_platform)][$discColumn] =
                    $this->_class->discriminatorValue;
        }
    }
    
    /** @override */
    protected function _getSelectColumnList()
    {
        $setResultColumnNames = empty($this->_resultColumnNames);
        $columnList = parent::_getSelectColumnList();
        // Append discriminator column
        $columnList .= ', ' . $this->_class->getQuotedDiscriminatorColumnName($this->_platform);
        
        if ($setResultColumnNames) {
            $resultColumnName = $this->_platform->getSqlResultCasing($this->_class->discriminatorColumn['name']);
            $this->_resultColumnNames[$resultColumnName] = $this->_class->discriminatorColumn['name'];
        }
        
        ///$tableAlias = $this->_class->getQuotedTableName($this->_platform);
        foreach ($this->_class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            // Append subclass columns
            foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                if ( ! isset($mapping['inherited'])) {
                    $columnList .= ', ' . $subClass->getQuotedColumnName($fieldName, $this->_platform);
                    if ($setResultColumnNames) {
                        $resultColumnName = $this->_platform->getSqlResultCasing($mapping['columnName']);
                        $this->_resultColumnNames[$resultColumnName] = $mapping['columnName'];
                    }
                }
            }
            
            // Append subclass foreign keys
            foreach ($subClass->associationMappings as $assoc) {
                if ($assoc->isOwningSide && $assoc->isOneToOne() && ! isset($subClass->inheritedAssociationFields[$assoc->sourceFieldName])) {
                    foreach ($assoc->targetToSourceKeyColumns as $srcColumn) {
                        $columnList .= ', ' /*. $tableAlias . '.'*/ . $assoc->getQuotedJoinColumnName($srcColumn, $this->_platform);
                        if ($setResultColumnNames) {
                            $resultColumnName = $this->_platform->getSqlResultCasing($srcColumn);
                            $this->_resultColumnNames[$resultColumnName] = $srcColumn;
                        }
                    }
                }
            }
        }

        return $columnList;
    }
    
    /** @override */
    protected function _processSqlResult(array $sqlResult)
    {
        return $this->_processSqlResultInheritanceAware($sqlResult);
    }
}
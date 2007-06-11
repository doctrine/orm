<?php
/*
 *  $Id: Self.php 1434 2007-05-22 15:57:17Z zYne $
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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Relation_Association');
/**
 * Doctrine_Relation_Association_Self
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1434 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Relation_Nest extends Doctrine_Relation_Association
{
    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count, $context = 'record')
    {
        switch ($context) {
            case 'record':
                $sub    = 'SELECT '.$this->definition['foreign'] 
                        . ' FROM '.$this->definition['refTable']->getTableName()
                        . ' WHERE '.$this->definition['local']
                        . ' = ?';

                $sub2   = 'SELECT '.$this->definition['local']
                        . ' FROM '.$this->definition['refTable']->getTableName()
                        . ' WHERE '.$this->definition['foreign']
                        . ' = ?';

                $dql  = 'FROM ' . $this->definition['table']->getComponentName()
                      . '.' . $this->definition['refTable']->getComponentName()
                      . ' WHERE ' . $this->definition['table']->getComponentName()
                      . '.' . $this->definition['table']->getIdentifier() 
                      . ' IN (' . $sub . ')'
                      . ' || ' . $this->definition['table']->getComponentName() 
                      . '.' . $this->definition['table']->getIdentifier() 
                      . ' IN (' . $sub2 . ')';
                break;
            case 'collection':
                $sub  = substr(str_repeat('?, ', $count),0,-2);
                $dql  = 'FROM '.$this->definition['refTable']->getComponentName()
                      . '.' . $this->definition['table']->getComponentName()
                      . ' WHERE '.$this->definition['refTable']->getComponentName()
                      . '.' . $this->definition['local'] . ' IN (' . $sub . ')';
        };

        return $dql;
    }
    /**
    public function fetchRelatedFor(Doctrine_Record $record)
    {
        $id = $record->getIncremented();

        if (empty($id) || ! $this->definition['table']->getAttribute(Doctrine::ATTR_LOAD_REFERENCES)) {
            return new Doctrine_Collection($this->getTable());
        } else {
            $q = new Doctrine_Query();
            
            $c  = $this->getTable()->getComponentName();
            $a  = substr($c, 0, 1);
            $c2 = $this->getAssociationTable()->getComponentName();
            $a2 = substr($c2, 0, 1);

            $q->from($c)
              ->innerJoin($c . '.' . $c2)

            $sub = 'SELECT ' . $this->getForeign() 
                 . ' FROM '  . $c2
                 . ' WHERE ' . $this->getLocal() 
                 . ' = ?';
        }
    }
    */

    public function fetchRelatedFor(Doctrine_Record $record)
    {
        $id = $record->getIncremented();


        if (empty($id) || ! $this->definition['table']->getAttribute(Doctrine::ATTR_LOAD_REFERENCES)) {
            return new Doctrine_Collection($this->getTable());
        } else {
            $q = new Doctrine_RawSql();

            $assocTable = $this->getAssociationFactory()->getTableName();
            $tableName  = $record->getTable()->getTableName();
            $identifier = $record->getTable()->getIdentifier();
    
            $sub = 'SELECT ' . $this->getForeign()
                 . ' FROM ' . $assocTable 
                 . ' WHERE ' . $this->getLocal() 
                 . ' = ?';

            $condition[] = $tableName . '.' . $identifier . ' IN (' . $sub . ')';
            $joinCondition[] = $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getForeign();

            if ($this->definition['equal']) {
                $sub2   = 'SELECT ' . $this->getLocal()
                        . ' FROM '  . $assocTable
                        . ' WHERE ' . $this->getForeign()
                        . ' = ?';

                $condition[] = $tableName . '.' . $identifier . ' IN (' . $sub2 . ')';
                $joinCondition[] = $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getLocal();
            }
            $q->select('{'.$tableName.'.*}, {'.$assocTable.'.*}')
              ->from($tableName . ' INNER JOIN ' . $assocTable . ' ON ' . implode(' OR ', $joinCondition))
              ->where(implode(' OR ', $condition));
            $q->addComponent($tableName,  $record->getTable()->getComponentName());
            $q->addComponent($assocTable, $record->getTable()->getComponentName(). '.' . $this->getAssociationFactory()->getComponentName());

            $params = ($this->definition['equal']) ? array($id, $id) : array($id);

            return $q->execute($params);
        }
    }
}

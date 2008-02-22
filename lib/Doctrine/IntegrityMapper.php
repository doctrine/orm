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
 * Doctrine_IntegrityMapper
 *
 * @package     Doctrine
 * @subpackage  IntegrityMapper
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_IntegrityMapper 
{
    /**
     * processDeleteIntegrity 
     * 
     * @param Doctrine_Record $record 
     * @return void
     */
    public function processDeleteIntegrity(Doctrine_Record $record)
    {
        $coll = $this->buildIntegrityRelationQuery($record);
        
        $this->invokeIntegrityActions($record);
    }


    /**
     * invokeIntegrityActions 
     * 
     * @param Doctrine_Record $record 
     * @return void
     */
    public function invokeIntegrityActions(Doctrine_Record $record)
    {
        $deleteActions = Doctrine_Manager::getInstance()
                         ->getDeleteActions($record->getTable()->getComponentName());
                         
        foreach ($record->getTable()->getRelations() as $relation) {
            $componentName = $relation->getTable()->getComponentName();
            
            foreach($record->get($relation->getAlias()) as $coll) {
                if ( ! ($coll instanceof Doctrine_Collection)) {
                    $coll = array($coll);
                }
                foreach ($coll as $record) {
                    $this->invokeIntegrityActions($record);

                    if (isset($deleteActions[$componentName])) {
                        if ($deleteActions[$componentName] === 'SET NULL') {
                            $record->set($relation->getForeign(), null);
                        } elseif ($deleteActions[$componentName] === 'CASCADE') {
                            $this->conn->transaction->addDelete($record);
                        }
                    }

                }
            }
        }
    }

    /**
     * buildIntegrityRelationQuery 
     * 
     * @param Doctrine_Record $record 
     * @return array The result
     */
    public function buildIntegrityRelationQuery(Doctrine_Record $record)
    {
        $q = new Doctrine_Query();
        
        $aliases = array();
        $indexes = array();

        $root = $record->getTable()->getComponentName();
        $rootAlias = strtolower(substr($root, 0, 1));
        $aliases[$rootAlias] = $root;

        foreach ((array) $record->getTable()->getIdentifier() as $id) {
            $field = $rootAlias . '.' . $id;
            $cond[]   = $field . ' = ?';
            $fields[] = $field;
            $params   = $record->get($id);
        }
        $fields = implode(', ', $fields);
        $components[] = $root;
        $this->buildIntegrityRelations($record->getTable(), $aliases, $fields, $indexes, $components);

        $q->select($fields)->from($root. ' ' . $rootAlias);

        foreach ($aliases as $alias => $name) {
            $q->leftJoin($rootAlias . '.' . $name . ' ' . $alias);
        }
        $q->where(implode(' AND ', $cond));

        return $q->execute(array($params));
    }

    /**
     * buildIntegrityRelations 
     * 
     * @param Doctrine_Table $table 
     * @param mixed $aliases 
     * @param mixed $fields 
     * @param mixed $indexes 
     * @param mixed $components 
     * @return void
     */
    public function buildIntegrityRelations(Doctrine_Table $table, &$aliases, &$fields, &$indexes, &$components)
    {
        $deleteActions = Doctrine_Manager::getInstance()
                         ->getDeleteActions($table->getComponentName());

        foreach ($table->getRelations() as $relation) {
            $componentName = $relation->getTable()->getComponentName();
            if (in_array($componentName, $components)) {
                continue;
            }
            $components[] = $componentName;

            $alias = strtolower(substr($relation->getAlias(), 0, 1));

            if ( ! isset($indexes[$alias])) {
                $indexes[$alias] = 1;
            }

            if (isset($deleteActions[$componentName])) {
                if (isset($aliases[$alias])) {
                    $alias = $alias . ++$indexes[$alias];
                }
                $aliases[$alias] = $relation->getAlias();

                if ($deleteActions[$componentName] === 'SET NULL') {
                    if ($relation instanceof Doctrine_Relation_ForeignKey) {
                        foreach ((array) $relation->getForeign() as $foreign) {
                            $fields .= ', ' . $alias . '.' . $foreign;
                        }
                    } elseif ($relation instanceof Doctrine_Relation_LocalKey) {
                        foreach ((array) $relation->getLocal() as $foreign) {
                            $fields .= ', ' . $alias . '.' . $foreign;
                        }
                    }
                }
                foreach ((array) $relation->getTable()->getIdentifier() as $id) {
                    $fields .= ', ' . $alias . '.' . $id;
                }
                if ($deleteActions[$componentName] === 'CASCADE') {
                    $this->buildIntegrityRelations($relation->getTable(), $aliases, $fields, $indexes, $components);
                }
            }
        }
    }
}

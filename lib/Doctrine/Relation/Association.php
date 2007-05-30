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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Relation');
/**
 * Doctrine_Relation_Association    this class takes care of association mapping
 *                         (= many-to-many relationships, where the relationship is handled with an additional relational table
 *                         which holds 2 foreign keys)
 *
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Relation_Association extends Doctrine_Relation
{
    /**
     * @return Doctrine_Table
     */
    public function getAssociationFactory()
    {
        return $this->definition['refTable'];
    }
    public function getAssociationTable()
    {
    	return $this->definition['refTable'];
    }
    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count, $context = 'record')
    {
    	$component = $this->definition['refTable']->getComponentName();
        switch ($context) {
            case "record":
                $sub    = 'SQL:SELECT ' . $this->definition['foreign'].
                          ' FROM '  . $this->definition['refTable']->getTableName().
                          ' WHERE ' . $this->definition['local'] .
                          ' IN ('   . substr(str_repeat("?, ", $count),0,-2) .
                          ')';

                $dql  = 'FROM ' . $this->getTable()->getComponentName();
                $dql .= '.' . $component;
                $dql .= ' WHERE ' . $this->getTable()->getComponentName()
                      . '.' . $this->getTable()->getIdentifier() . ' IN (' . $sub . ')';
                break;
            case "collection":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = 'FROM ' . $component . '.' . $this->getTable()->getComponentName();
                $dql .= ' WHERE ' . $component . '.' . $this->definition['local'] . ' IN (' . $sub . ')';
                break;
        }

        return $dql;
    }
    /**
     * fetchRelatedFor
     *
     * fetches a component related to given record
     *
     * @param Doctrine_Record $record
     * @return Doctrine_Record|Doctrine_Collection
     */
    public function fetchRelatedFor(Doctrine_Record $record)
    {
        $id = $record->getIncremented();
        if (empty($id) || ! $this->definition['table']->getAttribute(Doctrine::ATTR_LOAD_REFERENCES)) {
            $coll = new Doctrine_Collection($this->getTable());
        } else {
            $coll = Doctrine_Query::create()->parseQuery($this->getRelationDql(1))->execute(array($id));
        }
        return $coll;
    }
}

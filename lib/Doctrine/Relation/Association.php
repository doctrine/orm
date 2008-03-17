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
Doctrine::autoload('Doctrine_Relation');
/**
 * Doctrine_Relation_Association
 *
 * This class is reponsible for lazy-loading the related objects in a many-to-many relation.
 *
 * @package     Doctrine
 * @subpackage  Relation
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
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
    
    public function getAssociationClassName()
    {
        return $this->definition['refClass'];
    }
    

    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count, $context = 'record')
    {
        //$table = $this->definition['refTable'];
        $assocRelationName = $this->definition['refClass'];
        
        $relatedClassName = $this->_foreignMapper->getComponentName();
        
        switch ($context) {
            case "record":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = "FROM $relatedClassName";
                $dql .= " INNER JOIN $relatedClassName.$assocRelationName";
                //$dql .= " ON $relatedClassName.$assocRelationName.$inverseJoinColumn = $relatedClassName.$relatedClassIdentifier";
                $dql .= " WHERE $relatedClassName.$assocRelationName.{$this->definition['local']} IN ($sub)";
                break;
            case "collection":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = "FROM $assocRelationName INNER JOIN $assocRelationName.$relatedClassName";
                //$dql .= " ON $relatedClassName.$assocRelationName.$inverseJoinColumn = $relatedClassName.$relatedClassIdentifier";
                $dql .= " WHERE $assocRelationName.{$this->definition['local']} IN ($sub)";
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
        //var_dump($id);
        //echo "<br /><br />";
        if (empty($id) || ! $this->_foreignMapper->getClassMetadata()->getAttribute(Doctrine::ATTR_LOAD_REFERENCES)) {
            //echo "here" . $this->_foreignMapper->getAttribute(Doctrine::ATTR_LOAD_REFERENCES);
            $coll = new Doctrine_Collection($this->getForeignComponentName());
        } else {
            $query = Doctrine_Query::create()->parseQuery($this->getRelationDql(1));
            //echo $query->getDql() . "<br />";
            //echo $query->getSql() . "<br />";
            //echo "<br /><br />";
            $coll = Doctrine_Query::create()->query($this->getRelationDql(1), array($id));
        }
        $coll->setReference($record, $this);
        return $coll;
    }
}
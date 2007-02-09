<?php
/*
 *    $Id$
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
/**
 * Doctrine_Node_NestedSet
 *
 * @package         Doctrine
 * @license         http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category        Object Relational Mapping
 * @link                www.phpdoctrine.com
 * @since             1.0
 * @version         $Revision$
 * @author            Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Node_NestedSet extends Doctrine_Node implements Doctrine_Node_Interface
{
    /**
     * test if node has previous sibling
     *
     * @return bool            
     */
    public function hasPrevSibling()
    {
        return $this->isValidNode($this->getPrevSibling());        
    }

    /**
     * test if node has next sibling
     *
     * @return bool            
     */ 
    public function hasNextSibling()
    {
        return $this->isValidNode($this->getNextSibling());        
    }

    /**
     * test if node has children
     *
     * @return bool            
     */
    public function hasChildren()
    {
        return (($this->getRightValue() - $this->getLeftValue() ) >1 );        
    }

    /**
     * test if node has parent
     *
     * @return bool            
     */
    public function hasParent()
    {
        return !$this->isRoot();
    }

    /**
     * gets record of prev sibling or empty record
     *
     * @return object     Doctrine_Record            
     */
    public function getPrevSibling()
    {
        $q = $this->record->getTable()->createQuery();
        $result = $q->where('rgt = ?', $this->getLeftValue() - 1)->execute()->getFirst();

        if(!$result)
             $result = $this->record->getTable()->create();
        
        return $result;    
    }

    /**
     * gets record of next sibling or empty record
     *
     * @return object     Doctrine_Record            
     */
    public function getNextSibling()
    {
        $q = $this->record->getTable()->createQuery();
        $result = $q->where('lft = ?', $this->getRightValue() + 1)->execute()->getFirst();

        if(!$result)
             $result = $this->record->getTable()->create();
        
        return $result;
    }

    /**
     * gets siblings for node
     *
     * @return array     array of sibling Doctrine_Record objects            
     */
    public function getSiblings($includeNode = false)
    {
        $parent = $this->getParent();
        $siblings = array();
        if($parent->exists())
        {
            foreach($parent->getNode()->getChildren() as $child)
            {
                if($this->isEqualTo($child) && !$includeNode)
                    continue;
                    
                $siblings[] = $child;
            }            
        }
    
        return $siblings;
    }

    /**
     * gets record of first child or empty record
     *
     * @return object     Doctrine_Record            
     */
    public function getFirstChild()
    {
        $q = $this->record->getTable()->createQuery();
        $result = $q->where('lft = ?', $this->getLeftValue() + 1)->execute()->getFirst();
        
        if(!$result)
             $result = $this->record->getTable()->create();
        
        return $result;        
    }

    /**
     * gets record of last child or empty record
     *
     * @return object     Doctrine_Record            
     */
    public function getLastChild()
    {
        $q = $this->record->getTable()->createQuery();
        $result = $q->where('rgt = ?', $this->getRightValue() - 1)->execute()->getFirst();

        if(!$result)
             $result = $this->record->getTable()->create();
        
        return $result;     
    }

    /**
     * gets children for node (direct descendants only)
     *
     * @return array     array of sibling Doctrine_Record objects                
     */
    public function getChildren()
    { 
        return $this->getIterator('Pre', array('depth' => 1));
    }

    /**
     * gets descendants for node (direct descendants only)
     *
     * @return iterator     iterator to traverse descendants from node                
     */
    public function getDescendants()
    {
        return $this->getIterator();
    }

    /**
     * gets record of parent or empty record
     *
     * @return object     Doctrine_Record            
     */
    public function getParent()
    {
        $q = $this->record->getTable()->createQuery();

        $componentName = $this->record->getTable()->getComponentName();
        $parent =     $q->where("$componentName.lft < ? AND $componentName.rgt > ?", array($this->getLeftValue(), $this->getRightValue()))
                                    ->orderBy("$componentName.rgt asc")
                                    ->execute()
                                    ->getFirst();

        if(!$parent)
             $parent = $this->record->getTable()->create();
        
        return $parent;
    }

    /**
     * gets ancestors for node
     *
     * @return object     Doctrine_Collection                
     */
    public function getAncestors()
    {
        $q = $this->record->getTable()->createQuery();

        $componentName = $this->record->getTable()->getComponentName();
        $ancestors =    $q->where("$componentName.lft < ? AND $componentName.rgt > ?", array($this->getLeftValue(), $this->getRightValue()))
                                        ->orderBy("$componentName.lft asc")
                                        ->execute();

        return $ancestors;
    }

    /**
     * gets path to node from root, uses record::toString() method to get node names
     *
     * @param string     $seperator     path seperator
     * @param bool     $includeNode     whether or not to include node at end of path
     * @return string     string representation of path                
     */     
    public function getPath($seperator = ' > ', $includeRecord = false)
    {
        $path = array();
        $ancestors = $this->getAncestors();
        foreach($ancestors as $ancestor)
        {
            $path[] = $ancestor->__toString();
        }
        if($includeRecord)
            $path[] = $this->getRecord()->__toString();
        
        return implode($seperator, $path);
    }

    /**
     * gets number of children (direct descendants)
     *
     * @return int            
     */     
    public function getNumberChildren()
    {
        $count = 0;
        $children = $this->getChildren();
    
        while($children->next())
        {
            $count++;
        }
        return $count; 
    }

    /**
     * gets number of descendants (children and their children)
     *
     * @return int            
     */
    public function getNumberDescendants()
    {
        return ($this->getRightValue() - $this->getLeftValue() - 1) / 2;
    }

    /**
     * inserts node as parent of dest record
     *
     * @return bool            
     */
    public function insertAsParentOf(Doctrine_Record $dest)
    {
        // cannot insert a node that has already has a place within the tree
        if($this->isValidNode())
            return false;
        
        // cannot insert as parent of root
        if($dest->getNode()->isRoot())
            return false;

        $this->shiftRLValues($dest->getNode()->getLeftValue(), 1);
        $this->shiftRLValues($dest->getNode()->getRightValue() + 2, 1);
        
        $newLeft = $dest->getNode()->getLeftValue();
        $newRight = $dest->getNode()->getRightValue() + 2;
        $newRoot = $dest->getNode()->getRootValue();

        $this->insertNode($newLeft, $newRight, $newRoot);
        
        return true;
    }

    /**
     * inserts node as previous sibling of dest record
     *
     * @return bool            
     */
    public function insertAsPrevSiblingOf(Doctrine_Record $dest)
    {
        // cannot insert a node that has already has a place within the tree
        if($this->isValidNode())
            return false;

        $newLeft = $dest->getNode()->getLeftValue();
        $newRight = $dest->getNode()->getLeftValue() + 1;
        $newRoot = $dest->getNode()->getRootValue();
        
        $this->shiftRLValues($newLeft, 2, $newRoot);
        $this->insertNode($newLeft, $newRight, $newRoot);
        // update destination left/right values to prevent a refresh
        // $dest->getNode()->setLeftValue($dest->getNode()->getLeftValue() + 2);
        // $dest->getNode()->setRightValue($dest->getNode()->getRightValue() + 2);
                        
        return true;
    }

    /**
     * inserts node as next sibling of dest record
     *
     * @return bool            
     */    
    public function insertAsNextSiblingOf(Doctrine_Record $dest)
    {
        // cannot insert a node that has already has a place within the tree
        if($this->isValidNode())
            return false;

        $newLeft = $dest->getNode()->getRightValue() + 1;
        $newRight = $dest->getNode()->getRightValue() + 2;
        $newRoot = $dest->getNode()->getRootValue();

        $this->shiftRLValues($newLeft, 2, $newRoot);
        $this->insertNode($newLeft, $newRight, $newRoot);

        // update destination left/right values to prevent a refresh
        // no need, node not affected

        return true;    
    }

    /**
     * inserts node as first child of dest record
     *
     * @return bool            
     */
    public function insertAsFirstChildOf(Doctrine_Record $dest)
    {
        // cannot insert a node that has already has a place within the tree
        if($this->isValidNode())
            return false;

        $newLeft = $dest->getNode()->getLeftValue() + 1;
        $newRight = $dest->getNode()->getLeftValue() + 2;
        $newRoot = $dest->getNode()->getRootValue();

        $this->shiftRLValues($newLeft, 2, $newRoot);
        $this->insertNode($newLeft, $newRight, $newRoot);
        
        // update destination left/right values to prevent a refresh
        // $dest->getNode()->setRightValue($dest->getNode()->getRightValue() + 2);

        return true;
    }

    /**
     * inserts node as last child of dest record
     *
     * @return bool            
     */
    public function insertAsLastChildOf(Doctrine_Record $dest)
    {
        // cannot insert a node that has already has a place within the tree
        if($this->isValidNode())
            return false;

        $newLeft = $dest->getNode()->getRightValue();
        $newRight = $dest->getNode()->getRightValue() + 1;
        $newRoot = $dest->getNode()->getRootValue();

        $this->shiftRLValues($newLeft, 2, $newRoot);
        $this->insertNode($newLeft, $newRight, $newRoot);

        // update destination left/right values to prevent a refresh
        // $dest->getNode()->setRightValue($dest->getNode()->getRightValue() + 2);
        
        return true;
    }

    /**
     * moves node as prev sibling of dest record
     *        
     */     
    public function moveAsPrevSiblingOf(Doctrine_Record $dest)
    {
        $this->updateNode($dest->getNode()->getLeftValue());
    }

    /**
     * moves node as next sibling of dest record
     *        
     */
    public function moveAsNextSiblingOf(Doctrine_Record $dest)
    {
        $this->updateNode($dest->getNode()->getRightValue() + 1);
    }

    /**
     * moves node as first child of dest record
     *            
     */
    public function moveAsFirstChildOf(Doctrine_Record $dest)
    {
        $this->updateNode($dest->getNode()->getLeftValue() + 1);
    }

    /**
     * moves node as last child of dest record
     *        
     */
    public function moveAsLastChildOf(Doctrine_Record $dest)
    {
        $this->updateNode($dest->getNode()->getRightValue());
    }

    /**
     * adds node as last child of record
     *        
     */
    public function addChild(Doctrine_Record $record)
    {
        $record->getNode()->insertAsLastChildOf($this->getRecord());
    }

    /**
     * determines if node is leaf
     *
     * @return bool            
     */
    public function isLeaf()
    {
        return (($this->getRightValue() - $this->getLeftValue()) == 1);
    }

    /**
     * determines if node is root
     *
     * @return bool            
     */
    public function isRoot()
    {
        return ($this->getLeftValue() == 1);
    }

    /**
     * determines if node is equal to subject node
     *
     * @return bool            
     */    
    public function isEqualTo(Doctrine_Record $subj)
    {
        return (($this->getLeftValue() == $subj->getNode()->getLeftValue()) &&
                ($this->getRightValue() == $subj->getNode()->getRightValue()) && 
                ($this->getRootValue() == $subj->getNode()->getRootValue())
                );
    }

    /**
     * determines if node is child of subject node
     *
     * @return bool
     */
    public function isDescendantOf(Doctrine_Record $subj)
    {
        return (($this->getLeftValue()>$subj->getNode()->getLeftValue()) && ($this->getRightValue()<$subj->getNode()->getRightValue()) && ($this->getRootValue() == $subj->getNode()->getRootValue()));
    }

    /**
     * determines if node is child of or sibling to subject node
     *
     * @return bool            
     */
    public function isDescendantOfOrEqualTo(Doctrine_Record $subj)
    {
        return (($this->getLeftValue()>=$subj->getNode()->getLeftValue()) && ($this->getRightValue()<=$subj->getNode()->getRightValue()) && ($this->getRootValue() == $subj->getNode()->getRootValue()));
    }

    /**
     * determines if node is valid
     *
     * @return bool            
     */
    public function isValidNode()
    {
        return ($this->getRightValue() > $this->getLeftValue());
    }

    /**
     * deletes node and it's descendants
     *            
     */
    public function delete()
    {
        // TODO: add the setting whether or not to delete descendants or relocate children

        $q = $this->record->getTable()->createQuery();
        
        $componentName = $this->record->getTable()->getComponentName();

        $q = $q->where($componentName. '.lft >= ? AND ' . $componentName . '.rgt <= ?', array($this->getLeftValue(), $this->getRightValue()));

        $q = $this->record->getTable()->getTree()->returnQueryWithRootId($q, $this->getRootValue());
        
        $coll = $q->execute();

        $coll->delete();

        $first = $this->getRightValue() + 1;
        $delta = $this->getLeftValue() - $this->getRightValue() - 1;
        $this->shiftRLValues($first, $delta);
        
        return true; 
    }

    /**
     * sets node's left and right values and save's it
     *
     * @param int     $destLeft     node left value
     * @param int        $destRight    node right value
     */    
    private function insertNode($destLeft = 0, $destRight = 0, $destRoot = 1)
    {
        $this->setLeftValue($destLeft);
        $this->setRightValue($destRight);
        $this->setRootValue($destRoot);
        $this->record->save();    
    }

    /**
     * move node's and its children to location $destLeft and updates rest of tree
     *
     * @param int     $destLeft    destination left value
     */
    private function updateNode($destLeft)
    { 
        $left = $this->getLeftValue();
        $right = $this->getRightValue();

        $treeSize = $right - $left + 1;

        $this->shiftRLValues($destLeft, $treeSize);

        if($left >= $destLeft){ // src was shifted too?
            $left += $treeSize;
            $right += $treeSize;
        }

        // now there's enough room next to target to move the subtree
        $this->shiftRLRange($left, $right, $destLeft - $left);

        // correct values after source
        $this->shiftRLValues($right + 1, -$treeSize);

        $this->record->save();
        $this->record->refresh();
    }

    /**
     * adds '$delta' to all Left and Right values that are >= '$first'. '$delta' can also be negative.
     *
     * @param int $first         First node to be shifted
     * @param int $delta         Value to be shifted by, can be negative
     */    
    private function shiftRlValues($first, $delta, $rootId = 1)
    {
        $qLeft  = new Doctrine_Query();
        $qRight = new Doctrine_Query();

        // TODO: Wrap in transaction

        // shift left columns
        $qLeft = $qLeft->update($this->record->getTable()->getComponentName())
                                ->set('lft', 'lft + ' . $delta)
                                ->where('lft >= ?', $first);

        $qLeft = $this->record->getTable()->getTree()->returnQueryWithRootId($qLeft, $rootId);
        
        $resultLeft = $qLeft->execute();
        
        // shift right columns
        $resultRight = $qRight->update($this->record->getTable()->getComponentName())
                                ->set('rgt', 'rgt + ' . $delta)
                                ->where('rgt >= ?', $first);

        $qRight = $this->record->getTable()->getTree()->returnQueryWithRootId($qRight, $rootId);

        $resultRight = $qRight->execute();
    }

    /**
     * adds '$delta' to all Left and Right values that are >= '$first' and <= '$last'. 
     * '$delta' can also be negative.
     *
     * @param int $first     First node to be shifted (L value)
     * @param int $last     Last node to be shifted (L value)
     * @param int $delta         Value to be shifted by, can be negative
     */ 
    private function shiftRlRange($first, $last, $delta, $rootId = 1)
    {
        $qLeft  = new Doctrine_Query();
        $qRight = new Doctrine_Query();
        
        // TODO : Wrap in transaction

        // shift left column values
        $qLeft = $qLeft->update($this->record->getTable()->getComponentName())
                                ->set('lft', 'lft + ' . $delta)
                                ->where('lft >= ? AND lft <= ?', array($first, $last));
        
        $qLeft = $this->record->getTable()->getTree()->returnQueryWithRootId($qLeft, $rootId);

        $resultLeft = $qLeft->execute();
        
        // shift right column values
        $qRight = $qRight->update($this->record->getTable()->getComponentName())
                                ->set('rgt', 'rgt + ' . $delta)
                                ->where('rgt >= ? AND rgt <= ?', array($first, $last));

        $qRight = $this->record->getTable()->getTree()->returnQueryWithRootId($qRight, $rootId);

        $resultRight = $qRight->execute();
    }
    
    /**
     * gets record's left value
     *
     * @return int            
     */     
    public function getLeftValue()
    {
        return $this->record->get('lft');
    }

    /**
     * sets record's left value
     *
     * @param int            
     */     
    public function setLeftValue($lft)
    {
        $this->record->set('lft', $lft);        
    }

    /**
     * gets record's right value
     *
     * @return int            
     */     
    public function getRightValue()
    {
        return $this->record->get('rgt');        
    }

    /**
     * sets record's right value
     *
     * @param int            
     */    
    public function setRightValue($rgt)
    {
        $this->record->set('rgt', $rgt);         
    }

    /**
     * gets level (depth) of node in the tree
     *
     * @return int            
     */    
    public function getLevel()
    {
        if(!isset($this->level))
        {
            $q = $this->record->getTable()->createQuery();
            $q = $q->where('lft < ? AND rgt > ?', array($this->getLeftValue(), $this->getRightValue()));

            $q = $this->record->getTable()->getTree()->returnQueryWithRootId($q, $this->getRootValue());
            
            $coll = $q->execute();

            $this->level = $coll->count() ? $coll->count() : 0;
        }

        return $this->level;
    }

    /**
     * sets node's level
     *
     * @param int            
     */    
    public function setLevel($level)
    {
        $this->level = $level;         
    }

    /**
     * get records root id value
     *            
     */     
    public function getRootValue()
    {
        if($this->record->getTable()->getTree()->getAttribute('hasManyRoots'))
            return $this->record->get($this->record->getTable()->getTree()->getAttribute('rootColumnName'));
        
        return 1;
    }

    /**
     * sets records root id value
     *
     * @param int            
     */
    public function setRootValue($value)
    {
        if($this->record->getTable()->getTree()->getAttribute('hasManyRoots'))
            $this->record->set($this->record->getTable()->getTree()->getAttribute('rootColumnName'), $value);     
    }
}

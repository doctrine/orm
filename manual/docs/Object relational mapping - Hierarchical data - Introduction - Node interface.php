

The node interface, for inserting and manipulating nodes within the tree, is accessed on a record level. A full implementation of this interface will be as follows:



<code type="php">
interface Doctrine_Node_Interface {

    /**
     * insert node into tree
     */
    public function insertAsParentOf(Doctrine_Record $dest);

    public function insertAsPrevSiblingOf(Doctrine_Record $dest);

    public function insertAsNextSiblingOf(Doctrine_Record $dest);

    public function insertAsFirstChildOf(Doctrine_Record $dest);

    public function insertAsLastChildOf(Doctrine_Record $dest);

    public function addChild(Doctrine_Record $record);

    /**
     * moves node (if has children, moves branch)
     *
     */  
    public function moveAsPrevSiblingOf(Doctrine_Record $dest);

    public function moveAsNextSiblingOf(Doctrine_Record $dest);

    public function moveAsFirstChildOf(Doctrine_Record $dest);

    public function moveAsLastChildOf(Doctrine_Record $dest);

    /**
     * node information
     */
    public function getPrevSibling();

    public function getNextSibling();

    public function getSiblings($includeNode = false);

    public function getFirstChild();

    public function getLastChild();

    public function getChildren();

    public function getDescendants();

    public function getParent();

    public function getAncestors();

    public function getPath($seperator = ' > ', $includeNode = false);

    public function getLevel();

    public function getNumberChildren();

    public function getNumberDescendants();

    /**
     * node checks
     */
    public function hasPrevSibling();

    public function hasNextSibling();

    public function hasChildren();

    public function hasParent();

    public function isLeaf();

    public function isRoot();

    public function isEqualTo(Doctrine_Record $subj);

    public function isDescendantOf(Doctrine_Record $subj);

    public function isDescendantOfOrEqualTo(Doctrine_Record $subj);

    public function isValidNode();

    /**
     * deletes node and it's descendants
     */
    public function delete();
}

// if your model acts as tree you can retrieve the associated node object as follows
$record = $manager->getTable('Model')->find($pk);
$nodeObj = $record->getNode();
</code>
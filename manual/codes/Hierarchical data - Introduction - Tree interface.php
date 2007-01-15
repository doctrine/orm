<?php

interface Doctrine_Tree_Interface {

    /**
     * creates root node from given record or from a new record
     */
    public function createRoot(Doctrine_Record $record = null);

    /**
     * returns root node
     */
    public function findRoot($root_id = 1);

    /**
     * optimised method to returns iterator for traversal of the entire tree from root
     */
    public function fetchTree($options = array());

    /**
     * optimised method that returns iterator for traversal of the tree from the given record's primary key
     */
    public function fetchBranch($pk, $options = array());
}

// if your model acts as tree you can retrieve the associated tree object as follows
$treeObj = $manager->getTable('Model')->getTree();

?>
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
 * Doctrine_Node_Interface
 *
 * @package     Doctrine
 * @subpackage  Node
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
interface Doctrine_Node_Interface {

    /**
     * test if node has previous sibling
     *
     * @return bool
     */
    public function hasPrevSibling();

    /**
     * test if node has next sibling
     *
     * @return bool
     */
    public function hasNextSibling();

    /**
     * test if node has children
     *
     * @return bool
     */
    public function hasChildren();

    /**
     * test if node has parent
     *
     * @return bool
     */
    public function hasParent();

    /**
     * gets record of prev sibling or empty record
     *
     * @return object Doctrine_Record
     */
    public function getPrevSibling();

    /**
     * gets record of next sibling or empty record
     *
     * @return object Doctrine_Record
     */
    public function getNextSibling();

    /**
     * gets siblings for node
     *
     * @return array                            array of sibling Doctrine_Record objects
     */
    public function getSiblings($includeNode = false);

    /**
     * gets record of first child or empty record
     *
     * @return object Doctrine_Record
     */
    public function getFirstChild();

    /**
     * gets record of last child or empty record
     *
     * @return object Doctrine_Record
     */
    public function getLastChild();

    /**
     * gets children for node (direct descendants only)
     *
     * @return array                            array of sibling Doctrine_Record objects
     */
    public function getChildren();

    /**
     * gets descendants for node (direct descendants only)
     *
     * @return iterator                         iterator to traverse descendants from node
     */
    public function getDescendants();

    /**
     * gets record of parent or empty record
     *
     * @return object Doctrine_Record
     */
    public function getParent();

    /**
     * gets ancestors for node
     *
     * @return object Doctrine_Collection
     */
    public function getAncestors();

    /**
     * gets path to node from root, uses record::toString() method to get node names
     *
     * @param string $seperator                 path seperator
     * @param bool $includeNode                 whether or not to include node at end of path
     * @return string                           string representation of path
     */
    public function getPath($seperator = ' > ', $includeNode = false);

    /**
     * gets level (depth) of node in the tree
     *
     * @return int
     */
    public function getLevel();

    /**
     * gets number of children (direct descendants)
     *
     * @return int
     */
    public function getNumberChildren();

    /**
     * gets number of descendants (children and their children)
     *
     * @return int
     */
    public function getNumberDescendants();

    /**
     * inserts node as parent of dest record
     *
     * @return bool
     */
    public function insertAsParentOf(Doctrine_Record $dest);

    /**
     * inserts node as previous sibling of dest record
     *
     * @return bool
     */
    public function insertAsPrevSiblingOf(Doctrine_Record $dest);

    /**
     * inserts node as next sibling of dest record
     *
     * @return bool
     */
    public function insertAsNextSiblingOf(Doctrine_Record $dest);

    /**
     * inserts node as first child of dest record
     *
     * @return bool
     */
    public function insertAsFirstChildOf(Doctrine_Record $dest);

    /**
     * inserts node as first child of dest record
     *
     * @return bool
     */
    public function insertAsLastChildOf(Doctrine_Record $dest);

    /**
     * moves node as prev sibling of dest record
     *
     */  
    public function moveAsPrevSiblingOf(Doctrine_Record $dest);

    /**
     * moves node as next sibling of dest record
     *
     */
    public function moveAsNextSiblingOf(Doctrine_Record $dest);

    /**
     * moves node as first child of dest record
     *
     */
    public function moveAsFirstChildOf(Doctrine_Record $dest);

    /**
     * moves node as last child of dest record
     *
     */
    public function moveAsLastChildOf(Doctrine_Record $dest);

    /**
     * adds node as last child of record
     *
     */
    public function addChild(Doctrine_Record $record);

    /**
     * determines if node is leaf
     *
     * @return bool
     */
    public function isLeaf();

    /**
     * determines if node is root
     *
     * @return bool
     */
    public function isRoot();

    /**
     * determines if node is equal to subject node
     *
     * @return bool
     */
    public function isEqualTo(Doctrine_Record $subj);

    /**
     * determines if node is child of subject node
     *
     * @return bool
     */
    public function isDescendantOf(Doctrine_Record $subj);

    /**
     * determines if node is child of or sibling to subject node
     *
     * @return bool
     */
    public function isDescendantOfOrEqualTo(Doctrine_Record $subj);

    /**
     * determines if node is valid
     *
     * @return bool
     */
    public function isValidNode();

    /**
     * deletes node and it's descendants
     *
     */
    public function delete();
}
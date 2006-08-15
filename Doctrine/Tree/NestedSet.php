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
/**
 * Doctrine_Tree_NestedSet
 *
 * the purpose of Doctrine_Tree_NestedSet is to provide NestedSet tree access
 * strategy for all records extending it
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Tree_NestedSet extends Doctrine_Record {

    public function getLeafNodes() { 
        $query = "SELECT ".implode(", ",$this->table->getColumnNames()).
                 " FROM ".$this->table->getTableName().
                 " WHERE rgt = lft + 1";
    }

    public function getPath() { }

    public function getDepth() { 
        $query = "SELECT (COUNT(parent.name) - 1) AS depth
                  FROM ".$this->table->getTableName()." AS node,".
                  $this->table->getTableName()." AS parent
                  WHERE node.lft BETWEEN parent.lft AND parent.rgt
                  GROUP BY node.name";
    }

    public function removeNode() { }
    
    public function addNode() { }
}
?>

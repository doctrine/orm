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
 * Doctrine_Connection_UnitOfWork
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Connection_UnitOfWork implements IteratorAggregate, Countable {
    /**
     * @var Doctrine_Connection $conn       the connection object
     */
    private $connection;

    /**
     * the constructor
     *
     * @param Doctrine_Connection $conn
     */
    public function __construct(Doctrine_Connection $conn) {
        $this->conn = $conn;
    }

    /**
     * buildFlushTree
     * builds a flush tree that is used in transactions
     *
     * @return array
     */
    public function buildFlushTree(array $tables) {
        $tree = array();
        foreach($tables as $k => $table) {
            $k = $k.$table;
            if( ! ($table instanceof Doctrine_Table))
                $table = $this->conn->getTable($table);

            $nm     = $table->getComponentName();

            $index  = array_search($nm,$tree);

            if($index === false) {
                $tree[] = $nm;
                $index  = max(array_keys($tree));
            }

            $rels = $table->getRelations();
            
            // group relations
            
            foreach($rels as $key => $rel) {
                if($rel instanceof Doctrine_Relation_ForeignKey) {
                    unset($rels[$key]);
                    array_unshift($rels, $rel);
                }
            }

            foreach($rels as $rel) {
                $name   = $rel->getTable()->getComponentName();
                $index2 = array_search($name,$tree);
                $type   = $rel->getType();

                // skip self-referenced relations
                if($name === $nm)
                    continue;

                if($rel instanceof Doctrine_Relation_ForeignKey) {
                    if($index2 !== false) {
                        if($index2 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree,$index2,0,$nm);
                        $index = $index2;
                    } else {
                        $tree[] = $name;
                    }

                } elseif($rel instanceof Doctrine_Relation_LocalKey) {
                    if($index2 !== false) {
                        if($index2 <= $index)
                            continue;

                        unset($tree[$index2]);
                        array_splice($tree,$index,0,$name);
                    } else {
                        array_unshift($tree,$name);
                        $index++;
                    }
                } elseif($rel instanceof Doctrine_Relation_Association) {
                    $t = $rel->getAssociationFactory();
                    $n = $t->getComponentName();
                    
                    if($index2 !== false)
                        unset($tree[$index2]);
                    
                    array_splice($tree,$index, 0,$name);
                    $index++;

                    $index3 = array_search($n,$tree);

                    if($index3 !== false) {
                        if($index3 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree,$index3,0,$n);
                        $index = $index2;
                    } else {
                        $tree[] = $n;
                    }
                }
            }
        }
        return array_values($tree);
    }

    public function getIterator() { }

    public function count() { }
}

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
 * Doctrine_Relation_ForeignKey
 * This class represents a foreign key relation
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_ForeignKey extends Doctrine_Relation {
    /**
     * processDiff
     *
     * @param Doctrine_Record $record
     */
    public function processDiff(Doctrine_Record $record) {
        $alias = $this->getAlias();

        if ($this->isOneToOne()) {
            if ($record->obtainOriginals($alias)
               && $record->obtainOriginals($alias)->obtainIdentifier() != $this->obtainReference($alias)->obtainIdentifier()
            ) {
                    $record->obtainOriginals($alias)->delete();
            }
        } else {
            if ($record->hasReference($alias)) {
                $new = $record->obtainReference($alias);

                if ( ! $record->obtainOriginals($alias)) {
                    $record->loadReference($alias);
                }
                $operations = Doctrine_Relation::getDeleteOperations($record->obtainOriginals($alias), $new);

                foreach ($operations as $r) {
                    $r->delete();
                }

                $record->assignOriginals($alias, clone $record->get($alias));
            }
        }
    }
    /**
     * fetchRelatedFor
     *
     * fetches a component related to given record
     *
     * @param Doctrine_Record $record
     * @return Doctrine_Record|Doctrine_Collection
     */
    public function fetchRelatedFor(Doctrine_Record $record) {
        $id = $record->get($this->local);

        if ($this->isOneToOne()) {
            if (empty($id)) {
                $related = $this->table->create();
            } else {
                $dql  = "FROM ".$this->table->getComponentName()." WHERE ".$this->table->getComponentName().".".$this->foreign." = ?";
                $coll = $this->table->getConnection()->query($dql, array($id));
                $related = $coll[0];
            }

            $related->set($this->foreign, $record, false);

        } else {
            if (empty($id)) {
                $related = new Doctrine_Collection($this->table);
            } else {
                $query      = $this->getRelationDql(1);
                $related    = $this->table->getConnection()->query($query, array($id));
            }
            $related->setReference($record, $this);
        }
        return $related;
    }
}

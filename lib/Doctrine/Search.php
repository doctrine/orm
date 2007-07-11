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
 * Doctrine_Search
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Search
{
    public function buildDefinition(Doctrine_Record $record)
    {

        $columns = array('keyword'  => array('type'    => 'string',
                                             'length'  => 200,
                                             'notnull' => true),
                         'field'    => array('type'    => 'string',
                                             'length'  => 50,
                                             'notnull' => true),
                         'position' => array('type'    => 'integer',
                                             'length'  => 8));

        $id = $record->getTable()->getIdentifier();
        $name = $record->getTable()->getComponentName();

        $options = array('className' => $name . 'Index');


        $fk = array();
        foreach ((array) $id as $column) {
            $def = $record->getTable()->getDefinitionOf($column);

            unset($def['autoincrement']);
            unset($def['sequence']);
            unset($def['primary']);

            $col = strtolower($name . '_' . $column);

            $fk[$col] = $def;
        }
        
        $local = (count($fk) > 1) ? array_keys($fk) : key($fk);
        
        $relations = array($name => array('local' => $local,
                                          'foreign' => $id, 
                                          'onDelete' => 'CASCADE',
                                          'onUpdate' => 'CASCADE'));


        $columns += $fk;

        $builder = new Doctrine_Import_Builder();

        $def = $builder->buildDefinition($options, $columns, $relations);
    
        print "<pre>";
        print_r($def);
    }
}
/**
fields:
[keyword] [field] [foreign_id] [position]


fields:
[keyword] [field] [match]

example data:
'orm' 'content' '1:36|2:23'
*/

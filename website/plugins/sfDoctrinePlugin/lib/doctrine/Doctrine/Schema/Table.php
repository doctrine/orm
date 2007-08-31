<?php
/*
 *  $Id: Table.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Schema_Object');
/**
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version     $Id: Table.php 1080 2007-02-10 18:17:08Z romanb $
 */
/**
 * class Doctrine_Schema_Table
 * Holds information on a database table
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Schema_Table extends Doctrine_Schema_Object implements Countable, IteratorAggregate
{

    protected $definition = array('name'        => '',
                                  'check'       => '',
                                  'charset'     => '',
                                  'description' => '');
    /**
     * Relations this table has with others. An array of Doctrine_Schema_Relation
     */
    private $relations = array();
    /**
     *
     * @return bool
     * @access public
     */
    public function isValid( )
    {

    }
    /**
     * returns an array of Doctrine_Schema_Column objects
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->children;
    }
    /**
     * @return Doctrine_Schema_Column|false
     */
    public function getColumn($name)
    {
        if ( ! isset($this->children[$name])) {
            return false;
        }
        return $this->children[$name];
    }
    /**
     *
     * @param Doctrine_Schema_Column column
     * @return Doctrine_Schema_Table
     * @access public
     */
    public function addColumn(Doctrine_Schema_Column $column)
    {
        $name = $column->get('name');
        $this->children[$name] = $column;

        return $this;
    }

    /**
     * Adds a relation between a local column and a 2nd table / column
     *
     * @param Doctrine_Schema_Relation Relation
     *
    */
    public function setRelation(Doctrine_Schema_Relation $relation){
         $this->relations[] = $relation;
    }
    /**
     * Return all the relations this table has with others
     *
     * @return array Array of Doctrine_Schema_Relation
    */
    public function getRelations(){
        return $this->relations;
    }

}

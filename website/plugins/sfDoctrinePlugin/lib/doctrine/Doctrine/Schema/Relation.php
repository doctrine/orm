<?php
/*
 *  $Id: Relation.php 1080 2007-02-10 18:17:08Z romanb $
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
 * @version     $Id: Relation.php 1080 2007-02-10 18:17:08Z romanb $
 */
/**
 * class Doctrine_Schema_Relation
 * Holds information on a foreign key relation.
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Schema_Relation extends Doctrine_Schema_Object
{

    /**
     * Column that refers to another table
     * @access public
     */
    public $referencingColumn;

    /**
     * Column that is referred from another table
     * @access public
     */
    public $referencedColumn;

    /**
     * Table where the referred column lives
     * @access public
     *
    */
    public $referencedTable;

    /**
     * ON UPDATE or ON DELETE action
     * @static
     * @access public
     */
    public static $ACTION_RESTRICT = 1;

    /**
     * ON UPDATE or ON DELETE action
     * @static
     * @access public
     */
    public static $ACTION_SET_NULL = 2;

    /**
     * ON UPDATE or ON DELETE action
     * @static
     * @access public
     */
    public static $ACTION_CASCADE = 3;

    /**
     * ON UPDATE or ON DELETE action
     * @static
     * @access public
     */
    public static $ACTION_NO_ACTION = 4;

    /**
     * ON UPDATE or ON DELETE action
     * @static
     * @access public
     */
    public static $ACTION_SET_DEFAULT = 5;

    /**
     *
     * @param Doctrine_Schema_Column referencing
     * @param Doctrine_Schema_Table referencedtable
     * @param Doctrine_Schema_Column referencedColumn
     * @return
     * @access public
     */
    public function setRelationBetween( $referencingColumn, $referencedTable, $referencedColumn )
    {
        $this->referencingColumn = $referencingColumn;
        $this->referencedTable = $referencedTable;
        $this->referencedColumn = $referencedColumn;
    }
    /**
     * @return string
     */
    public function __toString( )
    {
        return "Relation between '".$this->referencingColumn."' and '".$this->referencedTable."'.'".$this->referencingColumn."'";
    }
    /**
     *
     * @return bool
     */
    public function isValid( )
    {

    }
}

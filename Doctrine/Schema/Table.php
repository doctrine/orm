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
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version     $Id$
 */



/**
 * class Doctrine_Schema_Table
 * Holds information on a database table
 */
class Doctrine_Schema_Table extends Doctrine_Schema_Object
            implements Countable, Countable, IteratorAggregate
{

    /** Aggregations: */

    /** Compositions: */
    var $m_Vector = array();
    var $m_;

     /*** Attributes: ***/

    /**
     * Unique key fields
     * @access public
     */
    public $uniqueKeys;

    /**
     * Indexed columns
     * @access public
     */
    public $indexes;

    /**
     * Table name
     * @access public
     */
    public $name;

    /**
     * @access public
     */
    public $primaryKeys;

    /**
     * Check constraint definition
     * @access public
     */
    public $check;

    /**
     * Character encoding e.g. ISO-8859-1 or UTF-8 etc.
     * @access public
     */
    public $charset;

    /**
     * Description or comment given in the schema
     * @access public
     */
    public $description;

    /**
     * Columns of the table
     * @access private
     */
    private $childs;


    /**
     *
     * @return 
     * @access public
     */
    public function __toString( ) {
        
    } // end of member function __toString

    /**
     *
     * @return 
     * @access public
     */
    public function __clone( ) {
        
    } // end of member function __clone

    /**
     *
     * @return bool
     * @access public
     */
    public function isValid( ) {
        
    } // end of member function isValid

    /**
     *
     * @param Doctrine_Schema_Column column      * @return Doctrine_Schema_Column
     * @access public
     */
    public function addColumn( $column = null ) {
        
    } // end of member function addColumn





} // end of Doctrine_Schema_Table


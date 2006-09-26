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
 * class Doctrine_Schema_Database
 * Holds information on a database
 */
class Doctrine_Schema_Database extends Doctrine_Schema_Object
            implements Countable, IteratorAggregate
{

    /** Aggregations: */

    /** Compositions: */
    var $m_;

     /*** Attributes: ***/

    /**
     * Database name
     * @access public
     */
    public $name;

    /**
     * Database driver type
     * @access public
     */
    public $type;

    /**
     * Database server version
     * @access public
     */
    public $version;

    /**
     * The underlaying engine in the database e.g. InnoDB or MyISAM in MySQL.
     * @access public
     */
    public $engine;

    /**
     * Character encoding e.g. ISO-8859-1 or UTF-8 etc.
     * @access public
     */
    public $charset;

    /**
     * Foreign key constraints in the database
     * @access public
     */
    public $foreignKeyRelations;

    /**
     * Tables in the database
     * @access private
     */
    private $childs;


    /**
     *
     * @return 
     * @access public
     */
    public function __clone( ) {
        
    } // end of member function __clone

    /**
     *
     * @return 
     * @access public
     */
    public function __toString( ) {
        
    } // end of member function __toString

    /**
     *
     * @return bool
     * @access public
     */
    public function isValid( ) {
        
    } // end of member function isValid

    /**
     *
     * @param Doctrine_Schema_Table table      * @return Doctrine_Schema_Table
     * @access public
     */
    public function addTable( $table = null ) {
        
    } // end of member function addTable





} // end of Doctrine_Schema_Database


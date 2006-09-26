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
 * class Doctrine_Schema_Relation
 * Holds information on a foreign key relation.
 */
class Doctrine_Schema_Relation extends Doctrine_Schema_Object
{

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    /**
     * Columns that refer to another table
     * @access public
     */
    public $referencingFields;

    /**
     * Columns that are referred from another table
     * @access public
     */
    public $referredFields;

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
     * @param Doctrine_Schema_Column referringColumns      * @param Doctrine_Schema_Column referencedColumns      * @return 
     * @access public
     */
    public function setRelationBetween( $referringColumns,  $referencedColumns ) {
        
    } // end of member function setRelationBetween

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





} // end of Doctrine_Schema_Relation


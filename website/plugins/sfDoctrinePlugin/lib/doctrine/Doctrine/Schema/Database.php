<?php
/*
 *  $Id: Database.php 1080 2007-02-10 18:17:08Z romanb $
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
 * @version     $Id: Database.php 1080 2007-02-10 18:17:08Z romanb $
 */
/**
 * class Doctrine_Schema_Database
 * Holds information on a database
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Schema_Database extends Doctrine_Schema_Object
{

    protected $definition = array('name'        => null,
                                  'type'        => null,
                                  'charset'     => null,
                                  'description' => null,
                                  'version'     => null,
                                  'engine'      => null);

    private $childs = array();

    /**
     *
     * @return
     * @access public
     */
    public function __clone( )
    {

    }
    /**
     *
     * @return
     * @access public
     */
    public function __toString( )
    {

    }
    /**
     *
     * @return bool
     * @access public
     */
    public function isValid( )
    {

    }
    /**
     *
     * @param Doctrine_Schema_Table table      * @return Doctrine_Schema_Table
     * @access public
     */
    public function addTable( $table = null )
    {
        $this->childs[] = $table;
    }

    /**
     *
     * @return array of Doctrine_Schema_Table
     *
     */
    public function getTables()
    {
        return $this->childs;
    }
}

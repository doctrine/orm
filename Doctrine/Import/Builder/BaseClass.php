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
 * class Doctrine_Import_Builder_BaseClass
 * Builds a Doctrine_Record base class definition based on a schema.
 */
class Doctrine_Import_Builder_BaseClass extends Doctrine_Import_Builder
{

    /** Aggregations: */

    /** Compositions: */

     /*** Attributes: ***/

    private $path = '';
    private $suffix = '.php';


    /**
     *
     * @param string path      
     * @return 
     * @access public
     */
    public function setOutputPath( $path ) {
        $this->path = $path;
    } // end of member function setOuputPath

    /**
     *
     * @param string path      
     * @return 
     * @access public
     */
    public function setFileSuffix( $suffix ) {
        $this->suffix = $suffix;
    } // end of member function setOuputPath


    /**
     *
     * @param Doctrine_Schema schema      
     * @return 
     * @access public
     * @throws Doctrine_Import_Exception
     */
    public function build(Doctrine_Schema $schema )
    {
    	/* @todo FIXME i am incomplete*/
    }

} // end of Doctrine_Import_Builder_BaseClass


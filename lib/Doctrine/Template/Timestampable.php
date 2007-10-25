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
 * Doctrine_Template_Timestampable
 *
 * Easily add created and updated at timestamps to your doctrine records
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Template_Timestampable extends Doctrine_Template
{
    /**
     * Array of timestampable options
     *
     * @var string
     */
    protected $_options = array();
    
    /**
     * __construct
     *
     * @param string $array 
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = $options;
    }
    
    /**
     * setTableDefinition
     *
     * @return void
     */
    public function setTableDefinition()
    {
        $createdOptions = array();
        $updatedOptions = array();
        
        if (isset($this->_options['created'])) {
            $createdOptions = $this->_options['created'];
            unset($createdOptions['name']);
            unset($createdOptions['type']);
        }
        
        if (isset($this->_options['updated'])) {
            $updatedOptions = $this->_options['updated'];
            unset($updatedOptions['name']);
            unset($updatedOptions['type']);
        }
        
        $createdName = isset($this->_options['created']['name']) ? $this->_options['created']['name']:'created_at';
        $createdType = isset($this->_options['created']['type']) ? $this->_options['created']['type']:'timestamp';
        
        $updatedName = isset($this->_options['updated']['name']) ? $this->_options['updated']['name']:'updated_at';
        $updatedType = isset($this->_options['updated']['type']) ? $this->_options['updated']['type']:'timestamp';
        
        $this->hasColumn($createdName, $createdType, null, $createdOptions);
        $this->hasColumn($updatedName, $updatedType, null, $updatedOptions);
        
        $this->_options['created']['name'] = $createdName;
        $this->_options['created']['type'] = $createdType;
        
        $this->_options['updated']['name'] = $updatedName;
        $this->_options['updated']['type'] = $updatedType;
        
        $this->addListener(new Doctrine_Timestampable_Listener($this->_options));
    }
}
<?php
/*
 *  $Id: Driver.php 1401 2007-05-20 17:54:22Z zYne $
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
 * Doctrine_Cache_Driver
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Cache
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1401 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Cache_Driver implements Doctrine_Cache_Interface
{
    /**
     * @var array $_options      an array of options
     */
    protected $_options = array();
    
    /**
     * constructor
     *
     * @param array $_options      an array of options
     */
    public function __construct($options) 
    {
        $this->_options = $options;
    }
    /**
     * setOption
     *
     * @param mixed $option     the option name
     * @param mixed $value      option value
     * @return boolean          TRUE on success, FALSE on failure
     */
    public function setOption($option, $value)
    {
    	if (isset($this->_options[$option])) {
            $this->_options[$option] = $value;
            return true;
        }
        return false;
    }
    /**
     * getOption
     * 
     * @param mixed $option     the option name
     * @return mixed            option value
     */
    public function getOption($option)
    {
        if ( ! isset($this->_options[$option])) {
            return null;
        }

        return $this->_options[$option];
    }
}

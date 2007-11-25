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
 * Doctrine_Template_I18n
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Template_I18n extends Doctrine_Template
{
    /**
     * __construct
     *
     * @param string $array 
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->_plugin = new Doctrine_I18n($options);
    }
    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        $name = $this->_table->getComponentName();

        $this->_plugin->buildPluginDefinition($this->_table);

        $className = $this->_plugin->getOption('className');

        $id = $this->_table->getIdentifier();

        $this->hasMany($className . ' as Translation', array('local' => $id, 'foreign' => $id));
    }
    
    /**
     * getI18n
     *
     * @return void
     */
    public function getI18n()
    {
        return $this->_plugin;
    }
}

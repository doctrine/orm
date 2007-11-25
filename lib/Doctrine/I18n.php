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
 * Doctrine_I18n
 *
 * @package     Doctrine
 * @subpackage  I18n
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_I18n extends Doctrine_Plugin
{
    protected $_options = array(
                            'className'     => '%CLASS%Translation',
                            'fields'        => array(),
                            'generateFiles' => false,
                            'table'         => false,
                            'pluginTable'   => false,
                            'children'      => array(),
                            );

    /**
     * __construct
     *
     * @param string $options 
     * @return void
     */
    public function __construct($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * buildDefinition
     *
     * @param object $Doctrine_Table 
     * @return void
     */
    public function buildDefinition()
    {
      	if (empty($this->_options['fields'])) {
      	    throw new Doctrine_I18n_Exception('Fields not set.');
      	}

        $name = $this->_options['table']->getComponentName();

        $columns = array();

        $options = array('className' => $this->_options['className']);

        $fk = $this->buildForeignKeys($this->_options['table']);

        $cols = $this->_options['table']->getColumns();

        foreach ($cols as $column => $definition) {
            if (in_array($column, $this->_options['fields'])) {
                $columns[$column] = $definition;
                $this->_options['table']->removeColumn($column);
            }
        }

        $columns['lang'] = array('type'    => 'string',
                                 'length'  => 2,
                                 'fixed'   => true,
                                 'primary' => true);

        $relations = $this->buildRelation();

        $columns += $fk;

        $options = array('queryParts' => array('indexBy' => 'lang'));

        $this->generateClass($columns, $relations, $options);

        $this->_options['pluginTable'] = $this->_options['table']->getConnection()->getTable($this->_options['className']);
        
        $this->_options['pluginTable']->bindQueryPart('indexBy', 'lang');

        return true;
    }
}

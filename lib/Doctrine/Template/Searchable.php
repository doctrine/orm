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
 * Doctrine_Template_Searchable
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Template_Searchable extends Doctrine_Template
{     
    protected $_search;

    public function __construct(array $options)
    {
        $this->_search = new Doctrine_Search($options);
    }
    public function setUp()
    {
        $id = $this->_table->getIdentifier();
        $name = $this->_table->getComponentName();
        $className = $this->_search->getOption('className');

        if (strpos($className, '%CLASS%') !== false) {
            $this->_search->setOption('className', str_replace('%CLASS%', $name, $className));
            $className = $this->_search->getOption('className');
        }
        $this->_search->buildDefinition($this->_table);

        foreach ((array) $id as $column) {
            $foreign[] = strtolower(Doctrine::tableize($this->_table->getComponentName()) . '_' . $column);
        }

        $foreign = (count($foreign) > 1) ? $foreign : current($foreign);

        $this->hasMany($className, array('local' => $id, 'foreign' => $foreign));

        $this->addListener(new Doctrine_Search_Listener($this->_search));
    }
}

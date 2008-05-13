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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Template_Geographical
 *
 * Easily add created and updated at timestamps to your doctrine records
 *
 * @package     Doctrine
 * @subpackage  Template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Template_Geographical extends Doctrine_Template
{
    /**
     * Array of timestampable options
     *
     * @var string
     */
    protected $_options = array('latitude' =>  array('name'     =>  'latitude',
                                                     'type'     =>  'float',
                                                     'size'     =>  4,
                                                     'options'  =>  array()),
                                'longitude' => array('name'     =>  'longitude',
                                                     'type'     =>  'float',
                                                     'size'     =>  4,
                                                     'options'  =>  array()));

    /**
     * __construct
     *
     * @param string $array 
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
    }

    /**
     * setTableDefinition
     *
     * @return void
     */
    public function setTableDefinition()
    {
        $this->hasColumn($this->_options['latitude']['name'], $this->_options['latitude']['type'], $this->_options['latitude']['size'], $this->_options['latitude']['options']);
        $this->hasColumn($this->_options['longitude']['name'], $this->_options['longitude']['type'], $this->_options['longitude']['size'], $this->_options['longitude']['options']);
    }

    public function getDistanceQuery()
    {
        $invoker = $this->getInvoker();
        $query = $invoker->getTable()->createQuery();

        $rootAlias = $query->getRootAlias();
        $latName = $this->_options['latitude']['name'];
        $longName = $this->_options['longitude']['name'];

        $query->addSelect($rootAlias . '.*');

        $sql = "((ACOS(SIN(%s * PI() / 180) * SIN(" . $rootAlias . "." . $latName . " * PI() / 180) + COS(%s * PI() / 180) * COS(" . $rootAlias . "." . $latName . " * PI() / 180) * COS((%s - " . $rootAlias . "." . $longName . ") * PI() / 180)) * 180 / PI()) * 60 * %s) as %s";

        $milesSql = sprintf($sql, number_format($invoker->get('latitude'), 1), $invoker->get('latitude'), $invoker->get('longitude'), '1.1515', 'miles');
        $query->addSelect($milesSql);

        $kilometersSql = sprintf($sql, number_format($invoker->get('latitude'), 1), $invoker->get('latitude'), $invoker->get('longitude'), '1.1515 * 1.609344', 'kilometers');
        $query->addSelect($kilometersSql);

        return $query;
    }

    public function getDistance(Doctrine_Entity $record, $kilometers = false)
    {
        $query = $this->getDistanceQuery($kilometers);
        
        $conditions = array();
        $values = array();
        foreach ((array) $record->getTable()->getIdentifier() as $id) {
            $conditions[] = $query->getRootAlias() . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }

        $where = implode(' AND ', $conditions);

        $query->addWhere($where, $values);

        $query->limit(1);

        $result = $query->execute()->getFirst();
        
        if (isset($result['kilometers']) && $result['miles']) {
            return $kilometers ? $result->get('kilometers'):$result->get('miles');
        } else {
            return 0;
        }
    }
}
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
 * Doctrine_AuditLog
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_AuditLog
{
    protected $_options = array(
                            'className'     => '%CLASS%Version',
                            'versionColumn' => 'version',
                            'generateFiles' => false,
                            'table'         => false,
                            );

    protected $_auditTable;

    public function __construct($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
    /**
     * __get
     * an alias for getOption
     *
     * @param string $option
     */
    public function __get($option)
    {
        if (isset($this->options[$option])) {
            return $this->_options[$option];
        }
        return null;
    }
    /**
     * __isset
     *
     * @param string $option
     */
    public function __isset($option) 
    {
        return isset($this->_options[$option]);
    }
    /**
     * getOptions
     * returns all options of this table and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->_options;
    }
    /**
     * setOption
     * sets an option and returns this object in order to
     * allow flexible method chaining
     *
     * @see slef::$_options             for available options
     * @param string $name              the name of the option to set
     * @param mixed $value              the value of the option
     * @return Doctrine_AuditLog        this object
     */
    public function setOption($name, $value)
    {
        if ( ! isset($this->_options[$name])) {
            throw new Doctrine_Exception('Unknown option ' . $name);
        }
        $this->_options[$name] = $value;
    }
    /**
     * getOption
     * returns the value of given option
     *
     * @param string $name  the name of the option
     * @return mixed        the value of given option
     */
    public function getOption($name)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        }
        return null;
    }

    public function getVersion(Doctrine_Record $record, $version)
    {           
        $className = $this->_options['className'];

        $q = new Doctrine_Query();

        $values = array();
        foreach ((array) $this->_options['table']->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }
        $where = implode(' AND ', $conditions) . ' AND ' . $className . '.' . $this->_options['versionColumn'] . ' = ?';
        
        $values[] = $version;

        $q->from($className)
          ->where($where);

        return $q->execute($values, Doctrine_HYDRATE::HYDRATE_ARRAY);
    }
    public function buildDefinition(Doctrine_Table $table)
    {
        $this->_options['className'] = str_replace('%CLASS%', 
                                                   $this->_options['table']->getComponentName(),
                                                   $this->_options['className']);

        $name = $table->getComponentName();

        $className = $name . 'Version';
        
        if (class_exists($className)) {
            return false;
        }

        $columns = $table->getColumns();
        
        // the version column should be part of the primary key definition
        $columns[$this->_options['versionColumn']]['primary'] = true;

        $id = $table->getIdentifier();

        $options = array('className' => $className);

        $builder = new Doctrine_Import_Builder();

        $def = $builder->buildDefinition($options, $columns);

        if ( ! $this->_options['generateFiles']) {
            eval($def);
        }
        return true;
    }
}

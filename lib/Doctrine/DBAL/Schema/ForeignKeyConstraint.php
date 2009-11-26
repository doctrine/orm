<?php

namespace Doctrine\DBAL\Schema;

class ForeignKeyConstraint extends AbstractAsset implements Constraint
{
    /**
     * @var array
     */
    protected $_localColumnNames;

    /**
     * @var string
     */
    protected $_foreignTableName;

    /**
     * @var array
     */
    protected $_foreignColumnNames;

    /**
     * @var string
     */
    protected $_cascade = '';

    /**
     * @var array
     */
    protected $_options;

    /**
     *
     * @param array $localColumnNames
     * @param string $foreignTableName
     * @param array $foreignColumnNames
     * @param string $cascade
     * @param string|null $name
     */
    public function __construct(array $localColumnNames, $foreignTableName, array $foreignColumnNames, $name=null, array $options=array())
    {
        $this->_setName($name);
        $this->_localColumnNames = $localColumnNames;
        $this->_foreignTableName = $foreignTableName;
        $this->_foreignColumnNames = $foreignColumnNames;
        $this->_options = $options;
    }

    /**
     * @return array
     */
    public function getLocalColumnNames()
    {
        return $this->_localColumnNames;
    }

    /**
     * @return string
     */
    public function getForeignTableName()
    {
        return $this->_foreignTableName;
    }

    /**
     * @return array
     */
    public function getForeignColumnNames()
    {
        return $this->_foreignColumnNames;
    }

    public function hasOption($name)
    {
        return isset($this->_options[$name]);
    }

    public function getOption($name)
    {
        return $this->_options[$name];
    }
}
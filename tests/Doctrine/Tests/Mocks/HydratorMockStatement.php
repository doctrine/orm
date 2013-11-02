<?php

namespace Doctrine\Tests\Mocks;

/**
 * This class is a mock of the Statement interface that can be passed in to the Hydrator
 * to test the hydration standalone with faked result sets.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 */
class HydratorMockStatement implements \IteratorAggregate, \Doctrine\DBAL\Driver\Statement
{
    /**
     * @var array
     */
    private $_resultSet;

    /**
     * Creates a new mock statement that will serve the provided fake result set to clients.
     *
     * @param array $resultSet The faked SQL result set.
     */
    public function __construct(array $resultSet)
    {
        $this->_resultSet = $resultSet;
    }

    /**
     * Fetches all rows from the result set.
     *
     * @param int|null   $fetchStyle
     * @param int|null   $columnIndex
     * @param array|null $ctorArgs
     *
     * @return array
     */
    public function fetchAll($fetchStyle = null, $columnIndex = null, array $ctorArgs = null)
    {
        return $this->_resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnNumber = 0)
    {
        $row = current($this->_resultSet);
        if ( ! is_array($row)) return false;
        $val = array_shift($row);
        return $val !== null ? $val : false;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchStyle = null)
    {
        $current = current($this->_resultSet);
        next($this->_resultSet);
        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->_resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchStyle, $arg2 = null, $arg3 = null)
    {
    }
}

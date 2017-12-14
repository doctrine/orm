<?php

namespace Doctrine\Tests\Mocks;


/**
 * Simple statement mock that returns result based on array.
 * Doesn't support fetch modes
 */
class StatementArrayMock extends StatementMock
{
    /**
     * @var array
     */
    private $_result;

    public function __construct($result)
    {
        $this->_result = $result;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_result);
    }

    public function columnCount()
    {
        $row = reset($this->_result);
        if ($row) {
            return count($row);
        } else {
            return 0;
        }
    }

    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->_result;
    }

    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $current = current($this->_result);
        next($this->_result);

        return $current;
    }

    public function fetchColumn($columnIndex = 0)
    {
        $current = current($this->_result);
        if ($current) {
            next($this->_result);
            return reset($current);
        }

        return false;
    }

    public function rowCount()
    {
        return count($this->_result);
    }
}

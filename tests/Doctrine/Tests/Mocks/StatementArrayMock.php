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
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }

    public function columnCount()
    {
        $row = reset($this->result);
        if ($row) {
            return count($row);
        } else {
            return 0;
        }
    }

    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->result;
    }

    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $current = current($this->result);
        next($this->result);

        return $current;
    }

    public function fetchColumn($columnIndex = 0)
    {
        $current = current($this->result);
        if ($current) {
            next($this->result);
            return reset($current);
        } else {
            return false;
        }
    }

    public function rowCount()
    {
        return count($this->result);
    }
}

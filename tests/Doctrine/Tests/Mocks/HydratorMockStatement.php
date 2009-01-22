<?php

namespace Doctrine\Tests\Mocks;

/**
 * This class is a mock of the PDOStatement class that can be passed in to the Hydrator
 * to test the hydration standalone with faked result sets.
 *
 * @author  Roman Borschel <roman@code-factory.org>
 */
class HydratorMockStatement
{
    private $_resultSet;    
    
    /**
     * Creates a new mock statement that will serve the provided fake result set to clients.
     *
     * @param array $resultSet  The faked SQL result set.
     */
    public function __construct(array $resultSet)
    {
        $this->_resultSet = $resultSet;
    }
    
    /**
     * Fetches all rows from the result set.
     *
     * NOTE: Must adhere to the PDOStatement::fetchAll() signature that looks as follows:
     * array fetchAll  ([ int $fetch_style  [, int $column_index  [, array $ctor_args  ]]] )
     *
     * @return array
     */
    public function fetchAll($fetchStyle = null, $columnIndex = null, array $ctorArgs = null)
    {
        return $this->_resultSet;
    }
    
    public function fetchColumn($columnNumber = 0)
    {
        $row = array_shift($this->_resultSet);
        if ( ! is_array($row)) return false;
        $val = array_shift($row);
        return $val !== null ? $val : false;
    }
    
    /**
     * Fetches the next row in the result set.
     *
     * NOTE: Must adhere to the PDOStatement::fetch() signature that looks as follows:
     * mixed fetch  ([ int $fetch_style  [, int $cursor_orientation  [, int $cursor_offset  ]]] )
     *
     */
    public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null)
    {
        return array_shift($this->_resultSet);
    }
    
    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean
     */
    public function closeCursor()
    {
        return true;
    }
    
    public function setResultSet(array $resultSet)
    {
        $this->_resultSet = $resultSet;
    }
}
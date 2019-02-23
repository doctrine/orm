<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use ArrayIterator;
use function count;
use function current;
use function next;
use function reset;

/**
 * Simple statement mock that returns result based on array.
 * Doesn't support fetch modes
 */
class StatementArrayMock extends StatementMock
{
    /** @var array */
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->result);
    }

    public function columnCount()
    {
        $row = reset($this->result);
        if ($row) {
            return count($row);
        }

        return 0;
    }

    public function fetchAll($fetchMode = null, ...$args)
    {
        return $this->result;
    }

    public function fetch($fetchMode = null, ...$args)
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
        }

        return false;
    }

    public function rowCount() : int
    {
        return count($this->result);
    }
}

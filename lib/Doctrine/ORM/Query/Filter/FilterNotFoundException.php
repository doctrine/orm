<?php
namespace Doctrine\ORM\Query\Filter;

class FilterNotFoundException extends \InvalidArgumentException
{
    public function __construct($name)
    {
        parent::__construct("Filter '{$name}' does not exist.");
    }
}

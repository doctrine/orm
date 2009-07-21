<?php

namespace Doctrine\Tests\Mocks;

class MockTreeWalker extends \Doctrine\ORM\Query\TreeWalkerAdapter
{
    /**
     * Gets an executor that can be used to execute the result of this walker.
     * 
     * @return AbstractExecutor
     */
    public function getExecutor($AST)
    {
        return null;
    }
}


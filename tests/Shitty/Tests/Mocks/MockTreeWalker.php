<?php

namespace Shitty\Tests\Mocks;

/**
 * Mock class for TreeWalker.
 */
class MockTreeWalker extends \Shitty\ORM\Query\TreeWalkerAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
        return null;
    }
}

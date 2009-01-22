<?php

namespace Doctrine\Tests\ORM;

require_once dirname(__FILE__) . '/../TestInit.php';

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CommitOrderCalculatorTest extends \Doctrine\Tests\OrmTestCase
{
    private $_calc;
    
    protected function setUp()
    {
        $this->_calc = new \Doctrine\ORM\Internal\CommitOrderCalculator();
    }

    /** Helper to create an array of nodes */
    private function _createNodes(array $names)
    {
        $nodes = array();
        foreach ($names as $name) {
            $node = new \Doctrine\ORM\Internal\CommitOrderNode($name, $this->_calc);
            $nodes[$name] = $node;
            $this->_calc->addNode($node->getClass(), $node);
        }
        return $nodes;
    }
    
    public function testCommitOrdering1()
    {
        $nodes = $this->_createNodes(array("node1", "node2", "node3", "node4", "node5"));
        
        $nodes['node1']->before($nodes['node2']);
        $nodes['node2']->before($nodes['node3']);
        $nodes['node3']->before($nodes['node4']);
        $nodes['node5']->before($nodes['node1']);
        
        shuffle($nodes); // some randomness

        $sorted = $this->_calc->getCommitOrder();
        
        // There is only 1 valid ordering for this constellation
        $correctOrder = array("node5", "node1", "node2", "node3", "node4");
        $this->assertSame($correctOrder, $sorted);
        
    }
}

<?php

#namespace Doctrine::ORM::Internal;

#use Doctrine::ORM::Mapping::ClassMetadata;

/**
 * A CommitOrderNode is a temporary wrapper around ClassMetadata objects
 * that is used to sort the order of commits.
 * 
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Internal_CommitOrderNode
{
    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;
    
    private $_traversalState;
    private $_predecessor;
    private $_status;
    private $_calculator;
    private $_relatedNodes = array();
    
    private $_discoveryTime;
    private $_finishingTime;
    
    private $_wrappedObj;
    private $_relationEdges = array();
    
    
    public function __construct($wrappedObj, Doctrine_Internal_CommitOrderCalculator $calc)
    {
        $this->_wrappedObj = $wrappedObj;
        $this->_calculator = $calc;
    }
    
    public function getClass()
    {
        return $this->_wrappedObj;
    }
    
    public function setPredecessor($node)
    {
        $this->_predecessor = $node;
    }
    
    public function getPredecessor()
    {
        return $this->_predecessor;
    }
    
    public function markNotVisited()
    {
        $this->_traversalState = self::NOT_VISITED;
    }
    
    public function markInProgress()
    {
        $this->_traversalState = self::IN_PROGRESS;
    }
    
    public function markVisited()
    {
        $this->_traversalState = self::VISITED;
    }
    
    public function isNotVisited()
    {
        return $this->_traversalState == self::NOT_VISITED;
    }
    
    public function isInProgress()
    {
        return $this->_traversalState == self::IN_PROGRESS;
    }
    
    public function visit()
    {
        $this->markInProgress();
        $this->setDiscoveryTime($this->_calculator->getNextTime());
        
        foreach ($this->getRelatedNodes() as $node) {
            if ($node->isNotVisited()) {
                $node->setPredecessor($this);
                $node->visit();
            }
            if ($node->isInProgress()) {
                // back edge => cycle
                //TODO: anything to do here?
            }
        }
        
        $this->markVisited();
        $this->_calculator->prependNode($this);
        $this->setFinishingTime($this->_calculator->getNextTime());
    }
    
    public function setDiscoveryTime($time)
    {
        $this->_discoveryTime = $time;
    }
    
    public function setFinishingTime($time)
    {
        $this->_finishingTime = $time;
    }
    
    public function getDiscoveryTime()
    {
        return $this->_discoveryTime;
    }
    
    public function getFinishingTime()
    {
        return $this->_finishingTime;
    }
    
    public function getRelatedNodes()
    {
        return $this->_relatedNodes;
    }
    
    /**
     * Adds a directed dependency (an edge). "$this -before-> $other".
     *
     * @param Doctrine_Internal_CommitOrderNode $node
     */
    public function before(Doctrine_Internal_CommitOrderNode $node)
    {
        $this->_relatedNodes[] = $node;
    }
}

?>
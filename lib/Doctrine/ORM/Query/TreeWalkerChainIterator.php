<?php
/**
 * doctrine2
 * User: shustrik
 * Date: 2/6/14
 * Time: 7:03 PM
 */

namespace Doctrine\ORM\Query;


class TreeWalkerChainIterator implements \Iterator, \ArrayAccess
{
    /**
     * @var TreeWalker[]
     */
    private $walkers = array();
    /**
     * @var TreeWalkerChain
     */
    private $treeWalkerChain;
    /**
     * @var
     */
    private $query;
    /**
     * @var
     */
    private $parserResult;

    public function __construct(TreeWalkerChain $treeWalkerChain, $query, $parserResult)
    {
        $this->treeWalkerChain = $treeWalkerChain;
        $this->query = $query;
        $this->parserResult = $parserResult;
    }

    /**
     * {@inheritdoc}
     */
    function rewind()
    {
        return reset($this->walkers);
    }

    /**
     * {@inheritdoc}
     */
    function current()
    {
        return $this->offsetGet(key($this->walkers));
    }

    /**
     * {@inheritdoc}
     */
    function key()
    {
        return key($this->walkers);
    }

    /**
     * {@inheritdoc}
     */
    function next()
    {
        next($this->walkers);

        return $this->offsetGet(key($this->walkers));
    }

    /**
     * {@inheritdoc}
     */
    function valid()
    {
        return key($this->walkers) !== null;
    }


    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->walkers[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return new $this->walkers[$offset](
                $this->query,
                $this->parserResult,
                $this->treeWalkerChain->getQueryComponents()
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->walkers[] = $value;
        } else {
            $this->walkers[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->walkers[$offset]);
        }
    }
}
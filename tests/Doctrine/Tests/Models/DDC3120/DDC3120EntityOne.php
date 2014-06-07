<?php

namespace Doctrine\Tests\Models\DDC3120;

/**
 * @Entity
 */
class DDC3120EntityOne extends \ArrayObject
{
   /**
    * @Id
    * @Column(type="integer", nullable=false)
    * @GeneratedValue(strategy="IDENTITY")
    */
    protected $entity_one_id;

    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    public function offsetSet($offset, $value) {
        $this->$offset = $value;
    }

    public function offsetGet($offset) {
        return $this->$offset;
    }

    public function offsetUnset($offset) {
        $this->$offset = null;
    }
}

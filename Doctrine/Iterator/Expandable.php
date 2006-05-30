<?php
Doctrine::autoload('Doctrine_Iterator');

class Doctrine_Iterator_Expandable extends Doctrine_Iterator {
    public function valid() {
        if($this->index < $this->count)
            return true;
        elseif($this->index == $this->count) {

            $coll  = $this->collection->expand($this->index);

            if($coll instanceof Doctrine_Collection) {
                $count = count($coll);
                if($count > 0) {
                    $this->keys   = array_merge($this->keys, $coll->getKeys());
                    $this->count += $count;
                    return true;
                }
            }

            return false;
        }
    }
}
?>

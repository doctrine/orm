<?php
abstract class Doctrine_Creator {
    protected $_table;
    /**
     * constructor
     *
     * @param Doctrine_Table $table
     */
    public function __construct(Doctrine_Table $table) {
        $this->_table = $_table;
    }
    

    abstract public function get();
}
class Doctrine_IdentityMap {
    private $identityMap = array();

    /**
     * first checks if record exists in identityMap, if not
     * returns a new record
     *
     * @return Doctrine_Record
     */
    public function get() {
        $key = $this->getIdentifier();

        if( ! is_array($key))
            $key = array($key);


        foreach($key as $k) {
            if( ! isset($this->data[$k]))
                throw new Doctrine_Exception("No primary key found");

            $id[] = $this->data[$k];
        }
        $id = implode(' ', $id);

        if(isset($this->identityMap[$id]))
            $record = $this->identityMap[$id];
        else {
            $record = new $this->name($this);
            $this->identityMap[$id] = $record;
        }
        $this->data = array();

        return $record;
    }
}
?>

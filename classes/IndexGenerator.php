<?php
class Doctrine_IndexGenerator {
    /**
     * @var string $name
     */
    private $name;
    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }
    /**
     * @param Doctrine_Record $record
     * @return mixed
     */
    public function getIndex(Doctrine_Record $record) {
        $value = $record->get($this->name);
        if($value === null)
            throw new Doctrine_Exception("Couldn't create collection index. Record field '".$this->name."' was null.");

        return $value;
    }
}
?> 

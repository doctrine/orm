<?php
class Doctrine_Entity_Exception extends Doctrine_Exception
{
    public static function unknownField($field)
    {
        return new self("Undefined field: '$field'.");    
    }
    
    public static function invalidValueForOneToManyReference()
    {
        return new self("Invalid value. The value of a reference in a OneToMany "
                . "association must be a Collection.");
    }
    
    public static function invalidValueForOneToOneReference()
    {
        return new self("Invalid value. The value of a reference in a OneToOne "
                . "association must be an Entity.");
    }
    
    public static function invalidValueForManyToManyReference()
    {
        return new self("Invalid value. The value of a reference in a ManyToMany "
                . "association must be a Collection.");
    }
}

?>
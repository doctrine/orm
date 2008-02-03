<?php
class CheckConstraintTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('price', 'decimal', 2, array('max' => 5000, 'min' => 100));
        $class->setColumn('discounted_price', 'decimal', 2);
        $class->check('price > discounted_price');
    }
}

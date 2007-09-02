<?php
class CheckConstraintTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('price', 'decimal', 2, array('max' => 5000, 'min' => 100));
        $this->hasColumn('discounted_price', 'decimal', 2);
        $this->check('price > discounted_price');
    }
}

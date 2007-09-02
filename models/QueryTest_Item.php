<?php
class QueryTest_Item extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('price', 'decimal');
        $this->hasColumn('quantity', 'integer');
    }
}


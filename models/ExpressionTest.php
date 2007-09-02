<?php
class ExpressionTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('amount', 'integer');
    }
}

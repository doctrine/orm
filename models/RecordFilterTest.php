<?php
class RecordFilterTest extends Doctrine_Record 
{
	public function setTableDefinition()
    {

        $this->hasColumn("name", "string", 200);
        $this->hasColumn("password", "string", 32);
    }
    public function setPassword($password) {
        return md5($password);
    }
    public function getName($name) {
        return strtoupper($name);
    }
}

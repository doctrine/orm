<?php
class RecordFilterTest extends Doctrine_Record 
{
	public static function initMetadata($class)
    {
        $class->setColumn("name", "string", 200);
        $class->setColumn("password", "string", 32);
    }
    public function setPassword($password) {
        return md5($password);
    }
    public function getName($name) {
        return strtoupper($name);
    }
}

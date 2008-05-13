<?php
class RecordHookTest extends Doctrine_Entity
{
    protected $_messages = array();

    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null, array('primary' => true));
    }
    public function preSave()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postSave()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preInsert()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postInsert()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preUpdate()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postUpdate()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preDelete()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postDelete()
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->_messages);
    }
}

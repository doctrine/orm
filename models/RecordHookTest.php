<?php
class RecordHookTest extends Doctrine_Record
{
    protected $_messages = array();

    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null, array('primary' => true));
    }
    public function preSave(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postSave(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preInsert(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postInsert(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preUpdate(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postUpdate(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preDelete(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postDelete(Doctrine_Event $event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->_messages);
    }
}

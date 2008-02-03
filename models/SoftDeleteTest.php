<?php
class SoftDeleteTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null, array('primary' => true));
        $class->setColumn('something', 'string', '25', array('notnull' => true, 'unique' => true));
        $class->setColumn('deleted', 'boolean', 1);
    }
    public function preDelete(Doctrine_Event $event)
    {
        $event->skipOperation();
    }
    public function postDelete(Doctrine_Event $event)
    {
        $this->deleted = true;

        $this->save();
    }
}

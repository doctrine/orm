<?php
class InheritanceEntityUser extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('inheritance_entity_user');

        $this->hasColumn('type', 'integer', 4, array (  'primary' => true,));
        $this->hasColumn('user_id', 'integer', 4, array (  'primary' => true,));
        $this->hasColumn('entity_id', 'integer', 4, array (  'primary' => true,));
    }

    public function setUp()
    {  
    }
}

class InheritanceDealUser extends InheritanceEntityUser
{
    public function setTableDefinition()
    {
        parent::setTableDefinition();

        $this->setTableName('inheritance_entity_user');

        $this->hasColumn('user_id', 'integer', 4, array (  'primary' => true,));
        $this->hasColumn('entity_id', 'integer', 4, array (  'primary' => true,));
    }

    public function setUp()
    {
        parent::setUp();

        $this->hasOne('InheritanceUser as User', array('local' => 'user_id', 'foreign' => 'id'));
        $this->hasOne('InheritanceDeal as Deal', array('local' => 'entity_id', 'foreign' => 'id'));
        $this->setInheritanceMap(array (
        'type' => 1,
        ));
    }
}
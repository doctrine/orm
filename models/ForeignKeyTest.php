<?php
class ForeignKeyTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', null);
        $this->hasColumn('code', 'integer', 4);
        $this->hasColumn('content', 'string', 4000);
        $this->hasColumn('parent_id', 'integer');

        $this->hasOne('ForeignKeyTest as Parent',
                       array('local'    => 'parent_id',
                             'foreign'  => 'id',
                             'onDelete' => 'CASCADE',
                             'onUpdate' => 'RESTRICT')
                       );

        $this->hasMany('ForeignKeyTest as Children',
                       'ForeignKeyTest.parent_id');

        $this->option('type', 'INNODB');

    }
}

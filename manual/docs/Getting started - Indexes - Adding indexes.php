<?php ?>
You can add indexes by simple calling Doctrine_Record::option('index', $definition) where $definition is the 
definition array. The structure of the definition array is as follows:
<div class='sql'>
<pre>
[   indexName1 => [col1 => [col1-options], ... , colN => [colN-options]
    indexName2 => ...
    indexNameN => ]
</pre>
</div>
<br \><br \>
An example of adding a simple index to field called 'name':
<br \><br \>
<?php
renderCode("<?php
class IndexTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        \$this->hasColumn('name', 'string');
    }
    public function setUp()
    {
        \$this->option('index', array('myindex' => 'name'));
    }
}
?>");
?>
<br \><br \>
An example of adding a multi-column index to field called 'name':
<br \><br \>
<?php
renderCode("<?php
class MultiColumnIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');
    }
    public function setUp()
    {
        \$this->option('index', array('myindex' => array('name', 'code')));
    }
}
?>");
?>
<br \><br \>
An example of adding a multiple indexes on same table:
<br \><br \>
<?php
renderCode("<?php
class MultipleIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');
        \$this->hasColumn('age', 'integer');
    }
    public function setUp()
    {
        \$this->option('index', 
                      array('myindex' => array('name', 'code')
                            'ageindex' => 'age')
                            );
    }
}
?>");
?>

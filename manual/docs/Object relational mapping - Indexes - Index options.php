<?php ?>
Doctrine offers many index options, some of them being db-specific. Here is a full list of availible options:
<div class='sql'>
<pre>        

sorting     => string('ASC' / 'DESC')      
        what kind of sorting does the index use (ascending / descending)

length      => integer
        index length (only some drivers support this)

primary     => boolean(true / false)        
        whether or not the index is primary index

type        => string('unique',         -- supported by most drivers
                      'fulltext',       -- only availible on Mysql driver
                      'gist',           -- only availible on Pgsql driver
                      'gin')            -- only availible on Pgsql driver
</pre>
</div>
<?php
renderCode("<?php
class MultipleIndexTest extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        \$this->hasColumn('name', 'string');
        \$this->hasColumn('code', 'string');
        \$this->hasColumn('age', 'integer');

        \$this->index('myindex', array(
                      'fields' => array(
                                  'name' =>
                                  array('sorting' => 'ASC',
                                        'length'  => 10),
                                  'code'),
                      'type' => 'unique',
                      ));
    }
}
?>");
?>

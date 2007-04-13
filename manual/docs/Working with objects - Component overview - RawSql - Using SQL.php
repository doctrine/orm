The rawSql component works in much same way as Zend_Db_Select. You may use method overloading like $q->from()->where() or just use
$q->parseQuery(). There are some differences though:



1. In Doctrine_RawSql component you need to specify all the mapped table columns in curly brackets {} this is used for smart column aliasing.



2. When joining multiple tables you need to specify the component paths with addComponent() method



The following example represents a very simple case where no addComponent() calls are needed. 
Here we select all entities from table entity with all the columns loaded in the records.


<code type="php">
$query = new Doctrine_RawSql($conn);

$query->parseQuery("SELECT {entity.name} FROM entity");
        
$entities = $query->execute();
</code>

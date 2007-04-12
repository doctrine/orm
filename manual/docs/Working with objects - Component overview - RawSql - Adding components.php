The following example represents a bit harder case where we select all entities and their associated phonenumbers using a left join. Again we
wrap all the columns in curly brackets but we also specify what tables associate to which components.
<br \> <br \>
First we specify that table entity maps to record class 'Entity'
<br \><br \>
Then we specify that table phonenumber maps to Entity.Phonenumber (meaning phonenumber associated with an entity)

<code type="php">
$query = new Doctrine_RawSql($conn);

$query->parseQuery("SELECT {entity.*}, {phonenumber.*} 
                   FROM entity 
                   LEFT JOIN phonenumber 
                   ON phonenumber.entity_id = entity.id");

$query->addComponent("entity", "Entity");
$query->addComponent("phonenumber", "Entity.Phonenumber");

$entities = $query->execute();
</code>

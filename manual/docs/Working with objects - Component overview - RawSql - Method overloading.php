
<code type="php">
$query = new Doctrine_RawSql($conn);

$query->select('{entity.name}')
      ->from('entity');

$query->addComponent("entity", "User");

$coll = $query->execute();
</code>

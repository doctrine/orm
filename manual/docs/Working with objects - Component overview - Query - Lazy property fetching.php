
<code type="php">

// retrieve all users with only their properties id and name loaded

$users = $conn->query("FROM User(id, name)");
</code>


<code type="php">
$conn->beginTransaction();

$user = new User();
$user->name = 'New user';
$user->save();

$user = $conn->getTable('User')->find(5);
$user->name = 'Modified user';
$user->save();


$pending = $conn->getInserts(); // an array containing one element

$pending = $conn->getUpdates(); // an array containing one element

$conn->commit(); // all the queries are executed here
</code>

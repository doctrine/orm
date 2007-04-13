When accessing single elements of the collection and those elements (records) don't exist Doctrine auto-adds them. 



In the following example
we fetch all users from database (there are 5) and then add couple of users in the collection.



As with PHP arrays the indexes start from zero.

<code type="php">
$users = $table->findAll();

print count($users); // 5

$users[5]->name = "new user 1";
$users[6]->name = "new user 2";
</code>

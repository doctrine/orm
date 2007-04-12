
<code type="php">
$q = new Doctrine_Query();

$q->from('User')->where('User.Phonenumber.phonenumber.like(?,?)');

$users = $q->execute(array('%123%', '456%'));
</code>

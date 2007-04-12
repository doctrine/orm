
<code type="php">
$q = new Doctrine_Query();

$q->from('User')->where('User.Phonenumber.phonenumber.regexp(?,?)');

$users = $q->execute(array('[123]', '^[3-5]'));
</code>

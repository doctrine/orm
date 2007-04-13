When accessing related records and if those records do not exists Doctrine automatically creates new records.

<code type="php">
// NOTE: related record have always the first letter in uppercase
$email = $user->Email;

$email->address = 'jackdaniels@drinkmore.info';

$user->save();

// alternative:

$user->Email->address = 'jackdaniels@drinkmore.info';

$user->save();
</code>

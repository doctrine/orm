You can update the related records by calling save for each related object / collection individually or by calling
save on the object that owns the other objects. You can also call Doctrine_Connection::flush which saves all pending objects.

<code type="php">
$user->Email['address'] = 'koskenkorva@drinkmore.info';

$user->Phonenumber[0]->phonenumber = '123123';

$user->save();

// saves the email and phonenumber
</code>

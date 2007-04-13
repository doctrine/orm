You can delete related records individually be calling delete() on each record. If you want to delete a whole record graph just call
delete on the owner record.

<code type="php">
$user->Email->delete();

$user->Phonenumber[3]->delete();

// deleting user and all related objects:

$user->delete();
</code>

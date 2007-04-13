You can retrieve related records by the very same Doctrine_Record methods you've already propably used for accessing record properties.
When accessing related record you just simply use the class names. 

<code type="php">
print $user->Email['address'];

print $user->Phonenumber[0]->phonenumber;

print $user->Group[0]->name;
</code>

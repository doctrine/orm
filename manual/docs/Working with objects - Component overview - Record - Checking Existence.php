
<code type="php">
$record = new User();

$record->exists(); // false

$record->name = 'someone';
$record->save();

$record->exists(); // true
</code>

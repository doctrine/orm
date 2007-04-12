Sometimes you may want to serialize your record objects (possibly for caching purposes). Records can be serialized, 
but remember: Doctrine cleans all relations, before doing this. So remember to persist your objects into database before serializing them.

<code type="php">
$string = serialize($user);

$user = unserialize($string);
</code>

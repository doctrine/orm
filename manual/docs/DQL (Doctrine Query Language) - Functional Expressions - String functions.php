* The //CONCAT// function returns a string that is a concatenation of its arguments. In the example above we 
map the concatenation of users firstname and lastname to a value called name


<code type="php">
$q = new Doctrine_Query();

$users = $q->select('CONCAT(u.firstname, u.lastname) name')->from('User u')->execute();

foreach($users as $user) {
    // here 'name' is not a property of $user,
    // its a mapped function value
    print $user->name;
}
?>
</code>




* The second and third arguments of the //SUBSTRING// function denote the starting position and length of
the substring to be returned. These arguments are integers. The first position of a string is denoted by 1. 
The //SUBSTRING// function returns a string.


<code type="php">
$q = new Doctrine_Query();

$users = $q->select('u.name')->from('User u')->where("SUBSTRING(u.name, 0, 1) = 'z'")->execute();

foreach($users as $user) {
    print $user->name;
}
?>
</code>
 



* The //TRIM// function trims the specified character from a string. If the character to be trimmed is not
specified, it is assumed to be space (or blank). The optional trim_character is a single-character string
literal or a character-valued input parameter (i.e., char or Character)[30]. If a trim specification is
not provided, BOTH is assumed. The //TRIM// function returns the trimmed string.


<code type="php">
$q = new Doctrine_Query();

$users = $q->select('u.name')->from('User u')->where("TRIM(u.name) = 'Someone'")->execute();

foreach($users as $user) {
    print $user->name;
}
?>
</code>    


* The //LOWER// and //UPPER// functions convert a string to lower and upper case, respectively. They return a
string. 



<code type="php">
$q = new Doctrine_Query();

$users = $q->select('u.name')->from('User u')->where("LOWER(u.name) = 'someone'")->execute();

foreach($users as $user) {
    print $user->name;
}
?>
</code>  


* The //LOCATE// function returns the position of a given string within a string, starting the search at a specified
position. It returns the first position at which the string was found as an integer. The first argument
is the string to be located; the second argument is the string to be searched; the optional third argument
is an integer that represents the string position at which the search is started (by default, the beginning of
the string to be searched). The first position in a string is denoted by 1. If the string is not found, 0 is
returned.




* The //LENGTH// function returns the length of the string in characters as an integer.




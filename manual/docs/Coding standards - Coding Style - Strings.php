
* When a string is literal (contains no variable substitutions), the apostrophe or "single quote" must always used to demarcate the string:


* When a literal string itself contains apostrophes, it is permitted to demarcate the string with quotation marks or "double quotes". This is especially encouraged for SQL statements:


* Variable substitution is permitted using the following form:



* Strings may be concatenated using the "." operator. A space must always be added before and after the "." operator to improve readability:



* When concatenating strings with the "." operator, it is permitted to break the statement into multiple lines to improve readability. In these cases, each successive line should be padded with whitespace such that the "."; operator is aligned under the "=" operator:



<code type="php">
// literal string
$string = 'something';

// string contains apostrophes
$sql = "SELECT id, name FROM people WHERE name = 'Fred' OR name = 'Susan'";

// variable substitution
$greeting = "Hello $name, welcome back!";

// concatenation
$framework = 'Doctrine' . ' ORM ' . 'Framework';

// concatenation line breaking

$sql = "SELECT id, name FROM user "
     . "WHERE name = ? "
     . "ORDER BY name ASC";

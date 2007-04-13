Syntax:

string_expression [NOT] LIKE pattern_value [ESCAPE escape_character]





The string_expression must have a string value. The pattern_value is a string literal or a string-valued
input parameter in which an underscore (_) stands for any single character, a percent (%) character
stands for any sequence of characters (including the empty sequence), and all other characters stand for
themselves. The optional escape_character is a single-character string literal or a character-valued
input parameter (i.e., char or Character) and is used to escape the special meaning of the underscore
and percent characters in pattern_value.



Examples:



* address.phone LIKE ‘12%3’ is true for '123' '12993' and false for '1234'
* asentence.word LIKE ‘l_se’ is true for ‘lose’ and false for 'loose'
* aword.underscored LIKE ‘\_%’ ESCAPE '\' is true for '_foo' and false for 'bar'
* address.phone NOT LIKE ‘12%3’ is false for '123' and '12993' and true for '1234'



If the value of the string_expression or pattern_value is NULL or unknown, the value of the LIKE
expression is unknown. If the escape_characteris specified and is NULL, the value of the LIKE expression
is unknown.

<code type="php">

// finding all users whose email ends with '@gmail.com'
$users = $conn->query("FROM User u, u.Email e WHERE e.address LIKE '%@gmail.com'");

// finding all users whose name starts with letter 'A'
$users = $conn->query("FROM User u WHERE u.name LIKE 'A%'");
</code>

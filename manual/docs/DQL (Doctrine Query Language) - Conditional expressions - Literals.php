<b>Strings</b><br \>

A string literal is enclosed in single quotes—for example: 'literal'. A string literal that includes a single
quote is represented by two single quotes—for example: 'literal''s'.
<div class='sql'>
<pre>
FROM User WHERE User.name = 'Vincent'
</pre>
</div>

<b>Integers</b><br \>
Integer literals support the use of PHP integer literal syntax.
<div class='sql'>
<pre>
FROM User WHERE User.id = 4
</pre>
</div>

<b>Floats</b><br \>
Float literals support the use of PHP float literal syntax.
<div class='sql'>
<pre>
FROM Account WHERE Account.amount = 432.123
</pre>
</div>

<br \>
<b>Booleans</b><br \>
The boolean literals are true and false.

<div class='sql'>
<pre>
FROM User WHERE User.admin = true

FROM Session WHERE Session.is_authed = false
</pre>
</div>

<br \>
<b>Enums</b><br \>
The enumerated values work in the same way as string literals.

<div class='sql'>
<pre>
FROM User WHERE User.type = 'admin'
</pre>
</div>

<br \>
Predefined reserved literals are case insensitive, although its a good standard to write them in uppercase.


**Strings**


A string literal is enclosed in single quotes—for example: 'literal'. A string literal that includes a single
quote is represented by two single quotes—for example: 'literal''s'.
<code>
FROM User WHERE User.name = 'Vincent'
</code>

**Integers**

Integer literals support the use of PHP integer literal syntax.
<code>
FROM User WHERE User.id = 4
</code>

**Floats**

Float literals support the use of PHP float literal syntax.
<code>
FROM Account WHERE Account.amount = 432.123
</code>



**Booleans**

The boolean literals are true and false.

<code>
FROM User WHERE User.admin = true

FROM Session WHERE Session.is_authed = false
</code>



**Enums**

The enumerated values work in the same way as string literals.

<code>
FROM User WHERE User.type = 'admin'
</code>



Predefined reserved literals are case insensitive, although its a good standard to write them in uppercase.


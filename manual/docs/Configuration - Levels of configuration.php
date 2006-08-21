Doctrine has a three-level configuration structure. You can set configuration attributes in global, connection and table level.
If the same attribute is set on both lower level and upper level, the uppermost attribute will always be used. So for example
if user first sets default fetchmode in global level to Doctrine::FETCH_BATCH and then sets 'example' table fetchmode to Doctrine::FETCH_LAZY,
the lazy fetching strategy will be used whenever the records of 'example' table are being fetched.

<br \><br \>
<li> Global level
    <ul>
        The attributes set in global level will affect every connection and every table in each connection.
    </ul>
<li> Connection level
    <ul>
        The attributes set in connection level will take effect on each table in that connection.
    </ul>
<li> Table level
    <ul>
        The attributes set in table level will take effect only on that table.
    </ul>

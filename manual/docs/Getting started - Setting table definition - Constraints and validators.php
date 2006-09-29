Following attributes are availible for columns
<table>

    <tr>
        <td>
            <b class='title' valign='top'>name</b>
        </td>
        <td>
            <b class='title' valign='top'>args</b>
        </td>
        <td>
            <b class='title'>description</b>
        </td>
    </tr>
    <tr>
        <td colspan=3>
            <hr>
        </td>

    </tr>
    <tr>
        <td colspan=3>
            &raquo;&raquo; Basic attributes
            <hr class='small'>
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>primary</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Defines column as a primary key column.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>autoincrement</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Defines column as autoincremented column. If the underlying database doesn't support autoincrementation natively its emulated with triggers and sequence tables.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>default</b>
        </td>
        <td class='title' valign='top'>
            mixed default
        </td>
        <td class='title' valign='top'>
            Sets <i>default</i> as an application level default value for a column. When default value has been set for a column every time a record is created the specified column has the <i>default</i> as its value.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>enum</b>
        </td>
        <td class='title' valign='top'>
            array enum
        </td>
        <td class='title' valign='top'>
            Sets <i>enum</i> as an application level enum value list for a column.
        </td>
    </tr>
    <tr>
        <td colspan=3>
            &raquo;&raquo; Basic validators
            <hr class='small'>
        </td>

    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>unique</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Acts as database level unique constraint. Also validates that the specified column is unique.
        </td>
    </tr>    
    <tr>
        <td class='title' valign='top'>
            <b>nospace</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Nospace validator. This validator validates that specified column doesn't contain any space/newline characters. <br />

        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>notblank</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Notblank validator. This validator validates that specified column doesn't contain only space/newline characters. Useful in for example comment posting applications
            where users are not allowed to post empty comments.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>notnull</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Acts as database level notnull constraint as well as notnull validator for the specified column.
        </td>
    </tr>
    <tr>
        <td colspan=3>
            &raquo;&raquo; Advanced validators
            <hr class='small'>
        </td>

    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>email</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Email validator. Validates that specified column is a valid email address.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>date</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Date validator.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>range</b>
        </td>
        <td class='title' valign='top'>
            array(min, max)
        </td>
        <td class='title' valign='top'>
            Range validator. Validates that the column is between <i>min</i> and <i>max</i>.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>country</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Country code validator validates that specified column has a valid country code.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>regexp </b>
        </td>
        <td class='title' valign='top'>
            string regexp
        </td>
        <td class='title' valign='top'>
            Regular expression validator validates that specified column matches <i>regexp</i>.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>ip</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Ip validator validates that specified column is a valid internet protocol address.
        </td>
    </tr>
    <tr>
        <td class='title' valign='top'>
            <b>usstate</b>
        </td>
        <td class='title' valign='top'>
            bool true
        </td>
        <td class='title' valign='top'>
            Usstate validator validates that specified column is a valid usstate.
        </td>
    </tr>
</table>


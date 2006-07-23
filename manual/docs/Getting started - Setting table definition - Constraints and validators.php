Following data types and constraints are availible in doctrine
    <ul>
    <li /><b> unique</b>
        <ul> Acts as database level unique constraint. Also validates that the specified column is unique.
        </ul>
    <li /><b> nospace</b>
        <ul> Nospace validator. This validator validates that specified column doesn't contain any space/newline characters. <br />
        </ul>
    <li /><b> notblank</b>
        <ul> Notblank validator. This validator validates that specified column doesn't contain only space/newline characters. Useful in for example comment posting applications
            where users are not allowed to post empty comments. <br />
        </ul>
    <li /><b> notnull</b>
        <dd /> Acts as database level notnull constraint as well as notnull validator for the specified column.<br />
    <li /><b> email</b>
        <dd /> Email validator. Validates that specified column is a valid email address.
    <li /><b> date</b>
        <dd /> Date validator.
    <li /><b> range:[args]</b>
        <dd /> Range validator, eg range:1-32
    <li /><b> enum:[args]</b>
        <dd /> Enum validator, eg enum:city1-city2-city3
    <li /><b> country</b>
        <ul> Country code validator validates that specified column has a valid country code.
        </ul>
    <li /><b> regexp:[args]</b>
        <ul> Regular expression validator validates that specified column matches a regular expression, eg regexp:[John]
        </ul>
    <li /><b> ip</b>
        <ul> Ip validator validates that specified column is a valid internet protocol address.
        </ul>
    <li /><b> usstate</b>
        <ul> Usstate validator validates that specified column is a valid usstate.
        </ul>
    </ul>

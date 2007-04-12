All variables must satisfy the following conditions:

<ul>
<li \>Variable names may only contain alphanumeric characters. Underscores are not permitted. Numbers are permitted in variable names but are discouraged.
</ul>

<ul>
<li \>Variable names must always start with a lowercase letter and follow the "camelCaps" capitalization convention.
</ul>

<ul>
<li \>Verbosity is encouraged. Variables should always be as verbose as practical. Terse variable names such as "$i" and "$n" are discouraged for anything other than the smallest loop contexts. If a loop contains more than 20 lines of code, the variables for the indices need to have more descriptive names.
</ul>

<ul>
<li \>Within the framework certain generic object variables should always use the following names:
    <ul>

    <li \> Doctrine_Connection  -> <i>$conn</i>
    <li \> Doctrine_Collection  -> <i>$coll</i>
    <li \> Doctrine_Manager     -> <i>$manager</i>
    <li \> Doctrine_Query       -> <i>$query</i>
    <li \> Doctrine_Db          -> <i>$db</i>

    </ul>
    There are cases when more descriptive names are more appropriate (for example when multiple objects of the same class are used in same context),
    in that case it is allowed to use different names than the ones mentioned.
</ul>



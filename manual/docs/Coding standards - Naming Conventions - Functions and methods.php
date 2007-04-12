<ul>
<li>Function names may only contain alphanumeric characters. Underscores are not permitted. Numbers are permitted in function names but are discouraged.
</ul>

<ul>
<li>Function names must always start with a lowercase letter. When a function name consists of more than one word, the first letter of each new word must be capitalized. This is commonly called the "studlyCaps" or "camelCaps" method.
</ul>

<ul>
<li>Verbosity is encouraged. Function names should be as verbose as is practical to enhance the understandability of code.
</ul>

<ul>
<li>For object-oriented programming, accessors for objects should always be prefixed with either "get" or "set". This applies to all classes except for Doctrine_Record which has some accessor methods prefixed with 'obtain' and 'assign'. The reason
for this is that since all user defined ActiveRecords inherit Doctrine_Record, it should populate the get / set namespace as little as possible. 
</ul>

<ul>
<li>Functions in the global scope ("floating functions") are NOT permmitted. All static functions should be wrapped in a static class.
</ul>


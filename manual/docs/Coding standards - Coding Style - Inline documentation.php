Documentation Format
<ul>
<li \>All documentation blocks ("docblocks") must be compatible with the phpDocumentor format. Describing the phpDocumentor format is beyond the scope of this document. For more information, visit: http://phpdoc.org/
</ul>

Methods:
<ul>
<li \>Every method, must have a docblock that contains at a minimum: 
</ul>

<ul>
<li \>A description of the function
</ul>

<ul>
<li \>All of the arguments
</ul>

<ul>
<li \>All of the possible return values
</ul>


<ul>
<li \>It is not necessary to use the "@access" tag because the access level is already known from the "public", "private", or "protected" construct used to declare the function.
</ul>

<ul>
<li \>If a function/method may throw an exception, use @throws:
</ul>


<ul>
<li \>@throws exceptionclass [description]
</ul>


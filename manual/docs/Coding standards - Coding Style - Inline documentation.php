Documentation Format

* All documentation blocks ("docblocks") must be compatible with the phpDocumentor format. Describing the phpDocumentor format is beyond the scope of this document. For more information, visit: http://phpdoc.org/


Methods:

* Every method, must have a docblock that contains at a minimum: 



* A description of the function



* All of the arguments



* All of the possible return values




* It is not necessary to use the "@access" tag because the access level is already known from the "public", "private", or "protected" construct used to declare the function.



* If a function/method may throw an exception, use @throws:




* @throws exceptionclass [description]



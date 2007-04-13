Following rules must apply to all constants used within Doctrine framework:

*  Constants may contain both alphanumeric characters and the underscore.

*  Constants must always have all letters capitalized.

*  For readablity reasons, words in constant names must be separated by underscore characters. For example, ATTR_EXC_LOGGING is permitted but ATTR_EXCLOGGING is not.

*  Constants must be defined as class members by using the "const" construct. Defining constants in the global scope with "define" is NOT permitted.


<code type="php">
class Doctrine_SomeClass {
    const MY_CONSTANT = 'something';
}
print Doctrine_SomeClass::MY_CONSTANT;
</code>

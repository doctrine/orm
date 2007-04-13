For files that contain only PHP code, the closing tag ("?>") is never permitted. It is not required by PHP. Not including it prevents trailing whitespace from being accidentally injected into the output.



IMPORTANT: Inclusion of arbitrary binary data as permitted by __HALT_COMPILER() is prohibited from any Doctrine framework PHP file or files derived from them. Use of this feature is only permitted for special installation scripts. 



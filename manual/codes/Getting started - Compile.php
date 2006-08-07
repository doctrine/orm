Doctrine is quite big framework and usually dozens of files are being included on each request.
This brings a lot of overhead. In fact these file operations are as time consuming as sending multiple queries to database server.
The clean separation of class per file works well in developing environment, however when project
goes commercial distribution the speed overcomes the clean separation of class per file -convention.

Doctrine offers method called compile() to solve this issue. The compile method makes a single file of most used
Doctrine components which can then be included on top of your script. By default the file is created into Doctrine root by the name
Doctrine.compiled.php.


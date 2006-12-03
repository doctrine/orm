<?php
require_once("highlight.php");
error_reporting(E_ALL);
include("top.php");
$h = new PHP_Highlight();

function render($title,$t,$e) {
    global $h;
    print $e." <a name=\"$e\"><u>".$t."</u></a><br><br>\n";
    $c = "";

    if(file_exists("docs/$e.php")) {
        rename("docs/$e.php","docs/$title - $t.php");
    }
    if(file_exists("docs/$t.php")) {
        rename("docs/$t.php","docs/$title - $t.php");
    }
    if(file_exists("docs/$title - $t.php")) {
        $c = file_get_contents("docs/$title - $t.php");
        if(substr($c,0,5) == "<?php") {
            include("docs/$title - $t.php");
        } else
            print $c."<br><br>";
    }
    $c = "";
    if(file_exists("codes/$e.php")) {
        rename("codes/$e.php","codes/$title - $t.php");
    }
    if(file_exists("codes/$t.php")) {
        rename("codes/$t.php","codes/$title - $t.php");
    }
    if(file_exists("codes/$title - $t.php")) {
        print "<table border=1 class='dashed' cellpadding=0 cellspacing=0>";
        print "<tr><td>";
         $c = file_get_contents("codes/$title - $t.php");
        $c = trim($c);

        $h->loadString($c);
        print $h->toHtml();
        print "</td></tr>";
        print "</table>";
    }
    print "<br>";
}

function render_block($name) {

    if(file_exists("docs/$name.php")) {
        $c = file_get_contents("docs/$name.php");
        if(substr($c,0,5) == "<?php") {
            include("docs/$name.php");
        } else {
            print $c."<br><br>";
        }
    }
    if(file_exists("codes/$name.php")) {
        $c = file_get_contents("codes/$name.php");
        $c = trim($c);
        renderCode($c);
    }
}

function renderCode($c = null) {
    global $h;
    if( ! empty($c)) {

        $h->loadString($c);

        print "<table width=500 border=1 class='dashed' cellpadding=0 cellspacing=0>";
        print "<tr><td><b>";

        $h->toHtml();
        print "</b></td></tr>";
        print "</table>";
    }
}
function array2path($array, $path = '') {
   $arrayValues = array();

   $index = 1;
   foreach ($array as $k => $value) {
       $p = ($path !== '')?$path.".".$index:$index;

       if (is_scalar($value) || is_resource($value)) {
             $arrayValues[$p] = $value;
       } elseif (is_array($value)) {
             $arrayValues[$p] = $k;
             $arrayValues = $arrayValues + array2path($value, $p);
       }
       $index++;
   }

   return $arrayValues;
}
$menu = array("Getting started" =>
                        array(
                        "Requirements",
                        "Installation",
                        "Compiling",
                        "Starting new project",
                        "Setting table definition" => array(
                                        "Introduction",
                                        "Table and class naming",
                                        "Field(Column) naming",
                                        "Data types and lengths",
                                        "Constraints and validators",
                                        "Default values",
                                        "Enum emulation",

                                        ),

                        "Record identifiers" => array(
                                        "Introduction",
                                        "Autoincremented",
                                        "Natural",
                                        "Composite",
                                        "Sequential")
                        ),

           "Schema reference" =>
                        array(
                        "Data types" => array(
                                        "Introduction",
                                        "Type modifiers",
                                        "Boolean",
                                        "Integer",
                                        "Float",
                                        "String",
                                        "Array",
                                        "Object",
                                        "Blob",
                                        "Clob",
                                        "Timestamp",
                                        "Time",
                                        "Date",
                                        "Enum",
                                        "Gzip",
                        ),

                        ),
           "Basic Components" =>
                        array(
                        "Manager"
                                => array("Introduction",
                                         "Opening a new connection",
                                         "Managing connections"),
                        "Record"
                                => array("Introduction",
                                         "Creating new records",
                                         "Retrieving existing records",
                                         "Accessing properties",
                                         "Updating records",
                                         "Deleting records",
                                         "Getting record state",
                                         "Getting object copy",
                                         "Serializing",
                                         "Checking Existence",
                                         "Callbacks"),
                        "Connection"
                                => array("Introduction",
                                         "Available drivers",
                                         "Getting a table object",
                                         "Flushing the connection",
                                         "Querying the database",
                                         "Getting connection state"),

                        "Collection"
                                => array("Introduction",
                                         "Accessing elements",
                                         "Adding new elements",
                                         "Getting collection count",
                                         "Saving the collection",
                                         "Deleting collection",
                                         //"Fetching strategies",
                                         "Key mapping",
                                         "Loading related records",
                                         "Collection expanding",
                                         ),

                        "Table" => array("Introduction",
                                         "Getting table information",
                                         "Finder methods",
                                         "Custom table classes",
                                         "Custom finders",
                                         "Getting relation objects"),

                        "Query" => array("Introduction",
                                         "FROM - selecting tables",
                                         "LIMIT and OFFSET - limiting the query results",
                                         "WHERE - setting query conditions",
                                         "ORDER BY - sorting query results",
                                         //"Fetching strategies",
                                         //"Lazy property fetching",
                                         "Method overloading",
                                         "Relation operators",
                                         "Bound parameters",
                                         "Aggregate functions",
                                         "DQL - SQL conversion"),
                        "RawSql" => array(
                                         "Introduction",
                                         "Using SQL",
                                         "Adding components",
                                         "Method overloading"),
                        "Db"     => array(
                                         "Introduction",
                                         "Connecting to a database",
                                         "Using event listeners",
                                         "Chaining listeners"),
                                         /**
                        "Statement - <font color='red'>UNDER CONSTRUCTION</font>" => array("Introduction",
                                             "Setting parameters",
                                             "Getting parameters",
                                             "Getting row count",
                                             "Executing the statement"),
                                            */
                        "Exceptions" => array(
                                    "Overview",
                                    "List of exceptions"
                                    )
                        ),
           "Mapping object relations" =>
                        array(
                        "Introduction",
                        "Composites and aggregates",
                        "Relation aliases",
                        "Foreign key associations" => array(
                                        "One-to-One",
                                        "One-to-Many, Many-to-One",
                                        "Tree structure"),

                        "Join table associations" => array(
                                        "One-to-One",
                                        "One-to-Many, Many-to-One",
                                        "Many-to-Many",
                                        "Self-referencing"),
                        "Dealing with relations" => array(
                                        "Creating related records",
                                        "Retrieving related records",
                                        "Updating related records",
                                        "Deleting related records",
                                        "Working with associations"),
                        "Inheritance" =>
                                        array("One table many classes",
                                        "One table one class",
                                        "Column aggregation"
                                        ),
                        ),
           "Configuration" =>
                        array(
                        "Introduction",
                        "Levels of configuration",
                        "Setting attributes"    => array(
                            "Portability",
                            "Identifier quoting",
                            "Table creation",
                            "Fetching strategy",
                            "Batch size",
                            "Session lockmode",
                            "Event listener",
                            "Validation",
                            "Offset collection limit"
                            )
                        ),



           "Advanced components" => array(
                      "Eventlisteners" =>
                                      array(
                                      "Introduction",
                                      "Creating new listener",
                                      "List of events",
                                      "Listening events",
                                      "Chaining",
                                      "AccessorInvoker",
                                      "Creating a logger",
                                      ),
                      "Validators" => array(
                                      "Introduction",
                                      "More Validation",
                                      "Valid or Not Valid",
                                      "List of predefined validators"
                                      ),
                      "View"        => array(
                                      "Intoduction",
                                      "Managing views",
                                      "Using views"
                                      ),
                      "Cache"       => array(
                                      "Introduction",
                                      "Query cache"),

                      "Locking Manager" => array(
                                        "Introduction",
                                        "Examples",
                                        "Planned",
                                        "Technical Details",
                                        "Maintainer"),
                      /**
                      "Debugger" => array(
                                        "Introduction",
                                        "Debugging actions"),
                      "Library" => array(
                                        "Introduction",
                                        "Using library functions"),
                      "Iterator" => array(
                                        "Introduction",
                                        "BatchIterator",
                                        "ExpandableIterator",
                                        "OffsetIterator")
                        */
                    ),

           "DQL (Doctrine Query Language)" =>

                            array(
                                  'Introduction',
                                  'SELECT queries',
                                  'UPDATE queries',
                                  'DELETE queries',
                                  'FROM clause',
                                  'WHERE clause',
                                  'Conditional expressions' =>
                                        array('Literals',
                                              'Input parameters',

                                              'Operators and operator precedence',
                                              'Between expressions',
                                              'In expressions',
                                              'Like Expressions',
                                              'Null Comparison Expressions',
                                              'Empty Collection Comparison Expressions',
                                              'Collection Member Expressions',
                                              'Exists Expressions',
                                              'All and Any Expressions',
                                              'Subqueries'),
                                  'Functional Expressions' =>
                                        array('String functions',
                                              'Arithmetic functions',
                                              'Datetime functions',
                                              'Collection functions'),

                                  'GROUP BY, HAVING clauses',
                                  'ORDER BY clause',
                                  'LIMIT and OFFSET clauses' =>
                                        array('Introduction',
                                              'Driver portability',
                                              'The limit-subquery-algorithm',
                                        ),

                                  'Examples',
                                  'BNF'),

           "Transactions" => array(
                        "Introduction",
                        "Unit of work",
                        "Nesting",
                        "Savepoints",
                        "Locking strategies" =>
                            array("Pessimistic locking",
                                  "Optimistic locking"),

                        "Lock modes",
                        "Isolation levels",
                        "Deadlocks",

                        ),
           "Native SQL" => array(
                        "Scalar queries",
                        "Component queries",
                        "Fetching multiple components",
                        ),
            /**
            "Developer components" => array(
                        "DataDict"  => array(
                                        "Introduction",
                                        "Usage"
                                        ),
                        "IndexGenerator" =>
                                       array(
                                        "Introduction",
                                        "Usage"),
                        "Relation"  => array(
                                        "Introduction",
                                        "Types of relations",
                                        ),
                        "Null"      => array(
                                        "Introduction",
                                        "Extremely fast null value checking"
                                        ),
                        "Access"    => array(
                                        "Introduction",
                                        "Usage"
                                        ),
                        "Configurable" => array(
                                        "Introduction",
                                        "Usage"
                                        ),
                        ),
            */
            /**
            "Improving performance" => array(
                            "Introduction",
                            "Data types" =>
                                array(
                                    "Enum",
                                ),
                            "Primary keys" => array(
                                "When to use surrogate primary keys",
                            ),
                            "Constraints" => array(
                                "General tips",
                                "Foreign keys",
                                "Triggers",
                            ),
                            "Data manipulation" => array(
                                "INSERT queries",
                                "UPDATE queries",
                                "DELETE queries",
                            ),
                            "Indexes" => array(
                                "General tips",
                                "Using compound indexes",
                            ),
                            "Transactions" => array(
                                "General tips",
                                "Locks",
                                "Isolation",
                            ),
                            "Data fetching" => array(
                                "General tips",
                            ),
                            "Normalization" => array(

                            ),
                            "Caching" => array(
                                "General tips"
                            ),
                            "Performance monitoring" => array( "Using the database profiler") 
                            
                            ),
            */
            "Technology" => array(
                "Architecture",
                "Design patterns used",
                "Speed",
                "Internal optimizations" =>

                        array("DELETE",
                              "INSERT",
                              "UPDATE"),
                ),
            "Real world examples" => array("User management system","Forum application","Album lister"),
            
            "Coding standards" => array(
                            "Overview" => 
                                array(
                                    "Scope",
                                    "Goals"
                                    ),
                            "PHP File Formatting" => array(
                                    "General",
                                    "Indentation",
                                    "Maximum line length",
                                    "Line termination"
                                    ),

                            "Naming Conventions" => array(
                                    "Classes",
                                    "Interfaces",
                                    "Filenames",
                                    "Functions and methods",
                                    "Variables",
                                    "Constants",
                                    "Record columns",
                                    ),


                            "Coding Style" => array(
                                    "PHP code demarcation",
                                    "Strings",
                                    "Arrays",
                                    "Classes",
                                    "Functions and methods",
                                    "Control statements",
                                    "Inline documentation"
                                    ),
                                )
            );


?>


<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td align="left" valign="top">
            <table width="100%" cellspacing=1 cellpadding=1>
            <tr>
                <td colspan=2 bgcolor="white">
                <img src="images/logo.jpg" align="left"><b class="title">Doctrine - PHP Data Persistence and ORM Tool</b>
                <hr>
                </td>
            </tr>
            <tr>
                <td bgcolor="white" valign="top">

                <?php
                if( ! isset($_REQUEST["index"])) {
                $i = 1;
                $missing = array();
                $missing[0] = 0;
                $missing[1] = 0;
                print "<dl>\n";
                foreach($menu as $title => $titles) {
                    print "<dt>" . $i . ". <a href=\"".$_SERVER['PHP_SELF']."?index=$i#$i\">".$title."</a></dt>\n";
                    print "<dd><dl>";
                    $i2 = 1;
                    foreach($titles as $k => $t) {
                        $e = "$i.".$i2."";
                        if(is_array($t)) {
                            print "<dt>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i.$i2#$e\">".$k."</a><dt>\n";

                            $i3 = 1;
                            print "<dd><dl>";
                            foreach($t as $k2 => $v2) {
                                $str = "";
                                if( ! file_exists("docs/$title - $k - $v2.php")) {
                                    $missing[0]++;
                                    $str .= " [ <font color='red'>doc</font> ] ";
                                    //touch("docs/$title - $k - $v2.php");
                                }
                                if( ! file_exists("codes/$title - $k - $v2.php")) {
                                    $missing[1]++;
                                    $str .= " [ <font color='red'>code</font> ] ";
                                    //touch("codes/$title - $k - $v2.php");

                                }

                                $e = implode(".",array($i,$i2,$i3));
                                print "<dt>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i.$i2#$e\">".$v2."</a></dt>\n";
                                $i3++;
                            }
                            print "</dl></dd>";

                        } else {
                            $str = "";
                            if( ! file_exists("docs/$title - $t.php")) {
                                $missing[0]++;
                                $str .= " [ <font color='red'>doc</font> ] ";
                                //touch("docs/$title - $t.php");
                            }
                            if( ! file_exists("codes/$title - $t.php")) {
                                $missing[1]++;
                                $str .= " [ <font color='red'>code</font> ] ";
                                //touch("codes/$title - $t.php");
                            }
                            print "<dt>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i#$e\">".$t."</a></dt>\n";
                        }
                        $i2++;

                    }
                    $i++;
                    print "</dl></dd>";
                }
                print "</dl>\n";

                } else {
                    $i = 1;
                    $ex = explode(".",$_REQUEST["index"]);


                    $paths = array2path($menu);

                    if( ! isset($paths[$ex[0]]))
                        exit;

                    $break = false;
                    $tmp   = $paths;

                    foreach($tmp as $path => $title) {
                        $e = explode(".", $path);
                        if(count($e) > 2) {
                            unset($tmp[$path]);
                        }
                    }

                    $prev = 1;
                    
                    foreach($tmp as $path => $title) {
                        if($break)
                            break;

                        if($path === $_REQUEST["index"]) {
                            $break = true;
                        } else {
                            $prev = $path;
                        }
                    }

                    $index = $_REQUEST['index'];

                    print "<table width='100%'>";
                    print "<tr><td colspan=3 align='center'><b></td></tr>";
                    print "<tr><td colspan=3 align='center'><b class='title'>".$paths[$ex[0]]."</b></td></tr>";
                    print "<tr><td align='left'><b><a href=documentation.php?index=$prev>Prev</a></b></td>";
                    print "<td align='right'><b><a href=documentation.php?index=$path>Next</a></b></td></tr>";
                    print "<tr><td>&nbsp;</td></tr>";
                    print "</table>";


                 $tmp = $ex;
                    if(count($tmp) > 2) {
                        //array_pop($tmp);
                    }
                    foreach($tmp as $k => $v) {
                        $numbers[] = $v;
                        $curr = implode(".",$numbers);
                        $stack[] = $paths[$curr];
                    }
                    if(isset($ex[1])) {
                        $name = implode(" - ", $stack);

                        print "<a name='$path'><b class='title'>".$paths[$curr]."</b></a><hr>";

                        $n = $numbers;

                        $o = $paths[$n[0]];
                        $numpath  = implode(".", array($n[0], $n[1]));
                        $o2 = $paths[$numpath];

                        $value = $menu[$o];
                        if( ! is_array($value))
                            exit;


                        if(in_array($o2, $value)) {
                            render_block($name);
                        } else {
                            $value = $menu[$o][$o2];

                            if(is_array($value)) {
                                foreach($value as $k => $title) {
                                    $numpath2 = $numpath . '.' . ($k + 1);
                                    print "<br \><a name='".$numpath2."'><b class='title'>".$title."</b></a><hr style='height: 1px' \>";

                                    $s = $name." - ".$title;

                                    render_block($s);
                                }
                            }
                        }
                    } else {

                        $tmp = $paths[$ex[0]];
                        $i = 1;
                        foreach($menu[$tmp] as $title => $value) {
                            $n = $ex[0].".".$i;

                            if(is_scalar($value)) {
                                print "<dd>".$n.". <a href=\"documentation.php?index=".$n."\">".$value."</a><br \>\n";
                            } else {
                                print "<dd>".$n.". <a href=\"documentation.php?index=".$n."\">".$title."</a><br \>\n";
                            }
                            $i++;
                        }
                    }
                    }
                ?>
                <td bgcolor="white" align="left" valign="top" width=300>
                <?php
                    $i = 1;
                    print "<dd>-- <b><a href=documentation.php>index</a></b><br>\n";
                    foreach($menu as $title => $titles) {
                        print "<dd>".$i.". <a href=\"".$_SERVER['PHP_SELF']."?index=$i\">".$title."</a><br>\n";
                        $i++;
                    }
                ?>
                </td>
            <td>
            <tr>
            <td bgcolor="white" valign="top" colspan="2">
            <?php






            /**
            foreach($menu as $title => $titles) {
                if($i == $ex[0]) {

                print $i.". <b><a class=\"big\" name=\"$i\">".$title."</a></b><hr><br>\n";
                    $i2 = 1;
                    foreach($titles as $k => $t) {
                        $e = "$i.".$i2;

                        if(is_array($t)) {
                            $tmp = "$title - $k";

                            if( ! isset($ex[1]) || $i2 != $ex[1]) {
                                $i2++;
                                continue;
                            }

                            print $e." <b><a class=\"big\" name=\"$e\">".$k."</a></b><hr><br><br>\n";
                            foreach($t as $k2 => $t2) {
                                if( ! isset($ex[1]) || $i2 != $ex[1])
                                    break;
                                $e = "$i.".$i2.".".($k2+1);
                                render($tmp,$t2,$e);
                            }
                        } else {
                            if( ! isset($ex[1])) {
                                render($title,$t,$e);
                            }
                        }
                        $i2++;
                    }
                }
                $i++;

            }
            */
            ?>
            </td>
            </tr>
        </td>
        <td>
        </td>
    </tr>
</table>

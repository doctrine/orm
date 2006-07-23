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
        $h->loadString($c);
        print $h->toHtml();
        print "</td></tr>";
        print "</table>";
    }
    print "<br>";
}

function render_block($name) {
    $h = new PHP_Highlight;
    if(file_exists("docs/$name.php")) {
        $c = file_get_contents("docs/$name.php");
        if(substr($c,0,5) == "<?php") {
            include("docs/$name.php");
        } else {
            print $c."<br><br>";
        }
    }
    if(file_exists("codes/$name.php")) {
        print "<table width=500 border=1 class='dashed' cellpadding=0 cellspacing=0>";
        print "<tr><td>";
        $c = file_get_contents("codes/$name.php");
        $h->loadString($c);
        print $h->toHtml();
        print "</td></tr>";
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
                        "Starting new project",
                        "Setting table definition" => array(
                                        "Introduction",
                                        "Data types and lengths",
                                        "Constraints and validators",
                                        ),
                        ),
           "Basic Components" =>
                        array(
                        "Manager" 
                                => array("Introduction",
                                         "Opening a new session",
                                         "Managing sessions"),
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
                                         "Getting identifiers",
                                         "Callbacks"),
                        "Session"
                                => array("Introduction",
                                         "Availible drivers",
                                         "Getting a table object",
                                         "Flushing the session",
                                         "Limiting queries",
                                         "Querying the database",
                                         "Getting session state"),

                        "Collection"
                                => array("Introduction",
                                         "Accessing elements",
                                         "Adding new elements",
                                         "Getting collection count",
                                         "Saving the collection",
                                         "Deleting collection",
                                         "Fetching strategies",
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
                                         "Fetching strategies",
                                         "Lazy property fetching",
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
                        "Statement - <font color='red'>UNDER CONSTRUCTION</font>" => array("Introduction",
                                             "Setting parameters",
                                             "Getting parameters",
                                             "Getting row count",
                                             "Executing the statement"),

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
                                        "Deleting related records"),
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
                                      ),
                      "Validators" => array(
                                      "Intruduction",
                                      "Validating transactions",
                                      "Analyzing the ErrorStack",
                                      "List of predefined validators"
                                      ),
                      "View"        => array(
                                      "Intoduction",
                                      "Managing views",
                                      "Using views"
                                      ),
                      /**
                      "Hook"        => array(
                                      "Introduction",
                                      "Parameter hooking",
                                      "Paging",
                                      "Setting conditions",
                                      "Sorting"
                                      ),
                      */
                      "Cache"       => array(
                                      "Introduction",
                                      "Query cache"),

                      "Locking Manager" => array(
                                        "Introduction",
                                        "Pessimistic locking",
                                        "Examples"),
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
           "Transactions" => array(
                        "Introduction",
                        "Unit of work",
                        "Locking strategies" =>
                            array("Pessimistic locking",
                                  "Optimistic locking"),
                        "Nesting"
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
            "Technology" => array(
                "Architecture",
                "Design patterns used",
                "Speed",
                "Internal optimizations" =>

                        array("DELETE",
                              "INSERT",
                              "UPDATE"),
                ),
            "Real world examples" => array("User management system","Forum application","Album lister")
            );

?>


<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td width=800 align="left" valign="top">
            <table width="100%" cellspacing=1 cellpadding=1>
            <tr>
                <td colspan=2 bgcolor="white">
                <img src="images/logo.jpg" align="left"><b class="title">Doctrine - PHP Data Persistence and ORM Tool</b>
                <hr>
                </td>
            </tr>
            <tr>
                <td bgcolor="white" valign="top" width="570">
                <?php
                if( ! isset($_REQUEST["index"])) {
                $i = 1;
                $missing = array();
                $missing[0] = 0;
                $missing[1] = 0;
                foreach($menu as $title => $titles) {
                    print $i.". <a href=\"".$_SERVER['PHP_SELF']."?index=$i#$i\">".$title."</a><br>\n";
                    $i2 = 1;
                    foreach($titles as $k => $t) {
                        $e = "$i.".$i2."";
                        if(is_array($t)) {
                            print "<dd>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i.$i2#$e\">".$k."</a><br>\n";

                            $i3 = 1;
                            foreach($t as $k2 => $v2) {
                                $str = "";
                                if( ! file_exists("docs/$title - $k - $v2.php")) {
                                    $missing[0]++;
                                    $str .= " [ <font color='red'>doc</font> ] ";
                                }
                                if( ! file_exists("codes/$title - $k - $v2.php")) {
                                    $missing[1]++;
                                    $str .= " [ <font color='red'>code</font> ] ";
                                }

                                $e = implode(".",array($i,$i2,$i3));
                                print "<dd><dd>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i.$i2#$e\">".$v2."</a><br>\n";
                                $i3++;
                            }
                        } else {
                            $str = "";
                            if( ! file_exists("docs/$title - $t.php")) {
                                $missing[0]++;
                                $str .= " [ <font color='red'>doc</font> ] ";
                            }
                            if( ! file_exists("codes/$title - $t.php")) {
                                $missing[1]++;
                                $str .= " [ <font color='red'>code</font> ] ";
                            }
                            print "<dd>".$e." <a href=\"".$_SERVER['PHP_SELF']."?index=$i#$e\">".$t."</a><br>\n";
                        }
                        $i2++;
                    }
                    $i++;
                }
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
                    
                        if($path == $_REQUEST["index"]) {
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

                        print "<b class='title'>".$paths[$curr]."</b><hr>";

                        $n = $numbers;

                        $o = $paths[$n[0]];
                        $s  = implode(".", array($n[0], $n[1]));
                        $o2 = $paths[$s];

                        $value = $menu[$o];
                        if( ! is_array($value)) 
                            exit;
                            

                        if(in_array($o2, $value)) {
                            render_block($name);
                        } else {     
                            $value = $menu[$o][$o2];

                            if(is_array($value)) {
                                foreach($value as $k => $title) {
                                    print "<br \><b class='title'>".$title."</b><hr style='height: 1px' \>";

                                    $s = $name." - ".$title;

                                    render_block($s);
                                }
                            }
                        }
                    } else {
                        //if( ! isset($menu[$ex[0]]))
                        //    exit;
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
                <td bgcolor="white" align="left" valign="top">
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

<?php
include("top.php"); 
require_once("highlight.php");
error_reporting(E_ALL | E_STRICT);
$f = file_get_contents('menu.php');
$a = explode(PHP_EOL, $f);
$res = array();
$curr = false;


class DocTool
{
    private $index;
    
    protected $highlighter;

    public function __construct()
    {
        $this->highlighter = new PHP_Highlight;
    }
    public function parseIndex2($index)
    {
    	$ret = array();
        $path = array();
        $counters = array();

        foreach ($index as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $v = rtrim($v);
            $i = count($path) - 1;

            $i = ($i > 0) ? $i : 0;

            $indent = substr_count($v, '    ');

            if ( ! isset($counters[$indent])) {
                $counters[$indent] = 0;
            }

            if ($indent > $i) {
                $counters[$indent]++;

                $path[] = trim($v);
            } else {
                $steps = abs($i - $indent);

                $key = ($i - $steps);
                while ($steps--) {
                    array_pop($path);
                    array_pop($counters);
                }
                
                $counters[$key]++;

                $path[$key] = trim($v);
            }

            $chapterName = implode(' - ', $path);

            $ret[] = array('index' => implode('.', $counters),
                           'name'  => $chapterName);

        }
        return $ret;
    }   
    /**
     if ($indent == $i) {
                $path[$i] = $v;
            } else {
    */
    public function parseIndex($index, $path = array(), $counters = array())
    {
    	$ret = array();

        foreach ($index as $k => $v) {
            $i = count($path) - 1;

            $counters[$i]++;

            if (is_array($v)) {
                if ( ! is_numeric($k)) {
                    $tmp   = $path;
                    $tmp[] = $k;
                    
                    $chapterName = ( ! empty($path)) ? implode(' - ', $path) . ' - ' . $k : $k;

                    $ret[] = array('index' => implode('.', $counters),
                                   'name'  => $chapterName);
                }

                $ret   = array_merge($ret, $this->parseIndex($v, $tmp, $counters));
            } else {
                $chapterName = ( ! empty($path)) ? implode(' - ', $path) . ' - ' . $v : $v;

                $ret[] = array('index' => implode('.', $counters), 
                               'name'  => $chapterName);
            }
        }
        return $ret;
    }
    public function renderBlock($name) {

        if(file_exists("docs/$name.php")) {
            $c = file_get_contents("docs/$name.php");

            if(substr($c, 0, 5) == "<?php") {
                include("docs/$name.php");
            } else {
                print $c."<br><br>";
            }
        }
        if(file_exists("codes/$name.php")) {
            $c = file_get_contents("codes/$name.php");
            $c = trim($c);
            $this->renderCode($c);
        }
    }
    public function renderCode($code = null) {
        if( ! empty($code)) {
    
            $this->highlighter->loadString($code);
    
            print "<table width=500 border=1 class='dashed' cellpadding=0 cellspacing=0>";
            print "<tr><td><b>";
    
            $this->highlighter->toHtml();
            print "</b></td></tr>";
            print "</table>";
        }
    }
}
print "<pre>";
$doc = new DocTool();

function renderCode($code = null)
{
    global $doc;
    
    return $doc->renderCode($code);
}
$i   = $doc->parseIndex2($a);

//print_r($i);

?>
<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td align="left" valign="top">
            <table width="100%" cellspacing=0 cellpadding=0>
            <tr>
                <td colspan=2 bgcolor="white">
                <img src="images/logo.jpg" align="left"><b class="title">Doctrine - PHP Data Persistence and ORM Tool</b>
                <hr>
                </td>
            </tr>
            <tr>
                <td bgcolor="white" valign="top">
                <div class='index'>
                <?php
                if ( ! isset($_GET['chapter'])) {

                    foreach ($i as $k => $v) {
                        $indexes = explode('.', $v['index']);
                        $level = count($indexes);
                        $e = explode(' - ', $v['name']);

                        print '<div class=level' . $level . '><font class=level' . $level . '>&nbsp;'. $v['index'] . '. <a href=documentation2.php?chapter='
                              . urlencode($v['name']) . ">" . end($e) ."</a></font></div>";
                    }
                } else {


                    $e = explode(' - ', $_GET['chapter']);
                    $subchapters = false;
                    $found = false;

                    foreach ($i as $k => $v) {
                        if ($found) {
                            if (strncmp($v['name'], $_GET['chapter'], strlen($_GET['chapter'])) === 0) {
                                $subchapters = true;                                                                     	
                            }
                            break;
                        }
                        $parts = explode(' - ', $v['name']);
                        $indexes = explode('.', $v['index']);

                        if ($v['name'] === $_GET['chapter']) {
                            $prev = $i[($k - 1)];
                            $next = $i[($k + 1)];
                            $foundKey   = ($k + 1);
                            $found = $v;
                        }

                    }

                ?>
                <table width='100%'>
                <tr><td colspan=3 align='center'><b></td></tr>
                <tr><td colspan=3 align='center'><b class='title'> 
                <?php 
                    print 'Chapter ' . $indexes[0] . '. ' . array_shift($parts);
                ?>
                </b></td></tr>
                <tr><td align='left'><b><a href=documentation2.php?chapter=<?php print urlencode($prev['name']); ?>>Prev</a></b></td>
                <td align='right'><b><a href=documentation2.php?chapter=<?php print urlencode($next['name']); ?>>Next</a></b></td></tr>
                <tr><td>&nbsp;</td></tr>
                </table>


                    <b class='title'>
                    <?php

                    print $indexes[0] . '.' . $indexes[1] . '. ' . $parts[0];
                    ?>
                    </b>
                    <hr>
                <?php
                if ($subchapters) {
                ?>
                <b class='title'>
                Table of contents<br \>
                </b>
                <?php
                    for ($x = $foundKey; $x < count($i); $x++) {
                        $p = explode(' - ', $i[$x]['name']);
                        $count = (count($parts) + 1);
                        while($count--) {
                            array_shift($p);
                        }
                        ?>
                        <a href=documentation2.php?chapter=<?php print urlencode($i[$x]['name']) . '>' . implode(' - ', $p); ?></a><br \>
                        <?php

                        if (strncmp($i[$x]['name'], $_GET['chapter'], strlen($_GET['chapter'])) !== 0) {
                            break;
                        }
                    }
                } else {
                ?>
                    <b class='title'>
                    <?php
                    if (isset($parts[1])) {
                        print $indexes[0] . '.' . $indexes[1] . '.' . $indexes[2] . '. ' . $parts[1];
                    }
                    ?>
                    </b>
                    <hr class='small'>

                <?php
                }
                $doc->renderBlock($found['name']);
                }


                ?>
                </div>
                </td>
            </tr>
        </td>
        <td>
        </td>
    </tr>
</table>

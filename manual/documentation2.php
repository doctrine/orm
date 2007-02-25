<?php
include("top.php"); 
require_once("highlight.php");
error_reporting(E_ALL);
set_include_path(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor/');

$f = file_get_contents('menu.php');
$a = explode(PHP_EOL, $f);
$res = array();
$curr = false;

require_once('Text/Wiki.php');
require_once('Text/Wiki/MediaWiki.php');




class DocTool
{
    private $index;
    
    protected $highlighter;
    
    protected $wiki;

    public function __construct()
    {
        $this->highlighter = new PHP_Highlight;
        $this->wiki = new Text_Wiki;
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

    public function renderBlock($name) 
    {

        if (file_exists("docs/$name.php")) {
            $c = file_get_contents("docs/$name.php");

            if (substr($c, 0, 5) == "<?php") {
                include("docs/$name.php");
            } elseif (strpos($c, '<br \>') !== false) {
                print $c;
            } else {
                print $this->wiki->transform($c) . "<br><br>";
            }
        }
        if (file_exists("codes/$name.php")) {
            $c = file_get_contents("codes/$name.php");
            $c = trim($c);
            $this->renderCode($c);
        }
    }
    public function renderCode($code = null) 
    {
        if( ! empty($code)) {
    
            $this->highlighter->loadString($code);
    
            print "<table border=1 class='dashed' cellpadding=0 cellspacing=0>";
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
                <td colspan=3 bgcolor="white">
                <img src="images/logo.jpg" align="left"><b class="title">Doctrine - PHP Data Persistence and ORM Tool</b>
                <hr>
                </td>
            </tr>
            <tr>
                <td bgcolor="white" valign="top">
                <?php
                include('content.php');
                ?>
                </td>
            </tr>
        </td>
        <td>
        </td>
    </tr>
</table>

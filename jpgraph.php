<?php

// If you dont have jpgraph, you need to download it from:
// http://www.aditus.nu/jpgraph/jpdownload.php

$jpgraphPath = '../lib/jpgraph-3.0.3/src'; // put the path to your jpgraph install here

// ------------------------------------------

require_once "$jpgraphPath/jpgraph.php";
require_once "$jpgraphPath/jpgraph_line.php";

$logsPath = 'logs/';

$revisions = array();
$graphs = array();

if (isset($_POST['test'])) {
    list($testsuite, $testcase) = explode('#', $_POST['test']);
}

$items = scandir($logsPath);
foreach ($items as $item) {
    if ($item[0] != '.') {
        $revisions[] = $item;
    }
}

foreach ($revisions as $rev) {
    $xml = simplexml_load_file($logsPath . $rev . '/log.xml');
    foreach ($xml->testsuite as $suite) {
        foreach ($suite->testcase as $test) {
            $name = (string)$suite['name'] . '#' . (string)$test['name'];
            $graphs[$name][] = (double)$test['time'];
        }
    }
}

if (isset($testsuite) && isset($testcase)) {
    $graphName = $testsuite . '#' . $testcase;
    $graphData = $graphs[$graphName];

    // Create the graph. These two calls are always required
    $graph = new Graph(650,250);

    //$graph->SetScale('intint');
    $graph->SetScale('textlin');
    $graph->yaxis->scale->SetAutoMin(0);

    $graph->title->Set($testsuite);
    $graph->subtitle->Set($testcase);

    $graph->xaxis->title->Set('revision');
    $graph->yaxis->title->Set('seconds');
    $graph->SetMargin(100, 100, 50, 50);

    // Create the linear plot
    $lineplot = new LinePlot($graphData);
    $lineplot->SetColor('blue');

    $graph->xaxis->SetTickLabels($revisions);

    // Add the plot to the graph
    $graph->Add($lineplot);

    // Display the graph
    $graph->Stroke();
} else {

    echo '<html><head></head><body>';
    echo 'Pick a test and click "show":<br/>';
    echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
    
    echo '<select name="test">';
    
    foreach ($graphs as $name => $data) {
        echo '<option value="' . $name . '">' . $name . '</option>';
    }
    
    echo '</select>';
    
    echo '<button type="submit">Show</button>';
    
    echo '</form>';
    echo '</body></html>';
    
}




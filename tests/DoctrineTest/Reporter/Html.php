<?php
class DoctrineTest_Reporter_Html extends DoctrineTest_Reporter{
        public function paintHeader($name) {
?>
<html>
<head>
  <title>Doctrine Unit Tests</title>
  <style>
.fail { color: red; } pre { background-color: lightgray; }
  </style>
</head>

<body>

<h1><?php echo $name ?></h1>
<?php

        }

        public function paintFooter()
        {

            print '<pre>';
            foreach ($this->_test->getMessages() as $message) {
                print "<p>$message</p>";
            }
            print '</pre>';
            $colour = ($this->_test->getFailCount() > 0 ? 'red' : 'green');
            print '<div style=\'';
            print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
            print '\'>';
            print $this->_test->getTestCaseCount() . ' test cases.';
            print '<strong>' . $this->_test->getPassCount() . '</strong> passes and ';
            print '<strong>' . $this->_test->getFailCount() . '</strong> fails.';
            print '</div>';
        }
    }

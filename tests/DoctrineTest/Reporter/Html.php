<?php
class DoctrineTest_Reporter_Html extends DoctrineTest_Reporter {
    public $progress = false;
    
    public function paintHeader($name) {
?>
<html>
<head>
  <title>Doctrine Unit Tests</title>
  <style>
  .fail
  {
      color: red;
  }
  
  #messages
  {
      border-left: 1px solid #333333;
      border-right: 1px solid #333333;
      background-color: #CCCCCC;
      padding: 10px;
  }
  
  #summary
  {
      background-color: red;
      padding: 8px;
      color: white;
  }
  
  #wrapper
  {
      
  }
  
  #wrapper h1
  {
      font-size: 20pt;
      margin-bottom: 10px;
      font-weight: bold;
  }
  </style>
</head>

<body>

<div id="wrapper">
<h1><?php echo $name ?></h1>

<?php
        }

        public function paintFooter()
        {
            $this->paintSummary();
            $this->paintMessages();
            $this->paintSummary();
            print '</div>';
        }
        
        public function paintMessages()
        {
            print '<div id="messages">';
            foreach ($this->_test->getMessages() as $message) {
                print "<p>$message</p>";
            }
            print '</div>';
        }
        
        public function paintSummary()
        {
            $color = ($this->_test->getFailCount() > 0 ? 'red' : 'green');
            print '<div id="summary" style="';
            print "background-color: $color;";
            print '">';
            print $this->_test->getTestCaseCount() . ' test cases. ';
            print '<strong>' . $this->_test->getPassCount() . '</strong> passes and ';
            print '<strong>' . $this->_test->getFailCount() . '</strong> fails.';
            print '</div>';
        }

        public function getProgressIndicator() {}
    }

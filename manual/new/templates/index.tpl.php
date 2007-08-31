<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>

<title><?php echo $title; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<link rel="stylesheet" type="text/css" href="styles/basic.css" media="screen"/>
<link rel="stylesheet" type="text/css" href="styles/print.css" media="print"/>

</head>

<body>

<div id="wrap">

<div id="content">

<h1><?php echo $title; ?></h1>

<p>There are several different versions of this manual available online:
<ul>
<li>View as <a href="?one-page">all chapters in one page</a>.</li>
<li>View as <a href="?chapter=<?php echo $toc->findByIndex('1.')->getPath(); ?>">one chapter per page</a>.</li>
<li>Download the <a href="?format=pdf">PDF version</a>.</li>
</ul>
</p>


</div>

</div>

</body>
</html>

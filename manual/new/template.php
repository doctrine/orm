<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>

<title><?php echo $title; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>styles/basic.css" media="screen"/>
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>styles/print.css" media="print"/>

<!--[if gte IE 5.5]>
<![if lt IE 7]>
<link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>styles/iefix.css"/>
<![endif]>
<![endif]-->

<script type="text/javascript" src="<?php echo $baseUrl; ?>scripts/tree.js"></script>

</head>

<body>

<div id="wrap">

<div id="sidebar">
<?php $tool->renderToc(); ?>
</div>

<div id="content">
<?php

try { 
    $tool->render();
} catch (Exception $e) {
?>

<h1>Doctrine Manual</h1>

<p>You can view this manual online as
<ul>
<li><a href="<?php echo $tool->getOption('clean-url') ? "${baseUrl}one-page" : '?one-page=1'; ?>">everything on a single page</a>, or</li>
<li><a href="<?php echo $tool->makeUrl($tool->findByIndex('1.')->getPath()); ?>">one chapter per page</a>.</li>
</ul>
</p>

<?php
}
?>
</div>

</div>

</body>
</html>

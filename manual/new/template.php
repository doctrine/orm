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

<script type="text/javascript" src="<?php echo $baseUrl; ?>scripts/util.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>scripts/tree.js"></script>
<script type="text/javascript" src="<?php echo $baseUrl; ?>scripts/toc.js"></script>

</head>

<body>

<div id="wrap">

<?php if($tool->getOption('section') || $tool->getOption('one-page')): ?>

<div id="sidebar">

<div id="table-of-contents">

<div id="toc-toggles"></div>

<h1>Table of Contents</h1>

<?php $tool->renderToc(); ?>

<script type="text/javascript">
//<![CDATA[
var tocHideText = "hide"; var tocShowText = "show"; createTocToggle();
var tocStickyText = "sticky"; var tocUnstickyText = 'unstick'; createTocStickyToggle();
//]]>
</script>

<p>
<?php if($tool->getOption('one-page')): ?>
<a href="<?php echo ($tool->getOption('clean-url') ? "${baseUrl}chapter/" : '?chapter=') . $tool->findByIndex('1.')->getPath(); ?>">View one chapter per page</a>
<?php else: ?>
<a href="<?php echo ($tool->getOption('clean-url') ? "${baseUrl}one-page" : '?one-page=1') . '#' . $tool->getOption('section')->getPath(); ?>">View all in one page</a>
<?php endif; ?>
</p>

</div>

</div>

<?php endif; ?>

<div id="content">

<?php if($tool->getOption('section') || $tool->getOption('one-page')): ?>
<?php $tool->render(); ?>
<?php else: ?>

<h1>Doctrine Manual</h1>

<p>You can view this manual online as
<ul>
<li><a href="<?php echo $tool->getOption('clean-url') ? "${baseUrl}one-page" : '?one-page=1'; ?>">everything on a single page</a>, or</li>
<li><a href="<?php echo $tool->makeUrl($tool->findByIndex('1.')->getPath()); ?>">one chapter per page</a>.</li>
</ul>
</p>

<?php endif; ?>

</div>

</div>


</body>
</html>

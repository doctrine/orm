<h1>Doctrine Manual</h1>

<p>There are several different versions of this manual available online:
<ul>
<li>View as <a href="?one-page">all chapters in one page</a>.</li>
<li>View as <a href="?chapter=<?php echo $toc->findByIndex('1.')->getPath(); ?>">one chapter per page</a>.</li>
<li>Download the <a href="?format=pdf">PDF version</a>.</li>
</ul>
</p>

<?php if( isset($output) ): ?>
  <?php echo $output; ?>
<?php endif; ?>

<?php slot('right'); ?>
  <?php echo $renderer->renderToc(); ?>
<?php end_slot(); ?>
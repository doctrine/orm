<div class="content">
  <h1><?php echo link_to('Doctrine Manual', '@manual'); ?></h1>
</div>

<div class="content" id="manual">
  <?php if( isset($output) ): ?>
    <?php echo $output; ?>
  <?php else: ?>
    <p>There are several different versions of this manual available online:
      <ul>
        <li>View as <a href="?one-page">all chapters in one page</a>.</li>
        <li>View as <a href="?chapter=<?php echo $toc->findByIndex('1.')->getPath(); ?>">one chapter per page</a>.</li>
        <li>Download the <a href="?format=pdf">PDF version</a>.</li>
      </ul>
    </p>
  <?php endif; ?>
</div>

<?php slot('right'); ?>
  <h2>Table of Contents</h2>
  <?php echo $renderer->renderToc(); ?>
<?php end_slot(); ?>
<div id="manual">
  <?php if( $tool->getOption('section') || $tool->getOption('one-page') ): ?>
    <?php $tool->render(); ?>
  <?php else: ?>
    <h1>Table of Contents</h1>

    <?php $tool->renderToc(); ?>
  <?php endif; ?>
</div>

<?php slot('right'); ?>
  <?php echo get_partial('table_of_contents', array('tool' => $tool)); ?>
<?php end_slot(); ?>
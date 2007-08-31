<?php $hides = $this->getParameterValue('list.hide', array()) ?>
<?php foreach ($this->getColumns('list.display') as $column): ?>
<?php if (in_array($column->getName(), $hides)) continue ?>
<?php $credentials = $this->getParameterValue('list.fields.'.$column->getName().'.credentials') ?>
<?php if ($credentials): $credentials = str_replace("\n", ' ', var_export($credentials, true)) ?>
    [?php if ($sf_user->hasCredential(<?php echo $credentials ?>)): ?]
<?php endif; ?>
  <th id="sf_admin_list_th_<?php echo $column->getName() ?>">
    <?php if ($column->isReal()): ?>
      [?php if ($sf_user->getAttribute('sort', null, 'sf_admin/<?php echo $this->getSingularName() ?>/sort') == '<?php echo $column->getName() ?>'): ?]
      [?php echo link_to(__('<?php echo str_replace("'", "\\'", $this->getParameterValue('list.fields.'.$column->getName().'.name')) ?>'), '<?php echo $this->getModuleName() ?>/list?sort=<?php echo $column->getName() ?>&type='.($sf_user->getAttribute('type', 'asc', 'sf_admin/<?php echo $this->getSingularName() ?>/sort') == 'asc' ? 'desc' : 'asc')) ?]
      ([?php echo __($sf_user->getAttribute('type', 'asc', 'sf_admin/<?php echo $this->getSingularName() ?>/sort')) ?])
      [?php else: ?]
      [?php echo link_to(__('<?php echo str_replace("'", "\\'", $this->getParameterValue('list.fields.'.$column->getName().'.name')) ?>'), '<?php echo $this->getModuleName() ?>/list?sort=<?php echo $column->getName() ?>&type=asc') ?]
      [?php endif; ?]
    <?php else: ?>
    [?php echo __('<?php echo str_replace("'", "\\'", $this->getParameterValue('list.fields.'.$column->getName().'.name')) ?>') ?]
    <?php endif; ?>
    <?php echo $this->getHelpAsIcon($column, 'list') ?>
  </th>
<?php if ($credentials): ?>
    [?php endif; ?]
<?php endif; ?>
<?php endforeach; ?>
